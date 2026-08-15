<?php
/**
 * checkout.php
 * Formulaire de commande. Le panier est stocké côté client (localStorage),
 * donc l'affichage du récapitulatif et le remplissage du champ caché
 * "cart_items_json" sont faits en JavaScript par checkout.js — cette page
 * ne connaît le contenu du panier qu'au moment du submit.
 *
 * La validation SERVEUR définitive est faite dans actions/process_order.php ;
 * ce formulaire fait uniquement de la validation front (UX), jamais de
 * confiance côté serveur.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Finaliser ma commande';
$extraStylesheets = ['/assets/css/checkout.css'];
require_once __DIR__ . '/includes/header.php';

// Récupère une erreur éventuelle transmise par process_order.php (redirection en cas d'échec)
$errors = [];
if (!empty($_SESSION['checkout_errors'])) {
    $errors = $_SESSION['checkout_errors'];
    unset($_SESSION['checkout_errors']);
}
$oldInput = $_SESSION['checkout_old_input'] ?? [];
unset($_SESSION['checkout_old_input']);
?>

<section class="container mt-lg mb-lg">
    <h1 class="mb-lg">Finaliser ma commande</h1>

    <div id="checkout-empty-warning" class="form-error" style="display:none; margin-bottom:1rem;">
        Votre panier est vide. <a href="<?= e(SITE_URL) ?>/index.php">Retourner au catalogue</a>.
    </div>

    <?php if (!empty($errors)): ?>
        <div class="modal-box" style="border-top-color: var(--color-danger); margin-bottom: var(--space-md); max-width:none;">
            <strong style="color:var(--color-danger);">Merci de corriger les points suivants :</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <div class="checkout-form-card">
            <form action="<?= e(SITE_URL) ?>/actions/process_order.php" method="POST" id="checkout-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="cart_items_json" id="cart_items_json" value="">

                <div class="form-row">
                    <div class="form-group">
                        <label for="lastname">Nom *</label>
                        <input type="text" id="lastname" name="lastname" required maxlength="100"
                               value="<?= e($oldInput['lastname'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="firstname">Prénom *</label>
                        <input type="text" id="firstname" name="firstname" required maxlength="100"
                               value="<?= e($oldInput['firstname'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" required maxlength="150"
                               value="<?= e($oldInput['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone *</label>
                        <input type="tel" id="phone" name="phone" required maxlength="30"
                               value="<?= e($oldInput['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Ville *</label>
                        <input type="text" id="city" name="city" required maxlength="100"
                               value="<?= e($oldInput['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="neighborhood">Quartier *</label>
                        <input type="text" id="neighborhood" name="neighborhood" required maxlength="100"
                               value="<?= e($oldInput['neighborhood'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="delivery_date">Date de livraison souhaitée *</label>
                    <input type="date" id="delivery_date" name="delivery_date" required
                           min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                           value="<?= e($oldInput['delivery_date'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="notes">Notes (optionnel)</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"><?= e($oldInput['notes'] ?? '') ?></textarea>
                </div>

                <p style="margin-bottom:1rem;">
                    <span class="badge-cod">💵 Paiement uniquement à la livraison</span>
                </p>

                <button type="submit" class="btn btn-primary btn-block" id="checkout-submit-btn">
                    Valider ma commande
                </button>
            </form>
        </div>

        <aside class="checkout-summary" id="checkout-summary">
            <h3 style="color:var(--color-secondary);">Récapitulatif</h3>
            <div id="checkout-summary-items">
                <!-- rempli dynamiquement par checkout.js -->
            </div>
            <div class="checkout-summary__total">
                <span>Total</span>
                <span id="checkout-summary-total">0 <?= e(CURRENCY_SYMBOL) ?></span>
            </div>
        </aside>
    </div>
</section>

<?php
$extraScripts = ['/assets/js/checkout.js'];
require_once __DIR__ . '/includes/footer.php';
?>
