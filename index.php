<?php
/**
 * index.php
 * Page d'accueil : catalogue complet, filtrable par catégorie en JS
 * (data-category sur chaque carte, filtrage via product-filter.js).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$categories = get_categories();
$products   = get_products(); // tous les produits actifs

$pageTitle = 'Accueil';
$extraStylesheets = ['/assets/css/product-card.css'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero" style="background:var(--color-primary-dark); color:var(--color-text-on-dark); padding-block:4rem;">
    <div class="container text-center">
        <h1 style="color:var(--color-secondary);">Bienvenue chez <?= e(SITE_NAME) ?></h1>
        <p style="max-width:560px; margin-inline:auto;">
            Cake & Aperro, musique, beauté & amp; parfums, vins — une sélection
            raffinée, livrée chez vous. <strong>Paiement à la livraison.</strong>
        </p>
    </div>
</section>

<section id="galeries" class="container mt-lg">
    <h2 class="text-center mb-lg">Notre catalogue</h2>

    <div class="category-filters" id="category-filters">
        <button type="button" class="is-active" data-filter="all">Tout voir</button>
        <?php foreach ($categories as $cat): ?>
            <button type="button" data-filter="<?= e($cat['slug']) ?>">
                <?= e($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <p class="text-center">Aucun produit disponible pour le moment.</p>
    <?php else: ?>
        <div class="product-grid" id="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card" data-category="<?= e($product['category_slug']) ?>">
                    <a href="<?= e(SITE_URL) ?>/product.php?id=<?= (int) $product['id'] ?>" class="product-card__media">
                        <?php if ($product['media_type'] === 'video'): ?>
                            <video src="<?= e(SITE_URL . $product['media_path']) ?>" muted playsinline preload="metadata"></video>
                        <?php else: ?>
                            <img src="<?= e(SITE_URL . $product['media_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                        <?php endif; ?>
                    </a>
                    <div class="product-card__body">
                        <span class="product-card__category"><?= e($product['category_name']) ?></span>
                        <a href="<?= e(SITE_URL) ?>/product.php?id=<?= (int) $product['id'] ?>" class="product-card__name">
                            <?= e($product['name']) ?>
                        </a>
                        <span class="product-card__price"><?= format_price((float) $product['price']) ?></span>
                        <button
                            type="button"
                            class="btn btn-primary btn-block js-add-to-cart"
                            data-id="<?= (int) $product['id'] ?>"
                            data-name="<?= e($product['name']) ?>"
                            data-price="<?= (float) $product['price'] ?>"
                            data-image="<?= e(SITE_URL . $product['media_path']) ?>"
                        >
                            Ajouter au panier
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section id="services" class="container mt-lg mb-lg">
    <div class="text-center" style="max-width: 650px; margin-inline: auto;">
        <h2 class="mb-lg">Nos Services</h2>
        <p>
            Nous offrons un service de livraison fiable et rapide pour toutes vos commandes.
            Chaque produit est emballé avec le plus grand soin pour garantir sa fraîcheur et sa qualité à l'arrivée.
            Le paiement se fait en toute simplicité et sécurité, directement à la livraison.
        </p>
    </div>
</section>

<section id="a-propos" class="container mt-lg mb-lg" style="background: var(--color-bg-alt); padding-block: var(--space-xl); border-radius: var(--radius-lg);">
    <div class="text-center" style="max-width: 650px; margin-inline: auto;">
        <h2 class="mb-lg">À Propos de Nous</h2>
        <p>Passionnés par les produits de qualité, nous sélectionnons pour vous le meilleur de l'épicerie fine, de la beauté et des vins. Notre mission est de vous offrir une expérience d'achat unique, alliant luxe, commodité et confiance.</p>
    </div>
</section>

<section id="commentaires" class="container mt-lg mb-lg">
    <h2 class="text-center mb-lg">Laissez-nous un mot</h2>

    <?php
    // On inclut le formulaire de commentaire.
    // Les dépendances (session, csrf, sanitize) sont déjà chargées en haut de index.php
    require_once __DIR__ . '/includes/comment-form.php';
    ?>

    <h3 class="text-center mt-lg mb-lg">Ce que nos clients pensent de nous</h3>

    <?php
    
    echo '<p class="text-center text-muted">Les témoignages apparaîtront ici une fois validés.</p>';
    ?>
</section>

<?php
$extraScripts = ['/assets/js/product-filter.js'];
require_once __DIR__ . '/includes/footer.php';
?>
