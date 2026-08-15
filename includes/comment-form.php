<?php

declare(strict_types=1);


$feedback = $_SESSION['comment_feedback'] ?? null;
unset($_SESSION['comment_feedback']);

$oldInput = $feedback['old_input'] ?? [];
?>

<?php if (isset($feedback)): ?>
    <div class="modal-box" style="border-top-color: var(--color-<?= isset($feedback['success']) ? 'success' : 'danger' ?>); margin-bottom: var(--space-md); max-width:none;">
        <?php if (isset($feedback['success'])): ?>
            <strong style="color:var(--color-success);"><?= e($feedback['success']) ?></strong>
        <?php else: ?>
            <strong style="color:var(--color-danger);">Merci de corriger les points suivants :</strong>
            <ul>
                <?php foreach ($feedback['errors'] as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form action="<?= e(SITE_URL) ?>/actions/comment-create.php" method="POST" style="max-width: 600px; margin: 2rem auto;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="form-group">
        <label for="customer_name">Votre nom *</label>
        <input type="text" id="customer_name" name="customer_name" required maxlength="100" value="<?= e($oldInput['customer_name'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="comment_text">Votre commentaire *</label>
        <textarea id="comment_text" name="comment_text" rows="5" required maxlength="1000"><?= e($oldInput['comment_text'] ?? '') ?></textarea>
        <small>Votre commentaire sera soumis à modération avant d'être publié.</small>
    </div>

    <div class="form-actions" style="justify-content: center;">
        <button type="submit" class="btn btn-primary">Envoyer mon commentaire</button>
    </div>
</form>