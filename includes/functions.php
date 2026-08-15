<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';


function generate_slug(string $text): string
{
    return slugify($text);
}


function format_price(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' ' . CURRENCY_SYMBOL;
}


function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text) ?: $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Retourne toutes les catégories actives, triées pour l'affichage.
 * @return array<int, array<string, mixed>>
 */
function get_categories(): array
{
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        'SELECT id, name, slug, description, icon
         FROM categories
         WHERE is_active = 1
         ORDER BY sort_order ASC, name ASC'
    );
    return $stmt->fetchAll();
}

/**
 * Retourne les produits actifs, avec filtre optionnel par slug de catégorie.
 * @return array<int, array<string, mixed>>
 */
function get_products(?string $categorySlug = null): array
{
    $pdo = getDbConnection();

    $sql = 'SELECT p.id, p.name, p.slug, p.description, p.price,
                   p.media_type, p.media_path, p.is_featured,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE p.is_active = 1';

    $params = [];

    if ($categorySlug !== null && $categorySlug !== '') {
        $sql .= ' AND c.slug = :slug';
        $params['slug'] = $categorySlug;
    }

    $sql .= ' ORDER BY p.is_featured DESC, p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retourne un produit unique par son id, ou null s'il n'existe pas / inactif.
 * @return array<string, mixed>|null
 */
function get_product_by_id(int $id): ?array
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p
         INNER JOIN categories c ON c.id = p.category_id
         WHERE p.id = :id AND p.is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
    return $product ?: null;
}


function generate_order_ref(PDO $pdo): string
{
    $today = date('Ymd');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS nb FROM orders WHERE DATE(created_at) = CURDATE()"
    );
    $stmt->execute();
    $count = (int) $stmt->fetch()['nb'] + 1;

    return sprintf('CMD-%s-%04d', $today, $count);
}
