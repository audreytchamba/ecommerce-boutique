<?php
/**
 * actions/admin/auth-logout.php
 * Gère la déconnexion de l'administrateur.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/config.php';

// 1. Vider toutes les variables de session
$_SESSION = [];

// 2. Détruire le cookie de session côté client
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Détruire la session côté serveur
session_destroy();

// 4. Rediriger vers la page de connexion
header('Location: ' . SITE_URL . '/admin/login.php');
exit;