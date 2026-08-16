<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

csrf_verify();

$action = $_POST['action'] ?? '';
$pdo = getDbConnection();

/**
 * Gère l'upload d'un fichier (média principal ou galerie).
 * @param array $fileData Données de $_FILES pour un fichier.
 * @param string $uploadSubDir Sous-dossier de destination ('/' ou '/gallery/').
 * @return array|string Tableau ['path' => ..., 'type' => ...] en cas de succès, sinon message d'erreur.
 */
function handle_file_upload(array $fileData, string $uploadSubDir = '/'): array|string
{
    if ($fileData['error'] !== UPLOAD_ERR_OK) {
        return 'Erreur lors du transfert du fichier.';
    }

    if ($fileData['size'] > UPLOAD_MAX_SIZE) {
        return 'Le fichier est trop volumineux (Max ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'Mo).';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileData['tmp_name']);

    $allowedMimeTypes = array_merge(UPLOAD_ALLOWED_IMAGE_MIME, UPLOAD_ALLOWED_VIDEO_MIME);
    if (!in_array($mimeType, $allowedMimeTypes)) {
        return 'Type de fichier non autorisé.';
    }

    $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
    $mediaType = str_starts_with($mimeType, 'image/') ? 'image' : 'video';
    $uniqueName = uniqid('prod_', true) . '.' . $extension;

    $destinationDir = UPLOAD_DIR . ltrim($uploadSubDir, '/');
    if (!is_dir($destinationDir)) {
        // Le sous-dossier (notamment /gallery/) peut ne pas exister encore.
        mkdir($destinationDir, 0755, true);
    }

    $destinationPath = $destinationDir . $uniqueName;

    if (!move_uploaded_file($fileData['tmp_name'], $destinationPath)) {
        return 'Échec du déplacement du fichier uploadé.';
    }

    return [
        'path' => '/uploads/products' . $uploadSubDir . $uniqueName,
        'type' => $mediaType
    ];
}

/**
 *
 * @param array<string, mixed> $oldInput
 */
function redirect_with_error(string $message, ?int $productId = null, array $oldInput = []): never
{
    $_SESSION['flash_error'] = $message;
    if (!empty($oldInput)) {
        $_SESSION['product_form_old_input'] = $oldInput;
    }
    $location = SITE_URL . '/admin/product-form.php' . ($productId ? '?id=' . $productId : '');
    header('Location: ' . $location, true, 303);
    exit;
}


