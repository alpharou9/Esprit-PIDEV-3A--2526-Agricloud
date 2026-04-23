-- AgriCloud Database Dump
-- Import instructions:
--   Option A (phpMyAdmin): Open phpMyAdmin → click the "SQL" tab at the SERVER level (not inside any DB) → paste this file → Go
--   Option B (CLI): mysql -u root -p < agricloud.sql

CREATE DATABASE IF NOT EXISTS `agricloud` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `agricloud`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table: roles
-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Admin',    'Administrator with full access',                          '["all"]',                                                    '2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(2, 'Farmer',   'Farmer who can manage farms and sell products',           '["farm.manage", "product.sell", "blog.create", "event.create"]','2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(3, 'Customer', 'Customer who can buy products and participate in events', '["product.buy", "event.participate", "blog.comment"]',        '2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(4, 'guest',    'Customer without access to comments',                     NULL,                                                         '2026-02-10 13:15:18', '2026-02-10 13:15:18');

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `oauth_provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `oauth_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_embeddings` text COLLATE utf8mb4_unicode_ci,
  `face_enrolled_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_oauth` (`oauth_provider`,`oauth_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`),
  KEY `idx_oauth` (`oauth_provider`,`oauth_id`),
  KEY `idx_face_enrolled` (`face_enrolled_at`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `phone`, `profile_picture`, `status`, `email_verified_at`, `created_at`, `updated_at`, `oauth_provider`, `oauth_id`, `face_embeddings`, `face_enrolled_at`) VALUES
(1,  1, 'Admin User',  'admin@agricloud.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL,       NULL, 'active', NULL, '2026-02-05 09:27:55', '2026-02-05 09:27:55', NULL, NULL, NULL, NULL),
(2,  3, 'farouk',      'farouknakkach@gmail.com',  '$2a$10$SEtk5hAbS4ewD.BYHa9TO.0I/pGg2SpYuh3GDDRk/Ylh2GyDrGEpm', '54022877', NULL, 'active', NULL, '2026-02-05 13:12:01', '2026-03-03 08:19:33', 'google', '103181140058911164726', NULL, NULL),
(3,  1, 'admin',       'admin@admin.com',          '$2a$10$JgXubc2dkVdMwChgPvvBzefbmwiY/gBiXVKbXLFRyGGnMZnL9B4mG', '88888888', NULL, 'active', NULL, '2026-02-05 13:29:27', '2026-02-22 13:13:56', NULL, NULL, NULL, NULL),
(4,  2, 'amine',       'daminekh@icloud.com',      '$2a$10$14oFp9N32guGtngFw58zseztSBEGdR0GlemIaFqkJk2C9DLrEzpNe', '54022877', NULL, 'active', NULL, '2026-02-05 14:44:14', '2026-03-01 14:53:04', NULL, NULL, NULL, NULL),
(5,  3, 'customer',    'customer@customer.com',    '$2a$10$MoZXhX3OtXt3ooEpaCeuxO0OTSazPZUAt4hZcGwsSuS87S.H2F6ui', '54022877', NULL, 'active', NULL, '2026-02-05 19:17:43', '2026-03-03 08:49:31', NULL, NULL, NULL, NULL),
(6,  2, 'farmer',      'farmer@farmer.com',        '$2a$10$RtZdFiEK39a7VElMjOkireDZRBaRYxOb7sCV6rNoHmSIJuXqTF5Oy', '66666666', NULL, 'active', NULL, '2026-02-05 19:22:00', '2026-02-06 10:04:04', NULL, NULL, NULL, NULL),
(7,  3, 'jdid',        'jdid@jdid.com',            '$2a$10$VT2vC1JUHVGGVwhIeZ8mteSFJnJefo1K2lR14IoaJUSOzu9lYBKP.', '88996633', NULL, 'active', NULL, '2026-02-06 00:04:28', '2026-02-06 17:02:12', NULL, NULL, NULL, NULL),
(8,  4, 'Guest User',  'guest@agricloud.com',      '$2a$10$XecHiTuN4RoqOvH2nxIPlerLpgh0doGRbsmQiq8ul5rAC.sjxqup2', NULL,       NULL, 'active', NULL, '2026-02-10 13:47:13', '2026-02-10 13:47:13', NULL, NULL, NULL, NULL),
(12, 3, 'youssef',     'youssefkaabachi66@gmail.com','$2a$10$E8hQirsgHRW0nJ2GRUjA6eiwHWaU7uTrXlbKPcRyqwHgMBCgeVJGK','55555555',NULL, 'active', NULL, '2026-02-10 23:13:40', '2026-02-10 23:13:40', NULL, NULL, NULL, NULL),
(21, 2, 'amouna',      'emna_nasraoui@icloud.com', '$2a$10$.lOR1XzlQvFb3NTqG73.N.VKuryfePB9yBGTPmA1ENknxet7DzjDm', '99068438', NULL, 'active', NULL, '2026-03-02 09:39:51', '2026-03-02 09:45:11', NULL, NULL, NULL, NULL),
(30, 3, 'ayman',       'ayman.alnsiri.1k@gmail.com','$2a$10$4Ew.G4cHovBiFntw.366fuJ9ip8/ovsLIRWUc.WsF5qTOnYsfWsD2','23722132', NULL, 'active', NULL, '2026-03-03 07:57:15', '2026-03-03 08:01:36', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table: farms
-- --------------------------------------------------------

DROP TABLE IF EXISTS `farms`;
CREATE TABLE IF NOT EXISTS `farms` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `area` decimal(10,2) DEFAULT NULL,
  `farm_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_location` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `farms` (`id`, `user_id`, `name`, `location`, `latitude`, `longitude`, `area`, `farm_type`, `description`, `image`, `status`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 6, 'firma',   'rimel',   37.23251521, 9.99481201, NULL,    NULL,      NULL,         NULL, 'approved', '2026-02-05 18:42:13', 3, '2026-02-05 19:39:48', '2026-02-06 17:13:39'),
(2, 6, 'benghazi','benghazi', 37.23470197, 9.99343872, 5000.00,'sa9weya', 'with water', NULL, 'pending',  NULL,                 NULL, '2026-02-10 13:31:48', '2026-02-10 13:31:48');

-- --------------------------------------------------------
-- Table: fields
-- --------------------------------------------------------

DROP TABLE IF EXISTS `fields`;
CREATE TABLE IF NOT EXISTS `fields` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `farm_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `soil_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `crop_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordinates` json DEFAULT NULL,
  `status` enum('active','inactive','fallow') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `fields` (`id`, `farm_id`, `name`, `area`, `soil_type`, `crop_type`, `coordinates`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'bonnus field', 5500.00, NULL, NULL, NULL, 'active', '2026-02-05 19:40:31', '2026-02-05 19:40:31');

-- --------------------------------------------------------
-- Table: farm_notifications
-- --------------------------------------------------------

DROP TABLE IF EXISTS `farm_notifications`;
CREATE TABLE IF NOT EXISTS `farm_notifications` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `farm_id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_farm_notification_user` (`user_id`),
  KEY `idx_farm_notification_farm` (`farm_id`),
  KEY `idx_farm_notification_read` (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: products
-- --------------------------------------------------------

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `farm_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','sold_out') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `views` int DEFAULT '0',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_farm_id` (`farm_id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `user_id`, `farm_id`, `name`, `description`, `price`, `quantity`, `unit`, `category`, `image`, `status`, `views`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, 'bsal', 'basla', 10.00, 0, 'kg',      'khodhra', NULL, 'approved', 0, '2026-02-07 14:25:41', 3, '2026-02-05 23:29:27', '2026-03-02 13:41:05'),
(2, 6, NULL, '3sal', '3sal',  15.00, 2, 'dabouza', 'Honey',   NULL, 'approved', 0, '2026-02-05 22:37:38', 3, '2026-02-05 23:37:18', '2026-03-03 08:52:18');

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `seller_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `shipping_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_postal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `delivery_date` date DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_status` (`status`),
  KEY `idx_order_date` (`order_date`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `customer_id`, `product_id`, `seller_id`, `quantity`, `unit_price`, `total_price`, `status`, `shipping_address`, `shipping_city`, `shipping_postal`, `shipping_email`, `shipping_phone`, `notes`, `order_date`, `delivery_date`, `cancelled_at`, `cancelled_reason`, `created_at`, `updated_at`) VALUES
(1,  5, 1, 6, 1, 10.00, 10.00,  'processing', 'benzart',  'benzart',  '7000', NULL,                    NULL,          'maghir hrissa',        '2026-02-05 22:31:27', NULL, NULL, NULL, '2026-02-05 23:31:26', '2026-02-05 23:31:51'),
(2,  5, 1, 6, 1, 10.00, 10.00,  'confirmed',  'benzart',  'benzart',  '7000', NULL,                    NULL,          'barcha hrissa aaa a',  '2026-02-06 08:53:41', NULL, NULL, NULL, '2026-02-06 09:53:41', '2026-03-02 13:31:39'),
(3,  8, 2, 6, 1, 15.00, 15.00,  'confirmed',  'aa',       'aa',       'aa',   NULL,                    NULL,          'aa',                   '2026-02-10 12:47:37', NULL, NULL, NULL, '2026-02-10 13:47:37', '2026-03-02 13:31:33'),
(5,  5, 1, 6, 2, 10.00, 20.00,  'pending',    'bizerte',  'bizerte',  '7000', 'farouknakkach@gmail.com','54022877',   'fagfgea',              '2026-03-02 12:41:05', NULL, NULL, NULL, '2026-03-02 13:41:05', '2026-03-02 13:41:05'),
(6,  5, 2, 6, 1, 15.00, 15.00,  'pending',    'faaef',    'fafea',    'fafzea','farouknakkach@gmail.com','+21654022877','fazfae',             '2026-03-02 12:44:14', NULL, NULL, NULL, '2026-03-02 13:44:14', '2026-03-02 13:44:14'),
(7,  5, 2, 6, 1, 15.00, 15.00,  'pending',    'zaef',     'fezaf',    'fezafza','farouknakkach@gmail.com','+21654022877','eazfa',             '2026-03-02 12:51:00', NULL, NULL, NULL, '2026-03-02 13:51:00', '2026-03-02 13:51:00'),
(8,  26,2, 6, 1, 15.00, 15.00,  'pending',    'a',        'ff',       '7000', 'farouknakkach@gmail.com','54022877',   NULL,                   '2026-03-03 06:09:01', NULL, NULL, NULL, '2026-03-03 07:09:00', '2026-03-03 07:09:00'),
(9,  5, 2, 6, 3, 15.00, 45.00,  'pending',    'ghfgh',    'tunis',    '2001', 'khad.ghd1@gmail.com',   '+21658239600','kjug',                '2026-03-03 07:51:01', NULL, NULL, NULL, '2026-03-03 08:51:01', '2026-03-03 08:51:01'),
(10, 5, 2, 6, 1, 15.00, 15.00,  'pending',    'fazf',     'faefea',   '7000', 'farouknakkach@gmail.com','54022877',   NULL,                   '2026-03-03 07:52:18', NULL, NULL, NULL, '2026-03-03 08:52:18', '2026-03-03 08:52:18');

-- --------------------------------------------------------
-- Table: shopping_cart
-- --------------------------------------------------------

DROP TABLE IF EXISTS `shopping_cart`;
CREATE TABLE IF NOT EXISTS `shopping_cart` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: posts
-- --------------------------------------------------------

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `status` enum('draft','published','unpublished') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `views` int DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` (`id`, `user_id`, `title`, `slug`, `content`, `excerpt`, `image`, `category`, `tags`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'l thoum mela7',       'l-thoum-mela7',       'bellehi l thoum zidouna menou ama raohu mela7 ken tnajmou tnahiw menou l melh rana babouch', NULL, NULL, 'News',          NULL, 'published', 34, '2026-02-06 08:51:51', '2026-02-06 00:03:28', '2026-02-24 21:21:58'),
(2, 5, 'hello frederick',     'hello-frederick',     'hello im a farmer that wants to do farming things',                                          NULL, NULL, 'Farming Guide', NULL, 'published', 6,  '2026-02-24 20:22:36', '2026-02-24 21:18:39', '2026-03-03 07:59:02'),
(3, 3, 'hello im a farmer',   'hello-im-a-farmer',   'im a farmer that wants to do farmer things',                                                 NULL, NULL, 'Farming Guide', NULL, 'published', 8,  '2026-02-24 20:22:34', '2026-02-24 21:21:43', '2026-03-03 08:21:09'),
(4, 3, 'Plants',              'plants',              'jdskbfjsdbfksfjsfdf',                                                                        NULL, NULL, 'Farming Guide', NULL, 'draft',     0,  NULL,                 '2026-03-03 08:36:49', '2026-03-03 08:36:49');

-- --------------------------------------------------------
-- Table: comments
-- --------------------------------------------------------

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `parent_comment_id` bigint UNSIGNED DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_parent_comment_id` (`parent_comment_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `parent_comment_id`, `content`, `status`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 1, 7, NULL, 'klemek shih sahbi',  'pending',  NULL, NULL, '2026-02-06 00:04:58', '2026-02-06 00:04:58'),
(3, 1, 5, NULL, 'jemla wa7da ?',      'approved', NULL, NULL, '2026-02-06 00:29:15', '2026-02-06 00:29:15'),
(4, 1, 5, NULL, 'sba3lkhir ya weldi', 'approved', NULL, NULL, '2026-02-06 00:29:22', '2026-02-06 00:29:22'),
(6, 3, 5, NULL, 'bannable comment',   'approved', NULL, NULL, '2026-03-03 07:26:10', '2026-03-03 07:26:10'),
(7, 2, 30,NULL, 'naaah',              'approved', NULL, NULL, '2026-03-03 07:59:00', '2026-03-03 07:59:00');

