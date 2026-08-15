<?php
/**
 * admin/index.php
 * Page principale du back-office (Tableau de bord).
 * Redirige vers login.php si l'admin n'est pas connecté.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php'; // Le garde d'authentification
require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/sanitize.php';
require __DIR__ . '/../includes/functions.php'; // Pour format_price

$pageTitle = 'Tableau de bord';
$extraStylesheets = ['/assets/css/admin/admin-dashboard.css'];

// --- Récupération des données pour le dashboard ---
$pdo = getDbConnection();

// 1. Chiffre d'affaires du mois en cours (commandes non annulées)
$stmt = $pdo->prepare(
    "SELECT SUM(total_amount) AS monthly_revenue
     FROM orders
     WHERE status != 'cancelled'
     AND MONTH(created_at) = MONTH(CURRENT_DATE())
     AND YEAR(created_at) = YEAR(CURRENT_DATE())"
);
$stmt->execute();
$monthlyRevenue = $stmt->fetchColumn();

// 2. Produit le plus vendu (basé sur sales_count)
$stmt = $pdo->query(
    "SELECT name, sales_count
     FROM products
     ORDER BY sales_count DESC
     LIMIT 1"
);
$bestSeller = $stmt->fetch();

// 3. Nombre de commandes en attente
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$pendingOrdersCount = $stmt->fetchColumn();

// 4. 5 dernières commandes
$stmt = $pdo->query(
    "SELECT id, order_ref, customer_firstname, customer_lastname, total_amount, created_at, status
     FROM orders
     ORDER BY created_at DESC
     LIMIT 5"
);
$lastOrders = $stmt->fetchAll();

require __DIR__ . '/../includes/admin/admin-header.php';
?>

<!-- Cartes de statistiques -->
<div class="stat-cards-grid">
    <div class="stat-card">
        <div class="stat-card__icon" style="background-color: rgba(212, 175, 55, 0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
        </div>
        <div class="stat-card__info">
            <span class="stat-card__title">Chiffre d'affaires (mois)</span>
            <span class="stat-card__value"><?= format_price((float) $monthlyRevenue) ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__icon" style="background-color: rgba(232, 212, 138, 0.15);">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-secondary-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
        </div>
        <div class="stat-card__info">
            <span class="stat-card__title">Produit phare</span>
            <span class="stat-card__value"><?= $bestSeller ? e($bestSeller['name']) : 'N/A' ?></span>
            <small><?= $bestSeller ? e((string)$bestSeller['sales_count']) . ' ventes' : '' ?></small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__icon" style="background-color: rgba(122, 30, 44, 0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-card__info">
            <span class="stat-card__title">Commandes en attente</span>
            <span class="stat-card__value"><?= e((string)$pendingOrdersCount) ?></span>
        </div>
    </div>
</div>

<!-- Tableau des dernières commandes -->
<div class="admin-card mt-lg">
    <div class="admin-card-header">
        <h3>Dernières commandes</h3>
        <a href="/admin/orders.php" class="btn btn-sm">Voir tout</a>
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
                <?php if (empty($lastOrders)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucune commande pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lastOrders as $order): ?>
                        <tr>
                            <td><strong><?= e($order['order_ref']) ?></strong></td>
                            <td><?= e($order['customer_firstname'] . ' ' . $order['customer_lastname']) ?></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
                            <td><?= format_price((float) $order['total_amount']) ?></td>
                            <td><span class="badge status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td>
                                <a href="<?= e(SITE_URL) ?>/admin/order-detail.php?id=<?= (int) $order['id'] ?>" class="btn btn-sm btn-outline">Détails</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin/admin-footer.php'; ?>