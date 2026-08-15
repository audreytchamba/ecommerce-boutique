<?php
/**
 * actions/comment-create.php
 * Traitement du formulaire de commentaire.
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

csrf_verify();

$oldInput = [
    'customer_name' => clean_input($_POST['customer_name'] ?? ''),
    'comment_text'  => clean_input($_POST['comment_text'] ?? ''),
];

$errors = [];

if (empty($oldInput['customer_name']) || mb_strlen($oldInput['customer_name']) > 100) {
    $errors[] = 'Le nom est requis (100 caractères maximum).';
}
if (empty($oldInput['comment_text']) || mb_strlen($oldInput['comment_text']) > 1000) {
    $errors[] = 'Le commentaire est requis (1000 caractères maximum).';
}

if (!empty($errors)) {
    $_SESSION['comment_feedback'] = [
        'errors' => $errors,
        'old_input' => $oldInput,
    ];
    header('Location: ' . SITE_URL . '/index.php#commentaires');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO comments (customer_name, comment_text, is_approved) VALUES (:name, :text, 0)'
    );
    $stmt->execute([
        'name' => $oldInput['customer_name'],
        'text' => $oldInput['comment_text'],
    ]);
} catch (\PDOException $e) {
    error_log('Erreur insertion commentaire: ' . $e->getMessage());
    $_SESSION['comment_feedback'] = [
        'errors' => ['Une erreur technique est survenue. Merci de réessayer plus tard.'],
        'old_input' => $oldInput,
    ];
    header('Location: ' . SITE_URL . '/index.php#commentaires');
    exit;
}

$_SESSION['comment_feedback'] = [
    'success' => 'Merci pour votre commentaire ! Il sera publié après modération.'
];

header('Location: ' . SITE_URL . '/index.php#commentaires');
exit;