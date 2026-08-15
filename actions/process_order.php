<?php
/**
 * actions/process_order.php
 * Traitement de la commande client. AUCUN HTML ici — uniquement logique
 * + redirection.
 *
 * Points de sécurité clés :
 * 1. Vérification CSRF avant tout traitement.
 * 2. Validation stricte de chaque champ (type, longueur, format).
 * 3. Les PRIX ne sont JAMAIS acceptés depuis le client : on ne fait
 *    confiance qu'à l'id produit envoyé, et on relit le prix réel en
 *    base pour chaque article (protection contre la manipulation du
 *    panier / prix côté navigateur).
 * 4. Insertion transactionnelle (orders + order_items) : tout ou rien.
 * 5. L'échec de l'envoi d'e-mail n'annule jamais une commande déjà
 *    enregistrée (l'email est une notification, pas une condition
 *    métier) — mais orders.email_sent trace l'état pour un renvoi manuel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/send-confirmation-email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

csrf_verify();

/**
 * Redirige vers checkout.php avec les erreurs et les valeurs saisies,
 * pour que l'utilisateur ne perde pas sa saisie.
 */
function redirect_with_errors(array $errors, array $oldInput): never
{
    $_SESSION['checkout_errors']    = $errors;
    $_SESSION['checkout_old_input'] = $oldInput;
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}

// -----------------------------------------------------------------------
// 1. Nettoyage et validation des champs client
// -----------------------------------------------------------------------
$oldInput = [
    'lastname'      => clean_input($_POST['lastname'] ?? ''),
    'firstname'     => clean_input($_POST['firstname'] ?? ''),
    'email'         => clean_input($_POST['email'] ?? ''),
    'phone'         => clean_input($_POST['phone'] ?? ''),
    'city'          => clean_input($_POST['city'] ?? ''),
    'neighborhood'  => clean_input($_POST['neighborhood'] ?? ''),
    'delivery_date' => clean_input($_POST['delivery_date'] ?? ''),
    'notes'         => clean_input($_POST['notes'] ?? ''),
];

$errors = [];

