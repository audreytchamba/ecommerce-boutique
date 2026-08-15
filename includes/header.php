<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
    <meta name="description" content="Boutique en ligne — paiement à la livraison.">

    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/main.css">
    <?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
        <?php foreach ($extraStylesheets as $sheet): ?>
            <link rel="stylesheet" href="<?= e(SITE_URL . $sheet) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="<?= e(SITE_URL) ?>/assets/js/loader.js"></script>
</head>
<body>
<!-- Loader -->
<div id="loader" class="loader">
    <div class="spinner"></div>
    <p>Chargement...</p>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>
<main>
