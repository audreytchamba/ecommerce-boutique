<?php

declare(strict_types=1);
?>
<header class="site-navbar">
    <div class="navbar-inner container">
        <a href="<?= e(SITE_URL) ?>/index.php" class="navbar-brand">
                <img src="<?= e(SITE_URL) ?>/assets/images/logo.jpeg" alt="<?= e(SITE_NAME) ?>" class="navbar-logo">
                <span class="navbar-brand-text"><?= e(SITE_NAME) ?></span>
        </a>

        <nav class="navbar-menu" id="navbar-menu" aria-label="Menu principal">
            <ul>
                <li><a href="<?= e(SITE_URL) ?>/index.php">Accueil</a></li>
                <li><a href="<?= e(SITE_URL) ?>/index.php#services">Services</a></li>
                <li><a href="<?= e(SITE_URL) ?>/index.php#galeries">Galeries</a></li>
                <li><a href="<?= e(SITE_URL) ?>/index.php#commentaires">Commentaire</a></li>
                <li><a href="<?= e(SITE_URL) ?>/index.php#a-propos">À propos</a></li>
            </ul>
        </nav>

        <div class="navbar-actions">
            <a href="<?= e(SITE_URL) ?>/cart.php" class="navbar-cart" aria-label="Voir le panier">
                🛒 <span id="cart-badge" class="cart-badge"></span>
            </a>
            <button type="button" class="navbar-toggle" id="navbar-toggle" aria-controls="navbar-menu" aria-expanded="false" aria-label="Ouvrir le menu">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
    </div>
</header>
