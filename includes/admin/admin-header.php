<?php
/**
 * includes/admin/admin-header.php
 * Header commun pour toutes les pages du back-office.
 */
declare(strict_types=1);

// Assure les dépendances minimales si la page appelante ne les a pas incluses
if (!function_exists('e')) {
    require_once __DIR__ . '/../sanitize.php';
}
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

// La page qui inclut ce header doit définir $pageTitle et optionnellement $extraStylesheets
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Administration') ?> | <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/variables.css">
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/reset.css">
    <link rel="stylesheet" href="<?= e(SITE_URL) ?>/assets/css/admin/admin-layout.css">
    <?php if (isset($extraStylesheets) && is_array($extraStylesheets)): ?>
        <?php foreach ($extraStylesheets as $stylesheet): ?>
            <link rel="stylesheet" href="<?= e(SITE_URL . '/' . ltrim($stylesheet, '/')) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-layout">
        <?php require_once __DIR__ . '/admin-sidebar.php'; ?>
        <main class="admin-main">
            <header class="admin-header">
                <h1><?= e($pageTitle ?? 'Tableau de bord') ?></h1>
                <div class="admin-user-menu">
                    <span><?= e($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="<?= e(SITE_URL) ?>/actions/admin/auth-logout.php" title="Déconnexion"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></a>
                </div>
            </header>
            <div class="admin-content">