<?php

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


function redirect_with_errors(array $errors, array $oldInput): void
{
    $_SESSION['checkout_errors']    = $errors;
    $_SESSION['checkout_old_input'] = $oldInput;
    header('Location: ' . SITE_URL . '/checkout.php');
    exit;
}


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
$stockErrors = [];

foreach ($dbProducts as $product) {
    $quantity = $requestedItems[(int) $product['id']];

    if ($quantity > (int) $product['stock']) {
        $stockErrors[] = sprintf(
            'Stock insuffisant pour "%s" (disponible : %d).',
            $product['name'],
            (int) $product['stock']
        );
        continue;
    }

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

if (!empty($stockErrors)) {
    redirect_with_errors($stockErrors, $oldInput);
}


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

    
    $updateStockAndSales = $pdo->prepare(
        'UPDATE products
         SET stock = stock - :qty, sales_count = sales_count + :qty
         WHERE id = :id AND stock >= :qty'
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

        $updateStockAndSales->execute([
            'qty' => $line['quantity'],
            'id'  => $line['product_id'],
        ]);

        
        if ($updateStockAndSales->rowCount() === 0) {
            throw new RuntimeException(
                'Stock épuisé entre-temps pour le produit #' . $line['product_id']
            );
        }
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


$_SESSION['last_order_ref'] = $orderRef;
header('Location: ' . SITE_URL . '/order-confirmation.php?ref=' . urlencode($orderRef));
exit;
