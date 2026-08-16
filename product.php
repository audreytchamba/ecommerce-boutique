<?php

declare(strict_types=1);

require __DIR__ . '/includes/session.php';
require __DIR__ . '/config/config.php';
require __DIR__ . '/includes/sanitize.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/functions.php';

// Récupérer l'ID du produit depuis l'URL
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($productId <= 0) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$product = get_product_by_id($productId);

if (!$product) {
    header('HTTP/1.1 404 Not Found');
    $pageTitle = 'Produit non trouvé';
    require __DIR__ . '/includes/header.php';
    echo '<section class="container mt-lg"><p class="text-center">Produit non trouvé. <a href="' . e(SITE_URL) . '/index.php">Retour au catalogue</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Récupérer les images de la galerie
$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order'
);
$stmt->execute([$productId]);
$gallery = $stmt->fetchAll();

$pageTitle = $product['name'];
$extraStylesheets = ['/assets/css/product-detail.css'];
$extraScripts = ['/assets/js/product-gallery.js'];

require __DIR__ . '/includes/header.php';
?>

<section class="container mt-lg mb-lg">
    <a href="<?= e(SITE_URL) ?>/index.php" style="color: var(--color-primary); text-decoration: none; font-size: 0.95rem;">← Retour au catalogue</a>
</section>

<section class="product-detail container">
    <div class="product-detail__gallery">
        <!-- Image principale -->
        <div class="gallery-main">
            <img id="main-image" src="<?= e(SITE_URL . $product['media_path']) ?>" alt="<?= e($product['name']) ?>" class="main-image">
        </div>

        <!-- Miniatures -->
        <?php if (!empty($gallery)): ?>
            <div class="gallery-thumbs">
                <button type="button" class="thumb-item is-active" data-src="<?= e(SITE_URL . $product['media_path']) ?>" aria-label="Image principale">
                    <img src="<?= e(SITE_URL . $product['media_path']) ?>" alt="<?= e($product['name']) ?>">
                </button>
                <?php foreach ($gallery as $image): ?>
                    <button type="button" class="thumb-item" data-src="<?= e(SITE_URL . $image['image_path']) ?>" aria-label="Image galerie">
                        <img src="<?= e(SITE_URL . $image['image_path']) ?>" alt="<?= e($product['name']) ?>">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-detail__info">
        <div class="breadcrumb">
            <a href="<?= e(SITE_URL) ?>/index.php">Accueil</a>
            <span> / </span>
            <a href="<?= e(SITE_URL) ?>/index.php?category=<?= e($product['category_slug']) ?>">
                <?= e($product['category_name']) ?>
            </a>
            <span> / </span>
            <strong><?= e($product['name']) ?></strong>
        </div>

        <h1 class="product-name"><?= e($product['name']) ?></h1>

        <div class="product-meta">
            <span class="product-category"><?= e($product['category_name']) ?></span>
            <?php if ($product['is_featured']): ?>
                <span class="badge-featured">✨ En vedette</span>
            <?php endif; ?>
        </div>

        <p class="product-price"><?= format_price((float) $product['price']) ?></p>

        <div class="product-description">
            <h3>Description</h3>
            <p><?= e($product['description']) ?></p>
        </div>

        <div class="product-quantity-selector">
            <label for="quantity">Quantité :</label>
            <div class="quantity-input">
                <button type="button" class="qty-btn qty-minus" aria-label="Réduire">−</button>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="100">
                <button type="button" class="qty-btn qty-plus" aria-label="Augmenter">+</button>
            </div>
        </div>

        <!--
            IMPORTANT : ce bouton utilise la classe "js-add-to-cart-detail" et NON
            "js-add-to-cart" (utilisée sur le catalogue index.php). Les deux classes
            sont volontairement différentes pour éviter que l'écouteur générique de
            cart.js (chargé globalement via footer.php) ne se déclenche EN PLUS de
            celui, spécifique, de product-gallery.js — ce qui provoquait un double
            ajout au panier avec un mauvais format d'appel (voir product-gallery.js).
        -->
        <button
            type="button"
            class="btn btn-primary btn-lg js-add-to-cart-detail"
            data-id="<?= (int) $product['id'] ?>"
            data-name="<?= e($product['name']) ?>"
            data-price="<?= (float) $product['price'] ?>"
            data-image="<?= e(SITE_URL . $product['media_path']) ?>"
        >
            🛒 Ajouter au panier
        </button>

        <div id="add-to-cart-message" class="add-to-cart-message" style="display: none;">
            ✅ Ajouté au panier! <a href="<?= e(SITE_URL) ?>/cart.php">Voir le panier</a>
        </div>

        <div class="product-info-sections">
            <h3>Informations pratiques</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Livraison</strong>
                    <p>Rapide et sécurisée chez vous</p>
                </div>
                <div class="info-item">
                    <strong>Paiement</strong>
                    <p>À la livraison (cash)</p>
                </div>
                <div class="info-item">
                    <strong>Garantie</strong>
                    <p>Qualité certifiée</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
