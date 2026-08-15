<?php

declare(strict_types=1);

if (!defined('APP_ENV')) {
    require_once __DIR__ . '/../config/config.php';
}

require_once __DIR__ . '/session.php';

if (!isset($_SESSION['admin_id'])) {
    
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}