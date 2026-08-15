<?php
/**
 * admin/orders.php
 * Liste chronologique de toutes les commandes.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Gestion des commandes';
// On réutilise les styles du dashboard qui contiennent déjà les styles pour les tableaux et les badges
$extraStylesheets = ['/assets/css/admin/admin-dashboard.css'];

$pdo = getDbConnection();

// Récupérer toutes les commandes, les plus récentes en premier
$stmt = $pdo->query(
    "SELECT id, order_ref, customer_firstname, customer_lastname, total_amount, created_at, status
     FROM orders
     ORDER BY created_at DESC"
);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Toutes les commandes (<?= count($orders) ?>)</h3>
    </div>
    <div class="admin-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Date</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucune commande enregistrée pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= e($order['order_ref']) ?></strong></td>
                            <td><?= e($order['customer_firstname'] . ' ' . $order['customer_lastname']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                            <td><?= format_price((float) $order['total_amount']) ?></td>
                            <td><span class="badge status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td>
                                <a href="<?= e(SITE_URL) ?>/admin/order-detail.php?id=<?= (int) $order['id'] ?>" class="btn btn-sm btn-outline">Voir le détail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin/admin-footer.php'; ?>