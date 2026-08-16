<?php

declare(strict_types=1);
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.6rem;">
            <div>
                <h4 style="color:var(--color-secondary);">
                    <?= e(SITE_NAME) ?>
                </h4>
                <p>Produits sélectionnés avec soin. Paiement uniquement à la livraison.</p>
                <span class="badge-cod"> Paiement à la livraison</span>
            </div>

            <div>
                <h4 style="color:var(--color-secondary);">Contact</h4>
                <p> Votre ville, Cameroun</p>
                <p>📞 <a href="tel:+237600000000">+237 6 00 00 00 00</a></p>
                <p>
                    <a href="https://wa.me/237600000000" target="_blank" rel="noopener">
                        💬 Nous écrire sur WhatsApp
                    </a>
                </p>
            </div>

            <div>
                <h4 style="color:var(--color-secondary);">Suivez-nous</h4>
                <p><a href="#" rel="noopener">Instagram</a></p>
                <p><a href="#" rel="noopener">Facebook</a></p>
            </div>
        </div>

        <hr style="border-color: rgba(255,255,255,0.1); margin: 1.6rem 0;">

        <p style="font-size:0.8rem; opacity:0.7;">
            © <?= date('Y') ?> <?= e(SITE_NAME) ?> — Tous droits réservés.
            Mentions légales · CGV
        </p>
    </div>
</footer>

<script src="<?= e(SITE_URL) ?>/assets/js/cart.js"></script>
<script src="<?= e(SITE_URL) ?>/assets/js/navbar-mobile.js"></script>

<?php if (!empty($clearCartOnPageLoad) && $clearCartOnPageLoad === true): ?>
    
    <div id="clear-cart-trigger" hidden aria-hidden="true"></div>
    <script src="<?= e(SITE_URL) ?>/assets/js/clear-cart-on-load.js"></script>
<?php endif; ?>

<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e(SITE_URL . $script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
