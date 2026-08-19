<?php
/**
 * admin/categories.php
 * Gère l'affichage et la création des catégories de produits.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Gestion des catégories';
// On peut réutiliser les styles de la page produits
$extraStylesheets = ['/assets/css/admin/admin-products.css'];
$extraScripts = ['/assets/js/admin/admin-products.js'];
$pdo = getDbConnection();

// Récupérer toutes les catégories pour les lister
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h3>Toutes les catégories (<?= count($categories) ?>)</h3>
        <a href="<?= e(SITE_URL) ?>/admin/category-form.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Ajouter une catégorie
        </a>
    </div>
    <div class="admin-card-body">
        <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-success" role="alert">
                    <?= e($_SESSION['flash_message']) ?>
                    <?php unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($_SESSION['flash_error']) ?>
                    <?php unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Slug</th>
                    <th>Icône</th>
                    <th class="text-center">Ordre</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucune catégorie n'a été créée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?= e($category['name']) ?></strong></td>
                            <td><?= e($category['slug']) ?></td>
                            <td><?= e($category['icon']) ?></td>
                            <td class="text-center"><?= (int) $category['sort_order'] ?></td>
                            <td class="table-actions">
                                <a href="<?= e(SITE_URL) ?>/admin/category-form.php?id=<?= (int) $category['id'] ?>" class="btn btn-sm btn-outline" title="Modifier">Modifier</a>
                                <form action="<?= e(SITE_URL) ?>/actions/admin/category_crud.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger js-confirm-delete" data-item-name="<?= e($category['name']) ?>" title="Supprimer">Supprimer</button>
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