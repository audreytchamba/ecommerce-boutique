<?php
/**
 * actions/admin/auth-login.php
 * Traitement du formulaire de connexion admin.
 * Sécurité : CSRF, anti-bruteforce, password_verify, session_regenerate_id.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

csrf_verify();

function login_fail(string $message): never
{
    $_SESSION['login_error'] = $message;
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

$username = clean_input($_POST['username'] ?? '');
$password = $_POST['password'] ?? ''; // Ne pas "clean" le mot de passe

if (empty($username) || empty($password)) {
    login_fail('Nom d\'utilisateur et mot de passe sont requis.');
}

$pdo = getDbConnection();

// 1. Récupérer l'admin par son nom d'utilisateur
$stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
$stmt->execute(['username' => $username]);
$admin = $stmt->fetch();

if (!$admin) {
    login_fail('Identifiants incorrects.');
}

// 2. Vérifier si le compte est verrouillé (anti-bruteforce)
if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
    login_fail('Compte temporairement verrouillé. Réessayez plus tard.');
}

// 3. Vérifier si le compte est actif
if (!$admin['is_active']) {
    login_fail('Ce compte administrateur est désactivé.');
}

// 4. Vérifier le mot de passe
if (!password_verify($password, $admin['password_hash'])) {
    // Échec de l'authentification : incrémenter le compteur de tentatives
    $attempts = (int) $admin['failed_attempts'] + 1;
    $params = ['attempts' => $attempts, 'id' => $admin['id']];

    if ($attempts >= LOGIN_MAX_ATTEMPTS) {
        // Verrouiller le compte
        $lock_duration = LOGIN_LOCK_MINUTES . ' minutes';
        $stmt = $pdo->prepare(
            'UPDATE admins SET failed_attempts = :attempts, locked_until = DATE_ADD(NOW(), INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE) WHERE id = :id'
        );
        error_log("Admin account locked for user: {$username}");
    } else {
        // Juste incrémenter
        $stmt = $pdo->prepare('UPDATE admins SET failed_attempts = :attempts WHERE id = :id');
    }
    $stmt->execute($params);

    login_fail('Identifiants incorrects.');
}

// 5. Succès de l'authentification

// Réinitialiser le compteur de tentatives
if ($admin['failed_attempts'] > 0) {
    $stmt = $pdo->prepare('UPDATE admins SET failed_attempts = 0, locked_until = NULL WHERE id = :id');
    $stmt->execute(['id' => $admin['id']]);
}

// Mettre à jour la date de dernière connexion
$stmt = $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
$stmt->execute(['id' => $admin['id']]);

// Régénérer l'ID de session pour prévenir la fixation de session
session_regenerate_id(true);

// Stocker les informations de l'admin en session
$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_username'] = $admin['username'];

// Redirection vers le tableau de bord
header('Location: ' . SITE_URL . '/admin/');
exit;
