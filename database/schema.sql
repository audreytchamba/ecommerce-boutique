-- =====================================================================
-- SCHEMA.SQL — Boutique E-Commerce (Paiement à la Livraison)
-- Compatible MySQL 5.7+ / MariaDB (LWS Perso)
-- Charset : utf8mb4 (support emojis, accents français complets)
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table : categories
-- Les 4 rubriques produits (Cake & Apero, Musique, Beauty & Fragrance, Wine...)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)      NOT NULL,
    `slug`        VARCHAR(120)      NOT NULL,
    `description` VARCHAR(255)      NULL,
    `icon`        VARCHAR(50)       NULL COMMENT 'Nom icône ou emoji utilisé côté front',
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : products
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id`   INT UNSIGNED NOT NULL,
    `name`          VARCHAR(150)   NOT NULL,
    `slug`          VARCHAR(180)   NOT NULL,
    `description`   TEXT           NULL,
    `price`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `media_type`    ENUM('image','video') NOT NULL DEFAULT 'image',
    `media_path`    VARCHAR(255)   NOT NULL COMMENT 'Chemin relatif ex: uploads/products/xxxx.jpg',
    `stock`         INT UNSIGNED   NOT NULL DEFAULT 0,
    `sales_count`   INT UNSIGNED   NOT NULL DEFAULT 0 COMMENT 'Incrémenté à chaque commande validée, sert au dashboard',
    `is_featured`   TINYINT(1)     NOT NULL DEFAULT 0,
    `is_active`     TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_products_slug` (`slug`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_sales_count` (`sales_count`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : product_images
-- Galerie d'images additionnelles pour un produit.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id`  INT UNSIGNED NOT NULL,
    `image_path`  VARCHAR(255) NOT NULL COMMENT 'Chemin relatif ex: uploads/products/gallery/xxxx.jpg',
    `alt_text`    VARCHAR(255) NULL,
    `sort_order`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_product_images_product` (`product_id`),
    CONSTRAINT `fk_product_images_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table : orders
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_ref`         VARCHAR(20)    NOT NULL COMMENT 'Référence lisible, ex: CMD-20260815-0001',
    `customer_lastname`  VARCHAR(100)   NOT NULL,
    `customer_firstname` VARCHAR(100)   NOT NULL,
    `customer_email`    VARCHAR(150)   NOT NULL,
    `customer_phone`    VARCHAR(30)    NOT NULL,
    `city`              VARCHAR(100)   NOT NULL,
    `neighborhood`      VARCHAR(100)   NOT NULL COMMENT 'Quartier',
    `delivery_date`     DATE           NOT NULL,
    `status`            ENUM('pending','confirmed','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `total_amount`      DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    `notes`             VARCHAR(500)   NULL,
    `email_sent`        TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_ref` (`order_ref`),
    KEY `idx_orders_created_at` (`created_at`),
    KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : order_items
-- On "snapshot" le nom et le prix du produit au moment de la commande
-- pour ne jamais perdre l'historique si le produit change/est supprimé.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id`       INT UNSIGNED NOT NULL,
    `product_id`     INT UNSIGNED NULL COMMENT 'NULL si le produit source a été supprimé',
    `product_name`   VARCHAR(150)   NOT NULL,
    `unit_price`     DECIMAL(10,2)  NOT NULL,
    `quantity`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `subtotal`       DECIMAL(10,2)  NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product`
        FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table : admins
-- Sécurité anti-bruteforce intégrée (failed_attempts / locked_until)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`         VARCHAR(60)    NOT NULL,
    `email`            VARCHAR(150)   NOT NULL,
    `password_hash`    VARCHAR(255)   NOT NULL COMMENT 'password_hash() BCRYPT ou ARGON2I',
    `failed_attempts`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`     DATETIME       NULL,
    `last_login_at`    DATETIME       NULL,
    `is_active`        TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admins_username` (`username`),
    UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DONNÉES INITIALES (seed) — les 4 rubriques de la maquette
-- =====================================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `sort_order`, `is_active`)
VALUES
('Cake & Aperro',     'cake-apero',        'Mini cakes faits maison et muffins salés raffinés.', 'utensils', 1, 1),
('Musique',            'musique',           "Sélection d'albums exclusifs, casques et enceintes.", 'headphones', 2, 1),
('Beauty & Fragrance', 'beauty-fragrance',  'Parfums envoûtants et cosmétiques de soin.', 'sparkles', 3, 1),
('Wine',               'wine',              'Vins soigneusement choisis pour vos tables et réceptions.', 'wine-glass', 4, 1)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `sort_order` = VALUES(`sort_order`),
    `is_active` = VALUES(`is_active`);

-- NOTE : Le compte admin par défaut n'est PAS inséré ici en clair.
-- Il sera créé via le script scripts/create-admin.php (voir guide d'exécution),
-- qui génère un password_hash() propre — ne jamais insérer un mot de passe
-- en clair directement en base.