-- --------------------------------------------------------
-- Table: events
-- --------------------------------------------------------

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `capacity` int DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'upcoming',
  `registration_deadline` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `events` (`id`, `user_id`, `title`, `slug`, `description`, `event_date`, `end_date`, `location`, `latitude`, `longitude`, `capacity`, `image`, `category`, `status`, `registration_deadline`, `created_at`, `updated_at`) VALUES
(1, 3, 'event', 'event', 'barcha jaw', '2026-02-12 15:45:00', NULL, 'dra win', NULL, NULL, 50,   NULL, 'Workshop',   'upcoming', NULL, '2026-02-06 09:50:54', '2026-02-06 09:52:34'),
(2, 3, 'afeaf', 'afeaf', 'fzafa f',   '2026-02-12 14:55:00', NULL, 'gzrgz',   NULL, NULL, NULL, NULL, 'Conference', 'ongoing',  NULL, '2026-02-11 09:28:08', '2026-02-11 09:28:08');

-- --------------------------------------------------------
-- Table: participations
-- --------------------------------------------------------

DROP TABLE IF EXISTS `participations`;
CREATE TABLE IF NOT EXISTS `participations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `registration_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','confirmed','cancelled','attended') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_reason` text COLLATE utf8mb4_unicode_ci,
  `attended` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_user` (`event_id`,`user_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `participations` (`id`, `event_id`, `user_id`, `registration_date`, `status`, `notes`, `cancelled_at`, `cancelled_reason`, `attended`, `created_at`, `updated_at`) VALUES
