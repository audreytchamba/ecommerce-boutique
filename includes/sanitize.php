<?php


declare(strict_types=1);


function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


function clean_input(?string $value): string
{
    $value = trim($value ?? '');
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
}
