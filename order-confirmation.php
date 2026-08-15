<?php
/**
 * order-confirmation.php
 * Affiche le récapitulatif de la commande après validation.
 * Accessible via ?ref=CMD-xxxx — on vérifie que cette référence correspond
 * bien à la dernière commande de CETTE session (évite qu'un tiers devine
 * une référence et consulte les coordonnées d'un autre client).
 */
declare(strict_types=1);

require __DIR__ . '/includes/session.php';
require __DIR__ . '/config/config.php';
require __DIR__ . '/includes/sanitize.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$ref = clean_input($_GET['ref'] ?? '');

if ($ref === '' || empty($_SESSION['last_order_ref']) || $_SESSION['last_order_ref'] !== $ref) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE order_ref = :ref LIMIT 1');
$stmt->execute(['ref' => $ref]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemsStmt->execute(['id' => $order['id']]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Commande confirmée';
require __DIR__ . '/includes/header.php';
?>

<section class="container mt-lg mb-lg text-center">
    <h1 style="color: var(--color-success, #3B7A3B);">✅ Commande confirmée</h1>
    <p>Merci <?= e($order['customer_firstname']) ?> ! Votre commande
        <strong><?= e($order['order_ref']) ?></strong> a bien été enregistrée.</p>
    <p>Un e-mail de confirmation vous a été envoyé à <?= e($order['customer_email']) ?>.</p>

    <div class="checkout-form-card text-left" style="max-width:500px; margin:2rem auto; text-align:left;">
        <h3>Récapitulatif</h3>
        <?php foreach ($items as $item): ?>
            <div class="checkout-summary__item" style="color:var(--color-text); border-color:var(--color-border);">
                <span><?= e($item['product_name']) ?> × <?= (int) $item['quantity'] ?></span>
                <span><?= format_price((float) $item['subtotal']) ?></span>
            </div>
        <?php endforeach; ?>
        <div class="checkout-summary__total" style="color:var(--color-primary);">
            <span>Total</span>
            <span><?= format_price((float) $order['total_amount']) ?></span>
        </div>

        <hr style="margin: 1rem 0; border-color: var(--color-border);">
        <p><strong>Livraison :</strong> <?= e($order['city']) ?>, <?= e($order['neighborhood']) ?></p>
        <p><strong>Date souhaitée :</strong> <?= e($order['delivery_date']) ?></p>
        <p class="badge-cod">💵 Paiement à la livraison</p>
    </div>

    <a href="<?= e(SITE_URL) ?>/index.php" class="btn btn-primary">Retour à la boutique</a>
</section>

<?php 
// Indiquer au footer que le panier doit être vidé
$clearCartOnPageLoad = true;
require __DIR__ . '/includes/footer.php'; 
?>