switch ($action) {
    case 'create':
    case 'update':
        // --- Validation des données ---
        $name = clean_input($_POST['name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

        
        $oldInputForRedirect = [
            'name'        => $name,
            'description' => $description,
            'price'       => $_POST['price'] ?? '',
            'category_id' => $categoryId,
            'stock'       => $_POST['stock'] ?? '',
            'is_active'   => $isActive,
            'is_featured' => $isFeatured,
        ];

        $errors = [];
        if (empty($name)) $errors[] = 'Le nom du produit est requis.';
        if ($price === false || $price < 0) $errors[] = 'Le prix est invalide.';
        if ($categoryId === false || $categoryId <= 0) $errors[] = 'La catégorie est requise.';
        if ($stock === false || $stock < 0) $errors[] = 'Le stock est invalide.';

        if (!empty($errors)) {
            redirect_with_error(implode("\n", $errors), $productId, $oldInputForRedirect);
        }

        $slug = generate_slug($name);

        // --- Traitement des uploads ---
        $mediaResult = null;
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $mediaResult = handle_file_upload($_FILES['media_file']);
            if (is_string($mediaResult)) {
                redirect_with_error('Média principal : ' . $mediaResult, $productId, $oldInputForRedirect);
            }
        }

      
        if ($action === 'create' && !$mediaResult) {
            redirect_with_error('Le média principal est obligatoire pour la création.', null, $oldInputForRedirect);
        }

        $galleryResults = [];
        if (isset($_FILES['gallery_files'])) {
            foreach ($_FILES['gallery_files']['name'] as $key => $fileName) {
                if ($_FILES['gallery_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $_FILES['gallery_files']['name'][$key],
                        'type' => $_FILES['gallery_files']['type'][$key],
                        'tmp_name' => $_FILES['gallery_files']['tmp_name'][$key],
                        'error' => $_FILES['gallery_files']['error'][$key],
                        'size' => $_FILES['gallery_files']['size'][$key],
                    ];
                    $galleryUpload = handle_file_upload($fileData, '/gallery/');
                    if (is_string($galleryUpload)) {
                        // On nettoie ce qui a déjà été uploadé avant d'échouer ici.
                        if ($mediaResult && file_exists(ROOT_PATH . $mediaResult['path'])) {
                            unlink(ROOT_PATH . $mediaResult['path']);
                        }
                        foreach ($galleryResults as $img) {
                            if (file_exists(ROOT_PATH . $img['path'])) {
                                unlink(ROOT_PATH . $img['path']);
                            }
                        }
                        redirect_with_error('Galerie : ' . $galleryUpload, $productId, $oldInputForRedirect);
                    }
                    $galleryResults[] = $galleryUpload;
                }
            }
        }

       
        try {
            $pdo->beginTransaction();

            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    "INSERT INTO products (category_id, name, slug, description, price, media_type, media_path, stock, is_featured, is_active)
                     VALUES (:category_id, :name, :slug, :description, :price, :media_type, :media_path, :stock, :is_featured, :is_active)"
                );
                $stmt->execute([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'price' => $price,
                    'media_type' => $mediaResult['type'],
                    'media_path' => $mediaResult['path'],
                    'stock' => $stock,
                    'is_featured' => $isFeatured,
                    'is_active' => $isActive,
                ]);
                $productId = (int)$pdo->lastInsertId();
                $_SESSION['flash_message'] = 'Produit "' . e($name) . '" créé avec succès.';

            } else { // update
                $stmt = $pdo->prepare("SELECT media_path FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                $oldProduct = $stmt->fetch();

                $params = [
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                    'is_featured' => $isFeatured,
                    'is_active' => $isActive,
                    'id' => $productId,
                ];

                $sql = "UPDATE products SET category_id=:category_id, name=:name, slug=:slug, description=:description, price=:price, stock=:stock, is_featured=:is_featured, is_active=:is_active";
                if ($mediaResult) {
                    $sql .= ", media_type=:media_type, media_path=:media_path";
                    $params['media_type'] = $mediaResult['type'];
                    $params['media_path'] = $mediaResult['path'];
                }
                $sql .= " WHERE id=:id";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                // Supprimer l'ancien média principal si un nouveau est uploadé
                if ($mediaResult && $oldProduct && file_exists(ROOT_PATH . $oldProduct['media_path'])) {
                    unlink(ROOT_PATH . $oldProduct['media_path']);
                }

                
                if (!empty($_POST['delete_gallery_images'])) {
                    $idsToDelete = array_values(array_filter(
                        array_map('intval', (array) $_POST['delete_gallery_images']),
                        static fn ($id) => $id > 0
                    ));

                    if (!empty($idsToDelete)) {
                        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));

                        
                        $stmt = $pdo->prepare(
                            "SELECT image_path FROM product_images WHERE id IN ($placeholders) AND product_id = ?"
                        );
                        $stmt->execute([...$idsToDelete, $productId]);
                        $imagesToDelete = $stmt->fetchAll();
                        foreach ($imagesToDelete as $img) {
                            if (file_exists(ROOT_PATH . $img['image_path'])) {
                                unlink(ROOT_PATH . $img['image_path']);
                            }
                        }

                       
                        $stmt = $pdo->prepare(
                            "DELETE FROM product_images WHERE id IN ($placeholders) AND product_id = ?"
                        );
                        $stmt->execute([...$idsToDelete, $productId]);
                    }
                }

                $_SESSION['flash_message'] = 'Produit "' . e($name) . '" mis à jour avec succès.';
            }

            
            if (!empty($galleryResults)) {
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                foreach ($galleryResults as $img) {
                    $stmt->execute([$productId, $img['path']]);
                }
            }

            $pdo->commit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            
            if ($mediaResult && file_exists(ROOT_PATH . $mediaResult['path'])) unlink(ROOT_PATH . $mediaResult['path']);
            foreach ($galleryResults as $img) {
                if (file_exists(ROOT_PATH . $img['path'])) unlink(ROOT_PATH . $img['path']);
            }
            error_log("Product CRUD Error: " . $e->getMessage());
            redirect_with_error("Une erreur de base de données est survenue.", $productId, $oldInputForRedirect);
        }

        header('Location: ' . SITE_URL . '/admin/products.php', true, 303);
        exit;

    case 'delete':
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if (!$productId) {
            $_SESSION['flash_error'] = 'ID de produit invalide pour la suppression.';
            header('Location: ' . SITE_URL . '/admin/products.php', true, 303);
            exit;
        }

        try {
            $pdo->beginTransaction();

            
            $stmt = $pdo->prepare("SELECT media_path FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $mainMedia = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $stmt->execute([$productId]);
            $galleryImages = $stmt->fetchAll(PDO::FETCH_COLUMN);

            
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $productName = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);

            
            if ($mainMedia && file_exists(ROOT_PATH . $mainMedia)) {
                unlink(ROOT_PATH . $mainMedia);
            }
            foreach ($galleryImages as $imgPath) {
                if (file_exists(ROOT_PATH . $imgPath)) {
                    unlink(ROOT_PATH . $imgPath);
                }
            }

            $pdo->commit();

            $_SESSION['flash_message'] = 'Le produit "' . e($productName) . '" et tous ses médias ont été supprimés.';

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Product Delete Error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Une erreur de base de données est survenue lors de la suppression.';
        }

        header('Location: ' . SITE_URL . '/admin/products.php', true, 303);
        exit;

    default:
        $_SESSION['flash_error'] = 'Action non reconnue.';
        header('Location: ' . SITE_URL . '/admin/products.php', true, 303);
        exit;
}
