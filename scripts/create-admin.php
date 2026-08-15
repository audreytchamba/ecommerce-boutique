<?php
/**
 * scripts/create-admin.php
 * Script CLI (Command-Line Interface) pour créer le premier compte admin.
 * NE JAMAIS exposer ce script sur un serveur web.
 *
 * USAGE (dans un terminal, à la racine du projet) :
 * > php scripts/create-admin.php
 */
declare(strict_types=1);

// Empêche l'exécution via un navigateur web
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../config/db.php';

echo "--- Création d'un compte administrateur ---\n";

$username = readline("Nom d'utilisateur : ");
$email = readline("Adresse e-mail : ");
$password = readline("Mot de passe : ");

// Validation basique
if (empty($username) || empty($email) || empty($password)) {
    echo "\n[ERREUR] Tous les champs sont requis.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "\n[ERREUR] L'adresse e-mail est invalide.\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "\n[ERREUR] Le mot de passe doit faire au moins 8 caractères.\n";
    exit(1);
}

// Hachage sécurisé du mot de passe
$passwordHash = password_hash($password, PASSWORD_ARGON2ID);

if ($passwordHash === false) {
    echo "\n[ERREUR] Échec du hachage du mot de passe.\n";
    exit(1);
}

try {
    $pdo = getDbConnection();

    // Vérifier si l'utilisateur ou l'email existe déjà
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :username OR email = :email');
    $stmt->execute(['username' => $username, 'email' => $email]);
    if ($stmt->fetch()) {
        echo "\n[ERREUR] Un administrateur avec ce nom d'utilisateur ou cet e-mail existe déjà.\n";
        exit(1);
    }

    // Insertion
    $stmt = $pdo->prepare(
        'INSERT INTO admins (username, email, password_hash, is_active) VALUES (:username, :email, :password_hash, 1)'
    );
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);

    echo "\n[SUCCÈS] L'administrateur '$username' a été créé avec succès.\n";
    echo "Vous pouvez maintenant vous connecter sur /admin/login.php\n";

} catch (PDOException $e) {
    echo "\n[ERREUR] Échec de l'opération de base de données : " . $e->getMessage() . "\n";
    exit(1);
}