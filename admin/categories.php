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
$pdo = getDbConnection();

// Récupérer toutes les catégories pour les lister
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="admin-grid">
    <!-- Colonne de gauche : Liste des catégories -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Catégories existantes (<?= count($categories) ?>)</h3>
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
                                    <!-- Les actions d'édition/suppression pourront être ajoutées ici -->
                                    <span class="text-muted">N/A</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Colonne de droite : Formulaire d'ajout -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Ajouter une catégorie</h3>
        </div>
        <div class="admin-card-body">
            <form action="<?= e(SITE_URL) ?>/actions/admin/category_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-group">
                    <label for="name">Nom de la catégorie</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="description">Description (optionnel)</label>
                    <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="icon">Icône (ex: `utensils`, `headphones`)</label>
                    <input type="text" id="icon" name="icon" class="form-control">
                    <small class="form-text">Utilisé pour l'affichage. Peut être un mot-clé ou un emoji.</small>
                </div>

                <div class="form-group">
                    <label for="sort_order">Ordre d'affichage</label>
                    <input type="number" id="sort_order" name="sort_order" class="form-control" value="0" min="0" required>
                </div>

                <button type="submit" class="btn btn-primary">Créer la catégorie</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin/admin-footer.php'; ?>