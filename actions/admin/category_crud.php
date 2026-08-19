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

/**
 * Redirects with an error message and optionally preserves old input.
 *
 * @param string $message The error message to display.
 * @param int|null $categoryId The ID of the category being edited, if applicable.
 * @param array<string, mixed> $oldInput Associative array of old input values.
 * @param bool $toCategoryForm If true, redirects to category-form.php, otherwise to categories.php.
 * @return never
 */
function redirect_with_error(string $message, ?int $categoryId = null, array $oldInput = [], bool $toCategoryForm = true): never
{
    $_SESSION['flash_error'] = $message;
    if (!empty($oldInput)) {
        $_SESSION['category_form_old_input'] = $oldInput;
    }
    $location = SITE_URL . '/admin/' . ($toCategoryForm ? 'category-form.php' : 'categories.php');
    if ($toCategoryForm && $categoryId) {
        $location .= '?id=' . $categoryId;
    }
    header('Location: ' . $location, true, 303);
    exit;
}

switch ($action) {
    case 'create':
    case 'update':
        $name = clean_input($_POST['name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $icon = clean_input($_POST['icon'] ?? '');
        $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT); // For update

        $oldInputForRedirect = [
            'name'        => $name,
            'description' => $description,
            'icon'        => $icon,
            'sort_order'  => $_POST['sort_order'] ?? '', // Keep original string for form repopulation
        ];

        $errors = [];
        if (empty($name)) {
            $errors[] = 'Le nom de la catégorie est requis.';
        }
        // If sort_order is invalid, default to 0, but don't treat as an error that stops execution.
        if ($sort_order === false || $sort_order < 0) {
            $sort_order = 0;
        }

        if ($action === 'update' && (!$categoryId || $categoryId <= 0)) {
            redirect_with_error('ID de catégorie invalide pour la mise à jour.', null, [], false); // Redirect to categories list
        }

        if (!empty($errors)) {
            redirect_with_error(implode("\n", $errors), $categoryId, $oldInputForRedirect, true);
        }

        $slug = generate_slug($name);

        try {
            if ($action === 'create') {
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
            } else { // update
                $stmt = $pdo->prepare(
                    "UPDATE categories SET name = :name, slug = :slug, description = :description, icon = :icon, sort_order = :sort_order WHERE id = :id"
                );
                $stmt->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'icon' => $icon,
                    'sort_order' => $sort_order,
                    'id' => $categoryId,
                ]);
                $_SESSION['flash_message'] = 'La catégorie "' . e($name) . '" a été mise à jour avec succès.';
            }
        } catch (PDOException $e) {
            error_log("Category CRUD Error: " . $e->getMessage());
            if ($e->errorInfo[1] == 1062) { // Erreur "Duplicate entry"
                $_SESSION['flash_error'] = 'Une catégorie avec ce nom ou ce slug existe déjà.';
            } else {
                $_SESSION['flash_error'] = 'Une erreur de base de données est survenue.';
            }
            redirect_with_error($_SESSION['flash_error'], $categoryId, $oldInputForRedirect, true); // Redirect back to form
        }
        break;

    case 'delete':
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

        if (!$categoryId || $categoryId <= 0) {
            redirect_with_error('ID de catégorie invalide pour la suppression.', null, [], false);
        }

        try {
            // Check if there are any products associated with this category
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = :category_id");
            $stmt->execute(['category_id' => $categoryId]);
            $productCount = $stmt->fetchColumn();

            if ($productCount > 0) {
                redirect_with_error('Impossible de supprimer la catégorie car elle contient ' . $productCount . ' produit(s). Veuillez d\'abord déplacer ou supprimer ces produits.', null, [], false);
            }

            // Get category name for flash message
            $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = :id");
            $stmt->execute(['id' => $categoryId]);
            $categoryName = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute(['id' => $categoryId]);

            $_SESSION['flash_message'] = 'La catégorie "' . e($categoryName) . '" a été supprimée avec succès.';

        } catch (PDOException $e) {
            error_log("Category Delete Error: " . $e->getMessage());
            redirect_with_error('Une erreur de base de données est survenue lors de la suppression.', null, [], false);
        }
        break;

    default:
        $_SESSION['flash_error'] = 'Action non reconnue.';
        break;
}

header('Location: ' . SITE_URL . '/admin/categories.php', true, 303);
exit;