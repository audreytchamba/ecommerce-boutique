<?php
/**
 * actions/admin/category_crud.php
 * Point d'entrée pour la création, mise à jour et suppression des catégories.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit;
}

csrf_verify();

$action = $_POST['action'] ?? '';
$pdo = getDbConnection();

switch ($action) {
    case 'create':
        $name = clean_input($_POST['name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $icon = clean_input($_POST['icon'] ?? '');
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);

        if (empty($name)) {
            $_SESSION['flash_error'] = 'Le nom de la catégorie est requis.';
            header('Location: ' . SITE_URL . '/admin/categories.php');
            exit;
        }
        if ($sort_order === false || $sort_order < 0) {
            $sort_order = 0;
        }

        $slug = generate_slug($name);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name, slug, description, icon, sort_order, is_active)
                 VALUES (:name, :slug, :description, :icon, :sort_order, 1)"
            );
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'icon' => $icon,
                'sort_order' => $sort_order,
            ]);
            $_SESSION['flash_message'] = 'La catégorie "' . e($name) . '" a été créée avec succès.';
        } catch (PDOException $e) {
            error_log("Category Create Error: " . $e->getMessage());
            if ($e->errorInfo[1] == 1062) { // Erreur "Duplicate entry"
                $_SESSION['flash_error'] = 'Une catégorie avec ce nom ou ce slug existe déjà.';
            } else {
                $_SESSION['flash_error'] = 'Une erreur de base de données est survenue.';
            }
        }
        break;

    default:
        $_SESSION['flash_error'] = 'Action non reconnue.';
        break;
}

header('Location: ' . SITE_URL . '/admin/categories.php');
exit;