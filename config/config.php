<?php
/**
 * config/config.php
 * Constantes globales du site. Aucune logique métier ici.
 * En PRODUCTION (LWS), ce fichier peut être remplacé par des variables
 * d'environnement si l'hébergeur le permet ; pour un mutualisé classique,
 * on modifie simplement les valeurs ci-dessous au déploiement.
 */

declare(strict_types=1);

if (defined('APP_ENV') && defined('SITE_URL') && defined('ROOT_PATH')) {
    return;
}

// ---------------------------------------------------------------------
// Environnement : 'local' ou 'production'
// -> change le comportement des erreurs PHP et du SMTP (voir mail-config.php)
// ---------------------------------------------------------------------
define('APP_ENV', 'local');

// ---------------------------------------------------------------------
// Informations générales du site
// ---------------------------------------------------------------------
define('SITE_NAME', 'GAGA_Empire');
define('SITE_URL', 'http://localhost:8080'); // sans slash final
define('CURRENCY_SYMBOL', 'FCFA');

// ---------------------------------------------------------------------
// Chemins serveur (indépendants de l'OS)
// ---------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . '/uploads/products/');
define('UPLOAD_URL', SITE_URL . '/uploads/products/');

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 Mo
define('UPLOAD_ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/webp']);
define('UPLOAD_ALLOWED_VIDEO_MIME', ['video/mp4', 'video/webm']);
define('UPLOAD_ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('UPLOAD_ALLOWED_VIDEO_EXT', ['mp4', 'webm']);


define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_MINUTES', 15);

if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    // En prod, les erreurs partent dans un fichier log, jamais à l'écran
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/php-error.log');
}
