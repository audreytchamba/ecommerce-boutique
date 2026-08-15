<?php


declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // Durcissement des cookies de session
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,        // inaccessible en JavaScript (anti-XSS session hijacking)
        'samesite' => 'Lax',       // protection CSRF basique au niveau cookie
    ]);

    session_name('ECOMBOUTIQUE_SESSID');
    session_start();

    // Régénère l'ID de session périodiquement pour limiter la fixation de session
    if (empty($_SESSION['_last_regen'])) {
        $_SESSION['_last_regen'] = time();
    } elseif (time() - $_SESSION['_last_regen'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}
