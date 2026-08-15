<?php
/**
 * actions/admin/update-order-status.php
 * Traite la mise à jour du statut d'une commande.
 * 
 * Sécurité :
 * 1. Vérification authentification admin
 * 2. Vérification CSRF
 * 3. Validation du statut autorisé
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/sanitize.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed');
}

// Vérifier le CSRF
csrf_verify();

// Récupérer et valider les données
$orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$newStatus = clean_input($_POST['status'] ?? '');

if (!$orderId) {
    $_SESSION['error_message'] = 'ID commande invalide.';
    header('Location: ' . SITE_URL . '/admin/orders.php');
    exit;
}

// Valider le statut
$allowedStatuses = ['pending', 'confirmed', 'delivered', 'cancelled'];
if (!in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['error_message'] = 'Statut invalide.';
    header('Location: ' . SITE_URL . '/admin/order-detail.php?id=' . $orderId);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Vérifier que la commande existe
    $checkStmt = $pdo->prepare('SELECT id FROM orders WHERE id = ?');
    $checkStmt->execute([$orderId]);
    $order = $checkStmt->fetch();
    
    if (!$order) {
        $_SESSION['error_message'] = 'Commande non trouvée.';
        header('Location: ' . SITE_URL . '/admin/orders.php');
        exit;
    }
    
    // Mettre à jour le statut
    $updateStmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
    $updateStmt->execute([
        'status' => $newStatus,
        'id'     => $orderId,
    ]);
    
    $_SESSION['success_message'] = 'Le statut de la commande a été mis à jour avec succès.';
    
} catch (\Throwable $e) {
    error_log('Erreur mise à jour statut commande : ' . $e->getMessage());
    $_SESSION['error_message'] = 'Une erreur technique est survenue.';
}

header('Location: ' . SITE_URL . '/admin/order-detail.php?id=' . $orderId);
exit;
