<?php
/**
 * admin/order-detail.php
 * Affiche le détail complet d'une commande.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/sanitize.php';
require __DIR__ . '/../includes/functions.php';

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$orderId) {
    header('Location: ' . SITE_URL . '/admin/orders.php');
    exit;
}

$pdo = getDbConnection();

// 1. Récupérer la commande
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    // Commande non trouvée, on redirige
    header('Location: ' . SITE_URL . '/admin/orders.php');
    exit;
}

// 2. Récupérer les articles de la commande
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Détail Commande : ' . e($order['order_ref']);
$extraStylesheets = [
    '/assets/css/admin/admin-dashboard.css', // Pour les badges et la base des cartes
    '/assets/css/admin/admin-orders.css'
];

require __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="order-detail-layout">
    <!-- Colonne principale : articles commandés -->
    <div class="order-items-card admin-card">
        <div class="admin-card-header">
            <h3>Articles commandés</h3>
        </div>
        <div class="admin-card-body">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-right">P.U.</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td class="text-right"><?= format_price((float) $item['unit_price']) ?></td>
                            <td class="text-center"><?= (int) $item['quantity'] ?></td>
                            <td class="text-right"><strong><?= format_price((float) $item['subtotal']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right total-label">Total de la commande</td>
                        <td class="text-right total-amount"><?= format_price((float) $order['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Colonne latérale : infos client et commande -->
    <div class="order-info-column">
        <div class="admin-card">
            <div class="admin-card-header"><h3>Informations Client</h3></div>
            <div class="admin-card-body">
                <p><strong>Nom :</strong> <?= e($order['customer_firstname'] . ' ' . $order['customer_lastname']) ?></p>
                <p><strong>Email :</strong> <a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></p>
                <p><strong>Téléphone :</strong> <a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></p>
                <hr>
                <p><strong>Adresse de livraison :</strong></p>
                <p><?= e($order['city']) ?>, <?= e($order['neighborhood']) ?></p>
                <p><strong>Date souhaitée :</strong> <?= e(date('d/m/Y', strtotime($order['delivery_date']))) ?></p>
            </div>
        </div>

        <div class="admin-card mt-lg">
            <div class="admin-card-header"><h3>Statut de la commande</h3></div>
            <div class="admin-card-body">
                <p><strong>Statut actuel :</strong> <span class="badge status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></p>
                <!-- Ici, on pourra ajouter un formulaire pour changer le statut -->
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin/admin-footer.php'; ?>