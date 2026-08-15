<?php
/**
 * config/mail-config.php
 * Paramètres d'envoi d'e-mails. Utilisé par actions/send-confirmation-email.php.
 *
 * En local : voir Étape 5 (MailHog / Mailtrap) — aucun vrai e-mail n'est envoyé.
 * En production : identifiants SMTP pro fournis par LWS (voir Étape 6).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (APP_ENV === 'local') {
    // ---- Configuration locale (ex: MailHog écoute sur le port 1025) ----
    define('SMTP_HOST', '127.0.0.1');
    define('SMTP_PORT', 1025);
    define('SMTP_USER', '');
    define('SMTP_PASS', '');
    define('SMTP_SECURE', '');        // '' = pas de TLS/SSL en local avec MailHog
    define('SMTP_FROM_EMAIL', 'commandes@boutique.local');
    define('SMTP_FROM_NAME', SITE_NAME);
} else {
    // ---- Configuration production : SMTP pro LWS ----
    // Ces valeurs sont visibles dans le panel LWS > Messagerie > Configuration
    define('SMTP_HOST', 'mail.votredomaine.com');
    define('SMTP_PORT', 587);
    define('SMTP_USER', 'commandes@votredomaine.com');
    define('SMTP_PASS', 'REMPLACER_PAR_MOT_DE_PASSE_BOITE_MAIL');
    define('SMTP_SECURE', 'tls');
    define('SMTP_FROM_EMAIL', 'commandes@votredomaine.com');
    define('SMTP_FROM_NAME', SITE_NAME);
}

// Adresse recevant une copie de chaque commande (notification interne)
define('ADMIN_NOTIFICATION_EMAIL', 'contact@votredomaine.com');
