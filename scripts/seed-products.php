<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/sanitize.php';
require __DIR__ . '/../includes/functions.php';

$pdo = getDbConnection();

// Produits à créer avec leurs images
$products = [
    // Cakes (Gâteaux)
    [
        'category' => 'cake-apero',
        'name' => 'Gâteau Noël Festif',
        'description' => 'Délicieux gâteau de Noël garni de fruits confits et de chocolat noir.',
        'price' => 15000,
        'media_type' => 'image',
        'media_path' => '/assets/images/peggy_marco-christmas-1931236_1920.jpg',
        'images' => [
            '/assets/images/peggy_marco-christmas-1931236_1920.jpg',
            '/assets/images/hacolor-cake-7388277_1920.jpg',
        ]
    ],
    [
        'category' => 'cake-apero',
        'name' => 'Gâteau Fraise Gourmand',
        'description' => 'Génoise légère aux fraises fraîches et crème fouettée.',
        'price' => 12000,
        'media_type' => 'image',
        'media_path' => '/assets/images/vogue0987-peach-cake-8598851_1920.jpg',
        'images' => [
            '/assets/images/vogue0987-peach-cake-8598851_1920.jpg',
            '/assets/images/congerdesign-wedding-cake-639516_1920.jpg',
        ]
    ],
    [
        'category' => 'cake-apero',
        'name' => 'Gâteau Amande Premium',
        'description' => 'Gâteau moelleux à la poudre d\'amande et ganache chocolat.',
        'price' => 18000,
        'media_type' => 'image',
        'media_path' => '/assets/images/weteran20a-almond-cake-7825686_1920.jpg',
        'images' => [
            '/assets/images/weteran20a-almond-cake-7825686_1920.jpg',
            '/assets/images/peggy_marco-christmas-1931236_1920.jpg',
        ]
    ],

    // Wines (Vins)
    [
        'category' => 'wine',
        'name' => 'Vin Blanc Excellence',
        'description' => 'Vin blanc sec de terroir, fruité et harmonieux.',
        'price' => 25000,
        'media_type' => 'image',
        'media_path' => '/assets/images/santiagogonzalez-strawberry-wine-6782968_1920.jpg',
        'images' => [
            '/assets/images/santiagogonzalez-strawberry-wine-6782968_1920.jpg',
            '/assets/images/tonyzhu-liquor-5884_1920.jpg',
        ]
    ],
    [
        'category' => 'wine',
        'name' => 'Liqueur Artisanale',
        'description' => 'Liqueur douce aux épices et fruits exotiques.',
        'price' => 28000,
        'media_type' => 'image',
        'media_path' => '/assets/images/sponchia-liqueur-3786194_1920.jpg',
        'images' => [
            '/assets/images/sponchia-liqueur-3786194_1920.jpg',
            '/assets/images/4262076-drink-3507413_1920.jpg',
        ]
    ],

    // Beauty & Fragrance (Beauté & Parfums)
    [
        'category' => 'beauty-fragrance',
        'name' => 'Parfum Délicat Floral',
        'description' => 'Parfum frais aux notes florales épanouies, idéal pour le jour.',
        'price' => 45000,
        'media_type' => 'image',
        'media_path' => '/assets/images/hlevi-perfume-6899766_1920.jpg',
        'images' => [
            '/assets/images/hlevi-perfume-6899766_1920.jpg',
            '/assets/images/noname_13-perfume-2142792_1920.jpg',
        ]
    ],
    [
        'category' => 'beauty-fragrance',
        'name' => 'Parfum Intensité Oud',
        'description' => 'Parfum oriental riche aux notes d\'oud et ambre, pour les soirées.',
        'price' => 55000,
        'media_type' => 'image',
        'media_path' => '/assets/images/fabien_raquidel-perfume-8032808_1920.jpg',
        'images' => [
            '/assets/images/fabien_raquidel-perfume-8032808_1920.jpg',
            '/assets/images/hlevi-perfume-6899766_1920.jpg',
        ]
    ],
];

try {
    $pdo->beginTransaction();

    foreach ($products as $product) {
        // Récupérer la catégorie
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $product['category']]);
        $category = $stmt->fetch();

        if (!$category) {
            echo "❌ Catégorie '{$product['category']}' non trouvée\n";
            continue;
        }

        // Insérer le produit
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, name, slug, description, price, media_type, media_path, is_active)
             VALUES (:category_id, :name, :slug, :description, :price, :media_type, :media_path, 1)'
        );

        $slug = generate_slug($product['name']);
        
        $stmt->execute([
            'category_id' => $category['id'],
            'name' => $product['name'],
            'slug' => $slug,
            'description' => $product['description'],
            'price' => $product['price'],
            'media_type' => $product['media_type'],
            'media_path' => $product['media_path'],
        ]);

        $productId = (int) $pdo->lastInsertId();
        echo "✅ Produit '{$product['name']}' créé (ID: $productId)\n";

        // Ajouter les images à la galerie
        if (!empty($product['images'])) {
            foreach ($product['images'] as $index => $imagePath) {
                $stmt = $pdo->prepare(
                    'INSERT INTO product_images (product_id, image_path, sort_order)
                     VALUES (:product_id, :image_path, :sort_order)'
                );

                $stmt->execute([
                    'product_id' => $productId,
                    'image_path' => $imagePath,
                    'sort_order' => $index,
                ]);

                echo "   - Galerie: $imagePath\n";
            }
        }
    }

    $pdo->commit();
    echo "\n✅ Tous les produits ont été créés avec succès!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
