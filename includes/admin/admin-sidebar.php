<?php
/**
 * includes/admin/admin-sidebar.php
 * Menu latéral de navigation pour le back-office.
 * Gère l'état actif du lien courant.
 */
declare(strict_types=1);

// Détermine la page active pour le style du menu
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$activePages = [
    'index.php'      => ['index.php'],
    'orders.php'     => ['orders.php', 'order-detail.php'],
    'products.php'   => ['products.php', 'product-form.php'],
    'categories.php' => ['categories.php'],
];

function is_active(string $menuItem, string $currentPage, array $activePages): bool
{
    return in_array($currentPage, $activePages[$menuItem] ?? [], true);
}
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="<?= e(SITE_URL) ?>/admin/" class="sidebar-logo"><?= e(SITE_NAME) ?></a>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li class="<?= is_active('index.php', $currentPage, $activePages) ? 'is-active' : '' ?>">
                <a href="<?= e(SITE_URL) ?>/admin/">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Tableau de bord</span>
                </a>
            </li>
            <li class="<?= is_active('orders.php', $currentPage, $activePages) ? 'is-active' : '' ?>">
                <a href="<?= e(SITE_URL) ?>/admin/orders.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                    <span>Commandes</span>
                </a>
            </li>
            <li class="<?= is_active('products.php', $currentPage, $activePages) ? 'is-active' : '' ?>">
                <a href="<?= e(SITE_URL) ?>/admin/products.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    <span>Produits</span>
                </a>
            </li>
            <li class="<?= is_active('categories.php', $currentPage, $activePages) ? 'is-active' : '' ?>">
                <a href="<?= e(SITE_URL) ?>/admin/categories.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>Catégories</span>
                </a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= e(SITE_URL) ?>/" target="_blank" rel="noopener">Voir le site public</a>
    </div>
</aside>