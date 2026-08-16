<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';

// Si l'admin est déjà connecté, on le redirige vers le dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$pageTitle = 'Administration - Connexion';
$extraStylesheets = ['/assets/css/admin/admin-login.css'];


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($pageTitle ?? SITE_NAME) ?> | <?= e(SITE_NAME) ?></title>
        <meta name="description" content="Administration - connexion à l'espace back-office">
        <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/variables.css">
        <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/reset.css">
        <?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
            <?php foreach ($extraStylesheets as $stylesheet): ?>
                <link rel="stylesheet" href="<?= e(SITE_URL . $stylesheet) ?>">
            <?php endforeach; ?>
        <?php endif; ?>
</head>
<body class="login-body">

<main class="login-container">
    <div class="login-card">
        <h1><?= e(SITE_NAME) ?></h1>
        <p class="login-subtitle">Accès à l'administration</p>

        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="form-error" style="margin-bottom: 1rem;">
                <?= e($_SESSION['login_error']) ?>
                <?php unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(SITE_URL) ?>/actions/admin/auth-login.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
    </div>
</main>

</body>
</html>