(1,  1, 5,  '2026-02-06 09:52:53', 'cancelled', NULL, '2026-03-03 06:19:01', 'User cancelled', 0, '2026-02-06 09:52:53', '2026-03-03 07:19:00'),
(2,  1, 6,  '2026-02-06 09:54:34', 'confirmed', NULL, NULL, NULL, 0, '2026-02-06 09:54:34', '2026-03-03 07:21:33'),
(5,  1, 2,  '2026-02-24 21:09:57', 'attended',  NULL, '2026-02-24 20:27:15', 'User cancelled', 1, '2026-02-24 21:09:57', '2026-03-03 08:28:52'),
(6,  2, 2,  '2026-02-24 21:27:19', 'confirmed', NULL, NULL, NULL, 0, '2026-02-24 21:27:19', '2026-02-24 21:31:10'),
(7,  2, 30, '2026-03-03 07:10:47', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:10:47', '2026-03-03 07:10:47'),
(8,  1, 30, '2026-03-03 07:11:06', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:11:06', '2026-03-03 07:11:06'),
(9,  2, 5,  '2026-03-03 07:18:52', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:18:52', '2026-03-03 07:18:52'),
(10, 2, 30, '2026-03-03 07:59:27', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:59:27', '2026-03-03 07:59:27'),
(11, 1, 30, '2026-03-03 08:30:33', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 08:30:33', '2026-03-03 08:30:33');

-- --------------------------------------------------------
-- Table: user_activity_logs
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_activity_logs`;
CREATE TABLE IF NOT EXISTS `user_activity_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Full-text indexes
-- --------------------------------------------------------

ALTER TABLE `posts`    ADD FULLTEXT KEY `idx_search` (`title`,`content`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`description`);

-- --------------------------------------------------------
-- Foreign key constraints
-- --------------------------------------------------------

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT;

ALTER TABLE `farms`
  ADD CONSTRAINT `farms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `farms_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `fields`
  ADD CONSTRAINT `fields_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

ALTER TABLE `farm_notifications`
  ADD CONSTRAINT `farm_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `farm_notifications_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `participations`
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
