<?php
/**
 * admin/category-form.php
 * Formulaire pour l'ajout et la modification des catégories.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDbConnection();

$isEditing = isset($_GET['id']);
$category = null;

if ($isEditing) {
    $categoryId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    if (!$category) {
        $_SESSION['flash_error'] = 'Catégorie non trouvée.';
        header('Location: ' . SITE_URL . '/admin/categories.php');
        exit;
    }
    $pageTitle = 'Modifier la catégorie : ' . e($category['name']);
} else {
    $pageTitle = 'Ajouter une nouvelle catégorie';
}

$oldInput = $_SESSION['category_form_old_input'] ?? null;
unset($_SESSION['category_form_old_input']);

function form_value(?array $oldInput, ?array $entity, string $key, $default = '')
{
    if ($oldInput !== null && array_key_exists($key, $oldInput)) {
        return $oldInput[$key];
    }
    if ($entity !== null && array_key_exists($key, $entity)) {
        return $entity[$key];
    }
    return $default;
}

$extraStylesheets = ['/assets/css/admin/admin-products.css'];

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<div class="admin-card" style="max-width: 800px; margin: auto;">
    <div class="admin-card-header">
        <h3><?= e($pageTitle) ?></h3>
    </div>
    <div class="admin-card-body">
        <form action="<?= e(SITE_URL) ?>/actions/admin/category_crud.php" method="POST" class="admin-form">
            <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create' ?>">
            <?php if ($isEditing): ?>
                <input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= nl2br(e($_SESSION['flash_error'])) ?>
                    <?php unset($_SESSION['flash_error']); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nom de la catégorie</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e((string) form_value($oldInput, $category, 'name')) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description (optionnel)</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= e((string) form_value($oldInput, $category, 'description')) ?></textarea>
            </div>

            <div class="form-group">
                <label for="icon">Icône (ex: `utensils`, `headphones`)</label>
                <input type="text" id="icon" name="icon" class="form-control" value="<?= e((string) form_value($oldInput, $category, 'icon')) ?>">
                <small class="form-text">Utilisé pour l'affichage. Peut être un mot-clé ou un emoji.</small>
            </div>

            <div class="form-group">
                <label for="sort_order">Ordre d'affichage</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= e((string) form_value($oldInput, $category, 'sort_order', '0')) ?>" min="0" required>
            </div>

            <div class="form-actions">
                <a href="<?= e(SITE_URL) ?>/admin/categories.php" class="btn">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEditing ? 'Mettre à jour' : 'Créer la catégorie' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/admin/admin-footer.php';
?>