<?php
/**
 * admin/products.php
 * Liste des produits avec options de filtrage et actions CRUD.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Gestion des produits';
$extraStylesheets = ['/assets/css/admin/admin-products.css'];
$extraScripts = ['/assets/js/admin/admin-products.js'];

$pdo = getDbConnection();

// Récupérer tous les produits avec leur catégorie
$stmt = $pdo->query(
    "SELECT p.*, c.name as category_name
     FROM products p
     JOIN categories c ON p.category_id = c.id
     ORDER BY p.created_at DESC"
);
$products = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Tous les produits (<?= count($products) ?>)</h3>
        <a href="<?= e(SITE_URL) ?>/admin/product-form.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Ajouter un produit
        </a>
    </div>
    <div class="admin-card-body">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?= e($_SESSION['flash_message']) ?>
                <?php unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Média</th>
                    <th>Nom du produit</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucun produit n'a été créé pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?= e(SITE_URL . $product['media_path']) ?>" alt="<?= e($product['name']) ?>" class="table-media-preview">
                            </td>
                            <td><strong><?= e($product['name']) ?></strong></td>
                            <td><?= e($product['category_name']) ?></td>
                            <td><?= format_price((float) $product['price']) ?></td>
                            <td>
                                <span class="badge <?= $product['is_active'] ? 'status-completed' : 'status-cancelled' ?>">
                                    <?= $product['is_active'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <a href="<?= e(SITE_URL) ?>/admin/product-form.php?id=<?= (int) $product['id'] ?>" class="btn btn-sm btn-outline" title="Modifier">Modifier</a>
                                <form action="<?= e(SITE_URL) ?>/actions/admin/product_crud.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger js-confirm-delete" data-product-name="<?= e($product['name']) ?>" title="Supprimer">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin/admin-footer.php'; ?>