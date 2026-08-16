<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDbConnection();

$isEditing = isset($_GET['id']);
$product = null;
$galleryImages = [];

if ($isEditing) {
    $productId = (int)$_GET['id'];
    // Récupérer le produit
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    // Récupérer sa galerie
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
    $stmt->execute([$productId]);
    $galleryImages = $stmt->fetchAll();

    if (!$product) {
        // Si le produit n'existe pas, rediriger
        header('Location: ' . SITE_URL . '/admin/products.php');
        exit;
    }
    $pageTitle = 'Modifier le produit : ' . e($product['name']);
} else {
    $pageTitle = 'Ajouter un nouveau produit';
}

// Récupérer les catégories actives pour le select
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();


if ($isEditing && $product) {
    $hasCurrentCategory = false;
    foreach ($categories as $cat) {
        if ((int) $cat['id'] === (int) $product['category_id']) {
            $hasCurrentCategory = true;
            break;
        }
    }
    if (!$hasCurrentCategory) {
        $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
        $stmt->execute([$product['category_id']]);
        $currentCat = $stmt->fetch();
        if ($currentCat) {
            $currentCat['name'] .= ' (inactive)';
            $categories[] = $currentCat;
        }
    }
}


$oldInput = $_SESSION['product_form_old_input'] ?? null;
unset($_SESSION['product_form_old_input']);


function form_value(?array $oldInput, ?array $product, string $key, $default = '')
{
    if ($oldInput !== null && array_key_exists($key, $oldInput)) {
        return $oldInput[$key];
    }
    if ($product !== null && array_key_exists($key, $product)) {
        return $product[$key];
    }
    return $default;
}

$extraStylesheets = ['/assets/css/admin/admin-products.css'];
$extraScripts = ['/assets/js/admin/admin-media.js'];

require_once __DIR__ . '/../includes/admin/admin-header.php';
?>

<form action="<?= e(SITE_URL) ?>/actions/admin/product_crud.php" method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create' ?>">
    <?php if ($isEditing): ?>
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?= nl2br(e($_SESSION['flash_error'])) ?>
            <?php unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <!-- Colonne de gauche : infos principales -->
        <div class="form-column">
            <div class="admin-card">
                <div class="admin-card-header"><h3>Informations principales</h3></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label for="name">Nom du produit</label>
                        <input type="text" id="name" name="name" value="<?= e((string) form_value($oldInput, $product, 'name', '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="8"><?= e((string) form_value($oldInput, $product, 'description', '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="admin-card mt-lg">
                <div class="admin-card-header"><h3>Média principal (Image ou Vidéo)</h3></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label for="media_file">Fichier (JPG, PNG, WEBP, MP4, WEBM - Max 5Mo)</label>
                        <input type="file" id="media_file" name="media_file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
                        <small>Ce média sera affiché sur la page catalogue.</small>
                    </div>
                    <div id="media-preview"></div>
                    <?php if ($isEditing && $product['media_path']): ?>
                        <div id="existing-media-info">
                            <p>Média actuel :</p>
                            <?php if ($product['media_type'] === 'video'): ?>
                                <video src="<?= e(SITE_URL . $product['media_path']) ?>" controls class="existing-media-preview"></video>
                            <?php else: ?>
                                <img src="<?= e(SITE_URL . $product['media_path']) ?>" alt="Média actuel" class="existing-media-preview">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-card mt-lg">
                <div class="admin-card-header"><h3>Galerie d'images</h3></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label for="gallery_files">Ajouter des images (JPG, PNG, WEBP)</label>
                        <input type="file" id="gallery_files" name="gallery_files[]" multiple accept="image/jpeg,image/png,image/webp">
                    </div>
                    <!-- Prévisualisation des nouvelles images -->
                    <div id="gallery-preview-new" class="gallery-preview-grid"></div>

                    <!-- Affichage de la galerie existante -->
                    <?php if ($isEditing && !empty($galleryImages)): ?>
                        <hr>
                        <p>Galerie actuelle (cochez pour supprimer) :</p>
                        <div class="gallery-preview-grid">
                            <?php foreach ($galleryImages as $image): ?>
                                <div class="gallery-preview-item existing">
                                    <img src="<?= e(SITE_URL . $image['image_path']) ?>" alt="Image de la galerie">
                                    <label class="delete-checkbox">
                                        <input type="checkbox" name="delete_gallery_images[]" value="<?= (int)$image['id'] ?>">
                                        Supprimer
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne de droite : prix, stock, catégorie -->
        <div class="form-column">
            <div class="admin-card">
                <div class="admin-card-header"><h3>Détails</h3></div>
                <div class="admin-card-body">
                    <div class="form-group">
                        <label for="price">Prix (<?= e(CURRENCY_SYMBOL) ?>)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?= e((string) form_value($oldInput, $product, 'price', '0.00')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Catégorie</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Choisir une catégorie --</option>
                            <?php
                            $selectedCategoryId = form_value($oldInput, $product, 'category_id', null);
                            ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)$category['id'] ?>" <?= ($selectedCategoryId !== null && (int) $selectedCategoryId === (int) $category['id']) ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock">Quantité en stock</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?= e((string) form_value($oldInput, $product, 'stock', '0')) ?>" required>
                    </div>
                </div>
            </div>

            <div class="admin-card mt-lg">
                <div class="admin-card-header"><h3>Publication</h3></div>
                <div class="admin-card-body">
                    <?php
                    $isActiveVal = form_value($oldInput, $product, 'is_active', 1);
                    $isFeaturedVal = form_value($oldInput, $product, 'is_featured', 0);
                    ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1" <?= $isActiveVal ? 'checked' : '' ?>>
                            Rendre le produit visible sur le site
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_featured" value="1" <?= $isFeaturedVal ? 'checked' : '' ?>>
                            Mettre en avant sur la page d'accueil
                        </label>
                    </div>
                    <div class="form-actions">
                        <a href="<?= e(SITE_URL) ?>/admin/products.php" class="btn">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <?= $isEditing ? 'Mettre à jour' : 'Enregistrer le produit' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
require_once __DIR__ . '/../includes/admin/admin-footer.php';
?>
