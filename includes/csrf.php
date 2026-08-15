<?php

declare(strict_types=1);


function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($submitted) ||
        !hash_equals($_SESSION['csrf_token'], $submitted)
    ) {
        http_response_code(403);
        die('Requête invalide (jeton de sécurité manquant ou expiré). Merci de recharger la page.');
    }
}
