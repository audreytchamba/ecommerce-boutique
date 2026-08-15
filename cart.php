<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Mon panier';
$extraScripts = ['/assets/js/cart-page.js'];
require_once __DIR__ . '/includes/header.php';
?>

<section class="container mt-lg mb-lg">
    <h1 class="mb-lg">Mon panier</h1>

    <div id="cart-empty" style="display:none; margin-bottom:1rem;">
        Votre panier est vide. <a href="<?= e(SITE_URL) ?>/index.php">Retourner au catalogue</a>.
    </div>

    <div id="cart-list" class="cart-list"></div>

    <div id="cart-summary" style="margin-top:1.2rem; display:flex; gap:1rem; align-items:center;">
        <div style="flex:1">
            <a href="<?= e(SITE_URL) ?>/index.php" class="btn">Continuer mes achats</a>
        </div>
        <div style="text-align:right">
            <div style="margin-bottom:.6rem">Total : <strong id="cart-total">0 <?= e(CURRENCY_SYMBOL) ?></strong></div>
            <a href="<?= e(SITE_URL) ?>/checkout.php" id="btn-checkout" class="btn btn-primary">Passer à la commande</a>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>