if ($oldInput['lastname'] === '' || mb_strlen($oldInput['lastname']) > 100) {
    $errors[] = 'Le nom est requis (100 caractères maximum).';
}
if ($oldInput['firstname'] === '' || mb_strlen($oldInput['firstname']) > 100) {
    $errors[] = 'Le prénom est requis (100 caractères maximum).';
}
if (!filter_var($oldInput['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse e-mail n'est pas valide.";
}
if (!preg_match('/^[0-9+\s.-]{6,30}$/', $oldInput['phone'])) {
    $errors[] = 'Le numéro de téléphone est invalide.';
}
if ($oldInput['city'] === '' || mb_strlen($oldInput['city']) > 100) {
    $errors[] = 'La ville est requise.';
}
if ($oldInput['neighborhood'] === '' || mb_strlen($oldInput['neighborhood']) > 100) {
    $errors[] = 'Le quartier est requis.';
}

$deliveryTimestamp = strtotime($oldInput['delivery_date']);
$tomorrow = strtotime('+1 day', strtotime(date('Y-m-d')));
if ($deliveryTimestamp === false || $deliveryTimestamp < $tomorrow) {
    $errors[] = 'La date de livraison doit être un jour à partir de demain.';
}

if (mb_strlen($oldInput['notes']) > 500) {
    $errors[] = 'Les notes ne doivent pas dépasser 500 caractères.';
}

// -----------------------------------------------------------------------
// 2. Décodage et validation du panier
// -----------------------------------------------------------------------
$cartItemsRaw = $_POST['cart_items_json'] ?? '';
$cartItems = json_decode((string) $cartItemsRaw, true);

if (!is_array($cartItems) || empty($cartItems)) {
    $errors[] = 'Votre panier est vide ou invalide.';
    $cartItems = [];
}

// On ne garde que des id produits entiers positifs, avec une quantité saine
$requestedItems = [];
foreach ($cartItems as $item) {
    $productId = isset($item['id']) ? (int) $item['id'] : 0;
    $quantity  = isset($item['quantity']) ? (int) $item['quantity'] : 0;

    if ($productId <= 0 || $quantity <= 0 || $quantity > 50) {
        continue; // on ignore silencieusement une ligne malformée
    }
    $requestedItems[$productId] = ($requestedItems[$productId] ?? 0) + $quantity;
}

if (empty($requestedItems)) {
    $errors[] = 'Aucun article valide dans votre panier.';
}

if (!empty($errors)) {
    redirect_with_errors($errors, $oldInput);
}

// -----------------------------------------------------------------------
// 3. Revalidation des produits et des PRIX depuis la base (source de vérité)
//    -> on ne fait jamais confiance au prix envoyé par le navigateur.
// -----------------------------------------------------------------------
$pdo = getDbConnection();

$placeholders = implode(',', array_fill(0, count($requestedItems), '?'));
$stmt = $pdo->prepare(
    "SELECT id, name, price, stock FROM products
     WHERE id IN ($placeholders) AND is_active = 1"
);
$stmt->execute(array_keys($requestedItems));
$dbProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($dbProducts) !== count($requestedItems)) {
    redirect_with_errors(
        ["Un ou plusieurs articles de votre panier ne sont plus disponibles. Merci de vérifier votre panier."],
        $oldInput
    );
}

$orderItemsToInsert = [];
$totalAmount = 0.0;

foreach ($dbProducts as $product) {
    $quantity = $requestedItems[(int) $product['id']];
    $subtotal = (float) $product['price'] * $quantity;
    $totalAmount += $subtotal;

    $orderItemsToInsert[] = [
        'product_id'   => (int) $product['id'],
        'product_name' => $product['name'],
        'unit_price'   => (float) $product['price'],
        'quantity'     => $quantity,
        'subtotal'     => $subtotal,
    ];
}

// -----------------------------------------------------------------------
// 4. Insertion transactionnelle : orders + order_items + sales_count
// -----------------------------------------------------------------------
try {
    $pdo->beginTransaction();

    $orderRef = generate_order_ref($pdo);

    $insertOrder = $pdo->prepare(
        'INSERT INTO orders
            (order_ref, customer_lastname, customer_firstname, customer_email,
             customer_phone, city, neighborhood, delivery_date, status,
             total_amount, notes)
         VALUES
            (:order_ref, :lastname, :firstname, :email,
             :phone, :city, :neighborhood, :delivery_date, \'pending\',
             :total_amount, :notes)'
    );
    $insertOrder->execute([
        'order_ref'      => $orderRef,
        'lastname'       => $oldInput['lastname'],
        'firstname'      => $oldInput['firstname'],
        'email'          => $oldInput['email'],
        'phone'          => $oldInput['phone'],
        'city'           => $oldInput['city'],
        'neighborhood'   => $oldInput['neighborhood'],
        'delivery_date'  => date('Y-m-d', $deliveryTimestamp),
        'total_amount'   => $totalAmount,
        'notes'          => $oldInput['notes'] !== '' ? $oldInput['notes'] : null,
    ]);

    $orderId = (int) $pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal)
         VALUES (:order_id, :product_id, :product_name, :unit_price, :quantity, :subtotal)'
    );
    $incrementSales = $pdo->prepare(
        'UPDATE products SET sales_count = sales_count + :qty WHERE id = :id'
    );

    foreach ($orderItemsToInsert as $line) {
        $insertItem->execute([
            'order_id'     => $orderId,
            'product_id'   => $line['product_id'],
            'product_name' => $line['product_name'],
            'unit_price'   => $line['unit_price'],
            'quantity'     => $line['quantity'],
            'subtotal'     => $line['subtotal'],
        ]);

        $incrementSales->execute([
            'qty' => $line['quantity'],
            'id'  => $line['product_id'],
        ]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    error_log('Échec insertion commande : ' . $e->getMessage());
    redirect_with_errors(
        ['Une erreur technique est survenue pendant l’enregistrement de votre commande. Merci de réessayer.'],
        $oldInput
    );
}

// -----------------------------------------------------------------------
// 5. Envoi de l'e-mail de confirmation (best-effort, ne bloque jamais)
// -----------------------------------------------------------------------
$orderRow = [
    'order_ref'           => $orderRef,
    'customer_lastname'   => $oldInput['lastname'],
    'customer_firstname'  => $oldInput['firstname'],
    'customer_email'      => $oldInput['email'],
    'city'                => $oldInput['city'],
    'neighborhood'        => $oldInput['neighborhood'],
    'delivery_date'       => date('Y-m-d', $deliveryTimestamp),
    'total_amount'        => $totalAmount,
];

$emailSent = false;
try {
    $emailSent = send_order_confirmation_email($orderRow, $orderItemsToInsert);
} catch (\Throwable $e) {
    error_log('Exception envoi email confirmation : ' . $e->getMessage());
}

if ($emailSent) {
    $update = $pdo->prepare('UPDATE orders SET email_sent = 1 WHERE id = :id');
    $update->execute(['id' => $orderId]);
}

// -----------------------------------------------------------------------
// 6. Succès : on vide le panier côté serveur (session flash) et on redirige
//    Le panier localStorage est vidé côté client sur la page de confirmation.
// -----------------------------------------------------------------------
$_SESSION['last_order_ref'] = $orderRef;
header('Location: ' . SITE_URL . '/order-confirmation.php?ref=' . urlencode($orderRef));
exit;
