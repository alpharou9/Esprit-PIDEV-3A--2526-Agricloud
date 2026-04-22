-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 08, 2026 at 04:08 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agricloud`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

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

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `parent_comment_id`, `content`, `status`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 1, 7, NULL, 'klemek shih sahbi', 'pending', NULL, NULL, '2026-02-06 00:04:58', '2026-02-06 00:04:58'),
(3, 1, 5, NULL, 'jemla wa7da ?', 'approved', NULL, NULL, '2026-02-06 00:29:15', '2026-02-06 00:29:15'),
(4, 1, 5, NULL, 'sba3lkhir ya weldi', 'approved', NULL, NULL, '2026-02-06 00:29:22', '2026-02-06 00:29:22'),
(6, 3, 5, NULL, 'bannable comment', 'approved', NULL, NULL, '2026-03-03 07:26:10', '2026-03-03 07:26:10'),
(7, 2, 30, NULL, 'naaah', 'approved', NULL, NULL, '2026-03-03 07:59:00', '2026-03-03 07:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

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
) ;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `user_id`, `title`, `slug`, `description`, `event_date`, `end_date`, `location`, `latitude`, `longitude`, `capacity`, `image`, `category`, `status`, `registration_deadline`, `created_at`, `updated_at`) VALUES
(1, 3, 'event', 'event', 'barcha jaw', '2026-02-12 15:45:00', NULL, 'dra win', NULL, NULL, 50, NULL, 'Workshop', 'upcoming', NULL, '2026-02-06 09:50:54', '2026-02-06 09:52:34'),
(2, 3, 'afeaf', 'afeaf', 'fzafa	f', '2026-02-12 14:55:00', NULL, 'gzrgz', NULL, NULL, NULL, NULL, 'Conference', 'ongoing', NULL, '2026-02-11 09:28:08', '2026-02-11 09:28:08');

-- --------------------------------------------------------

--
-- Table structure for table `farms`
--

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
) ;

--
-- Dumping data for table `farms`
--

INSERT INTO `farms` (`id`, `user_id`, `name`, `location`, `latitude`, `longitude`, `area`, `farm_type`, `description`, `image`, `status`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 6, 'firma', 'rimel', 37.23251521, 9.99481201, NULL, NULL, NULL, NULL, 'approved', '2026-02-05 18:42:13', 3, '2026-02-05 19:39:48', '2026-02-06 17:13:39'),
(2, 6, 'benghazi', 'benghazi', 37.23470197, 9.99343872, 5000.00, 'sa9weya', 'with water', NULL, 'pending', NULL, NULL, '2026-02-10 13:31:48', '2026-02-10 13:31:48');

-- --------------------------------------------------------

--
-- Table structure for table `fields`
--

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
) ;

--
-- Dumping data for table `fields`
--

INSERT INTO `fields` (`id`, `farm_id`, `name`, `area`, `soil_type`, `crop_type`, `coordinates`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'bonnus field', 5500.00, NULL, NULL, NULL, 'active', '2026-02-05 19:40:31', '2026-02-05 19:40:31');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

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
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `product_id`, `seller_id`, `quantity`, `unit_price`, `total_price`, `status`, `shipping_address`, `shipping_city`, `shipping_postal`, `shipping_email`, `shipping_phone`, `notes`, `order_date`, `delivery_date`, `cancelled_at`, `cancelled_reason`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 6, 1, 10.00, 10.00, 'processing', 'benzart', 'benzart', '7000', NULL, NULL, 'maghir hrissa', '2026-02-05 22:31:27', NULL, NULL, NULL, '2026-02-05 23:31:26', '2026-02-05 23:31:51'),
(2, 5, 1, 6, 1, 10.00, 10.00, 'confirmed', 'benzart', 'benzart', '7000', NULL, NULL, 'barcha hrissa aaa a', '2026-02-06 08:53:41', NULL, NULL, NULL, '2026-02-06 09:53:41', '2026-03-02 13:31:39'),
(3, 8, 2, 6, 1, 15.00, 15.00, 'confirmed', 'aa', 'aa', 'aa', NULL, NULL, 'aa', '2026-02-10 12:47:37', NULL, NULL, NULL, '2026-02-10 13:47:37', '2026-03-02 13:31:33'),
(5, 5, 1, 6, 2, 10.00, 20.00, 'pending', 'bizerte', 'bizerte', '7000', 'farouknakkach@gmail.com', '54022877', 'fagfgea', '2026-03-02 12:41:05', NULL, NULL, NULL, '2026-03-02 13:41:05', '2026-03-02 13:41:05'),
(6, 5, 2, 6, 1, 15.00, 15.00, 'pending', 'faaef', 'fafea', 'fafzea', 'farouknakkach@gmail.com', '+21654022877', 'fazfae', '2026-03-02 12:44:14', NULL, NULL, NULL, '2026-03-02 13:44:14', '2026-03-02 13:44:14'),
(7, 5, 2, 6, 1, 15.00, 15.00, 'pending', 'zaef', 'fezaf', 'fezafza', 'farouknakkach@gmail.com', '+21654022877', 'eazfa', '2026-03-02 12:51:00', NULL, NULL, NULL, '2026-03-02 13:51:00', '2026-03-02 13:51:00'),
(8, 26, 2, 6, 1, 15.00, 15.00, 'pending', 'a', 'ff', '7000', 'farouknakkach@gmail.com', '54022877', NULL, '2026-03-03 06:09:01', NULL, NULL, NULL, '2026-03-03 07:09:00', '2026-03-03 07:09:00'),
(9, 5, 2, 6, 3, 15.00, 45.00, 'pending', 'ghfgh', 'tunis', '2001', 'khad.ghd1@gmail.com', '+21658239600', 'kjug', '2026-03-03 07:51:01', NULL, NULL, NULL, '2026-03-03 08:51:01', '2026-03-03 08:51:01'),
(10, 5, 2, 6, 1, 15.00, 15.00, 'pending', 'fazf', 'faefea', '7000', 'farouknakkach@gmail.com', '54022877', NULL, '2026-03-03 07:52:18', NULL, NULL, NULL, '2026-03-03 08:52:18', '2026-03-03 08:52:18');

-- --------------------------------------------------------

--
-- Table structure for table `participations`
--

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

--
-- Dumping data for table `participations`
--

INSERT INTO `participations` (`id`, `event_id`, `user_id`, `registration_date`, `status`, `notes`, `cancelled_at`, `cancelled_reason`, `attended`, `created_at`, `updated_at`) VALUES
(1, 1, 5, '2026-02-06 09:52:53', 'cancelled', NULL, '2026-03-03 06:19:01', 'User cancelled', 0, '2026-02-06 09:52:53', '2026-03-03 07:19:00'),
(2, 1, 6, '2026-02-06 09:54:34', 'confirmed', NULL, NULL, NULL, 0, '2026-02-06 09:54:34', '2026-03-03 07:21:33'),
(5, 1, 2, '2026-02-24 21:09:57', 'attended', NULL, '2026-02-24 20:27:15', 'User cancelled', 1, '2026-02-24 21:09:57', '2026-03-03 08:28:52'),
(6, 2, 2, '2026-02-24 21:27:19', 'confirmed', NULL, NULL, NULL, 0, '2026-02-24 21:27:19', '2026-02-24 21:31:10'),
(7, 2, 26, '2026-03-03 07:10:47', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:10:47', '2026-03-03 07:10:47'),
(8, 1, 26, '2026-03-03 07:11:06', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:11:06', '2026-03-03 07:11:06'),
(9, 2, 5, '2026-03-03 07:18:52', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:18:52', '2026-03-03 07:18:52'),
(10, 2, 30, '2026-03-03 07:59:27', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 07:59:27', '2026-03-03 07:59:27'),
(11, 1, 30, '2026-03-03 08:30:33', 'confirmed', NULL, NULL, NULL, 0, '2026-03-03 08:30:33', '2026-03-03 08:30:33');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

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

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `title`, `slug`, `content`, `excerpt`, `image`, `category`, `tags`, `status`, `views`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 5, 'l thoum mela7', 'l-thoum-mela7', 'bellehi l thoum zidouna menou ama raohu mela7 ken tnajmou tnahiw menou l melh rana babouch', NULL, NULL, 'News', NULL, 'published', 34, '2026-02-06 08:51:51', '2026-02-06 00:03:28', '2026-02-24 21:21:58'),
(2, 5, 'hello frederick', 'hello-frederick', 'hello im a farmer that  wants to do farming things', NULL, NULL, 'Farming Guide', NULL, 'published', 6, '2026-02-24 20:22:36', '2026-02-24 21:18:39', '2026-03-03 07:59:02'),
(3, 3, 'hello im a farmer', 'hello-im-a-farmer', 'im a farmer that wants to do farmer things', NULL, NULL, 'Farming Guide', NULL, 'published', 8, '2026-02-24 20:22:34', '2026-02-24 21:21:43', '2026-03-03 08:21:09'),
(4, 3, 'Plants', 'plants', 'jdskbfjsdbfksfjsfdf', NULL, NULL, 'Farming Guide', NULL, 'draft', 0, NULL, '2026-03-03 08:36:49', '2026-03-03 08:36:49');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

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
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `farm_id`, `name`, `description`, `price`, `quantity`, `unit`, `category`, `image`, `status`, `views`, `approved_at`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 6, NULL, 'bsal', 'basla', 10.00, 0, 'kg', 'khodhra', NULL, 'approved', 0, '2026-02-07 14:25:41', 3, '2026-02-05 23:29:27', '2026-03-02 13:41:05'),
(2, 6, NULL, '3sal', '3sal', 15.00, 2, 'dabouza', 'Honey', NULL, 'approved', 0, '2026-02-05 22:37:38', 3, '2026-02-05 23:37:18', '2026-03-03 08:52:18');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

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

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Administrator with full access', '[\"all\"]', '2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(2, 'Farmer', 'Farmer who can manage farms and sell products', '[\"farm.manage\", \"product.sell\", \"blog.create\", \"event.create\"]', '2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(3, 'Customer', 'Customer who can buy products and participate in events', '[\"product.buy\", \"event.participate\", \"blog.comment\"]', '2026-02-05 09:27:24', '2026-02-05 09:27:24'),
(4, 'guest', 'hes a customer but he doesnt have access to comments', NULL, '2026-02-10 13:15:18', '2026-02-10 13:15:18');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

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

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`, `updated_at`) VALUES
(15, 29, 2, 1, '2026-03-03 07:55:15', '2026-03-03 07:55:15'),
(16, 30, 2, 1, '2026-03-03 07:58:18', '2026-03-03 07:58:18'),
(17, 31, 2, 1, '2026-03-03 08:14:59', '2026-03-03 08:14:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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
  `oauth_provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'OAuth provider: google, facebook, apple, null',
  `oauth_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User ID from OAuth provider',
  `face_embeddings` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of face embeddings (base64 encoded)',
  `face_enrolled_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when user enrolled face',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `unique_oauth` (`oauth_provider`,`oauth_id`),
  KEY `idx_email` (`email`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`),
  KEY `idx_oauth` (`oauth_provider`,`oauth_id`),
  KEY `idx_face_enrolled` (`face_enrolled_at`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `password`, `phone`, `profile_picture`, `status`, `email_verified_at`, `created_at`, `updated_at`, `oauth_provider`, `oauth_id`, `face_embeddings`, `face_enrolled_at`) VALUES
(1, 1, 'Admin User', 'admin@agricloud.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'active', '2026-02-05 09:27:55', '2026-02-05 09:27:55', '2026-02-05 09:27:55', NULL, NULL, NULL, NULL),
(2, 3, 'farouk', 'farouknakkach@gmail.com', '$2a$10$SEtk5hAbS4ewD.BYHa9TO.0I/pGg2SpYuh3GDDRk/Ylh2GyDrGEpm', '54022877', 'uploads\\profile_pictures\\user_2.jpg', 'active', NULL, '2026-02-05 13:12:01', '2026-03-03 08:19:33', 'google', '103181140058911164726', NULL, NULL),
(3, 1, 'admin', 'admin@admin.com', '$2a$10$JgXubc2dkVdMwChgPvvBzefbmwiY/gBiXVKbXLFRyGGnMZnL9B4mG', '88888888', NULL, 'active', NULL, '2026-02-05 13:29:27', '2026-02-22 13:13:56', NULL, NULL, '[{\"embedding\":\"vm4HXT3GT6w+Kr0BvnbM9j95JEw+2mLrPaIayr7KNjS+CTfAvT7jJL4nykA8XfiwvhKoXj5suZY+xvR0PjyWob83gjQ+BiEhv5LIdr7TImq+vtJ2vZj6lD6dxUi+EL1RvgrR0r4zOTK+GTfAPd/Zuji0UAC+Y0lzvdB+fD6BitQ/A2elvr6EML57Bcw+8qfMvlRxBj7ZfmK+piFNPffp0D5RXXq+gNOdPnF/ULwUmao/DzRXPaDPH76PNRY+xnr3vlAnHD5euo4/AmeYvj5+qD4RvVW9DzxQv1FfIr4690c+1CaWPpVn5L6jMVs+7Yg+OrxIwD6zi1C/K86gvyLbQz8AhYm/HuotPyPrbT4IgiM/DPbnPV35UD8O4IW+BcxkvZp1NL7r/3M/MvWTviEguL7LisQ81MOUv4n6sDyzye4+7iKrPuQHRL43/ly8Z6esvVP25751v6g+rQm2vk6GPr2sLIW+cKsbPgxK0D8BA/Q+vrHbPwF0nb8LiVI9vzwQvlcorj67Qrm+kKhwPtKM+L2H6rO+m1oQvi+JjL9VB5Y+IiiUPqjS7jygwyA+qXbrPy8dDL9FtMQ8zS0wvstBTb5y9+a9DK3pPwZv6z29hly/KxhAPg5OJj7cpI6+juRcPrP2zruNJiA/RxRKPhiZSz29uj+/dFbOvhL6uj8iHCg\\u003d\",\"capturedAt\":\"2026-02-22T14:13:42.4146496\"},{\"embedding\":\"vnB+Wj3Domo+K6HcvngLTD95k+A+2UxlPapeOL7LCxi+CeekvTS1DL4opk48ZHhAvhUPNT5tTLA+x5YsPj1Yw784XBI+Anitv5MmcL7Sz0a+vxXEvZZG5D6cuhq+EVCLvgmiyb4xss6+FuPXPdzmG7thIuC+Y0yQvc3sSj6BgM4/BHSnvr4RAL53MOg+8ZnYvlF4Mz7YovS+pl57PfUdaD5RMli+gfU+PnHrYLvTydQ/EFF2PaBmzb6O4bY+yC4DvlSGmT5f1hc/Ayw/vj+Fqj4RMhW9GHUwv1EyL746aiQ+1YNoPpYFa76lBF8+700wOibWgD61ZLe/LFxMvyKtvj8AZPa/H1N8PyOC/D4FKMc/DI/JPWh5UD8Pvwq+BAqYvZwpSL7rz4c/M3+KviDaRL7LMbg8zu5ov4n3JzyG/1w+7mtkPuIi5r48MP68S6cIvVaNor50ljo+rt7uvlAUBL2oTr++b9TLPg+taD8BcZo+vtgQPwDsr78LIuI9wg1evlSusj67p56+k4BOPtRTLL2LMji+m4YyvjJ+rL9WMFI+I9yCPqmwUjzJccA+rKSmPy9ooL9Fg2I84dJAvsyFHr51cxm9F0/VPwbRkD3D2wi/K1r+PgyDRz7csKC+j4ROPrRWfLuc3kA/RwEcPhV/gz2/W7G/dX+bvhDTbD8i7iU\\u003d\",\"capturedAt\":\"2026-02-22T14:13:44.232365\"},{\"embedding\":\"vZWs3r0V9Ds+D4r2vfbYXz8/0B4/EBpavaSo1b72Qxq+HP26vgtvir1vcwA9VxIwvg2Lwz54GVY+q70vPiw9/b8QFLo+osJ4v4PSW768Aky+um+fviIK8D6tl5E75EEgPYRfw76XXCm+daFRPkhXRD5VFfK+vHQcvmTLxD3sduA+yn8ivoAcDL6SqJE+8iravht5Dj8BZYi+Ui+MPXRvOz4d9qi+ZGEmPktZLr4UcZU+8CFUPh/2ML4xyUI+qmWPvgEt7z5icXE+x9CKva75/D4sWPW+HLILv2KUGb23ReU+eHdxPr8cp75eL9Q+d+3QvSKhkT4b3/e/FmoVvwrWED8N78++6TP4P0O4zj5wEiw+4NiQPZbOVD8EqKK9Agw0vFLK4L7Ag2c/CJZEvjxy8r60hL68WLiwv3UNnj6OWJs++LkUPxxrtr2tGCS9FhhhvcpISL5wyCE+mBk2vU/vRL2mioy+RRM6vXGyQj66B0c+dEcqPvubvL8zUmQ9EQn3vqijFD6g9AW9EBHIPnIwbr3V1ty+o7VhPCDw8L8P6Yc+IjTOPnwjsL4Sq7M997WOPyrXer9SMCq+PYnqvqhoL74z/Bk7s/n4Pv6Vqr4kCfS/AGMBPlo00z6kari+Q2acPoU5vL2U2zE/N4dpPimHurqSZ0C/UHFovZ4lBT7rmTc\\u003d\",\"capturedAt\":\"2026-02-22T14:13:45.7445058\"},{\"embedding\":\"voS8lj3ie8A+IFaJvoXAeT97ik4+00LvPgqRU77JLTi+Be9Yu7F4gL449kY8xEwwviZ83j5g4vQ+0vlyPj69Lb8106w9m5ayv5Z0Lr7F3rO+weOdvZIfzj6JRq6+FWNlveLMiL4DrP6+BPHMPfCjf71HTF6+T+C0vcMzHj5xjBI/Duq8vsI2bL5H6Hc+4f7svjxEVD7V7YS+riXrPgc4vD5BhCq+kaBAPnj96jxu8/c/FPY9PZdPI76Lpq0+0yMOvno/ZD5o/GI/DN5QvkuhzD4OwyK9XQxev04PjL4mFRY+2zHYPpUo9b61eAs++uDIuy95AD7AmvS/KhEAvxyTkj783ly/JB2DPx5IMT3/WLQ/BSScPY2jiD8TyRK9/MUGvbCrlL7YC44/MNZovhSjvL7CmOY7AYTgv4pkhrzvAqg+7C/WPsqyXL5bZjI8kKV4vZ8chb5wue8+ufemvlufjr2CsJO+fC3OPjrZVj8DzAg+vhq3PvQj4L8DNWQ996LAvi0UZz6/Iku+tmOQPuD5bL2asPO+lbfkvlLL379bTC4+PM8OPqWY5T18IIA+xZUAPy5Y7L9GBAQ9Zp1IvtNzI75+Lry9kSZNPwQCvD4eYNi/J8MyPeQJsj7Xa8O+o3zQPrMokLsQpMA/Q/hvPf5YPD3LrRO/gAxPvg62pT8s4I0\\u003d\",\"capturedAt\":\"2026-02-22T14:13:49.4589574\"},{\"embedding\":\"PIVjkD4fjHM9VwcUOjrMAD8KP74+RnjAvhxVer8LuwI8HULgvhF5lD2f3Nu+j+o8vqjuizuvQHA+NU/IPdTlur6aan8+LGMGvurL0b7dj/C+sIOmOf5yAD6DqVs8VyhwPAWaZL5zWpK+TZQLPhkPiD6e8Mu/A81Evm/s8r5OWco+gb4MvVmk+r6ZBkE/FJplvhWSgD6oeHQ9yxLWPS58/b2wLnw9iSxOPhOoUr4Odns+dPg+PueKir2IcGC824SgPJsYmD7Tkww+4q3Gvqo9Hr1PgpG+opnpvxdw2T24RGQ9QglJPsrmuTzjd3Q+DxTYvnZX/7xv7EC+w0zXvtxCMj8Fp+y+zF3cPtjEg71F1xY+8lUUvVGnqD8Mdac6sHMAPbLgXb7Aw78+sbb2veS6EL4+Ixa+BOVpvx4I5D7F9TQ/B71QPxmw4z5Oona+hwoovK8p8L5FeG0+COFePiduYL4lTFi7z36gvfR7Uj45UOC9aJ+kPnqZK78M8OQ8f8B0vqvlNT1i/Xg7MiFAPf7IEb4yVsi+9FIsvVXh/L7NQhY+lUsEPvUOUL1Buz2+ClpIPvHF/789M+K+40TOvqTXqz2KHeU9Y4QDPukkjb6MCTK+hzQlPgn+575E8Tm9rEPMPqMHeL6Qm8U+wAp4PYet8r7K8Jy/DNVPPlaXJT7KiFc\\u003d\",\"capturedAt\":\"2026-02-22T14:13:51.1710634\"}]', '2026-02-22 13:13:56'),
(4, 2, 'amine', 'daminekh@icloud.com', '$2a$10$14oFp9N32guGtngFw58zseztSBEGdR0GlemIaFqkJk2C9DLrEzpNe', '54022877', 'uploads\\profile_pictures\\user_4.jpg', 'active', NULL, '2026-02-05 14:44:14', '2026-03-01 14:53:04', NULL, NULL, NULL, NULL),
(5, 3, 'customer', 'customer@customer.com', '$2a$10$MoZXhX3OtXt3ooEpaCeuxO0OTSazPZUAt4hZcGwsSuS87S.H2F6ui', '54022877', 'uploads\\profile_pictures\\user_5.jpg', 'active', NULL, '2026-02-05 19:17:43', '2026-03-03 08:49:31', NULL, NULL, '[{\"embedding\":\"vn+0FD3Giwo+KkJKvnm6qj94Hwg+0pFuPejT2r7LF+C+EuL2vO5paL4y7PY8ri1Avh9iWD5lkYw+yHvNPjT89786CbA9zmYOv5UbsL7J+B++vb+FvZWzTj6cVa6+ESuKvfs76r4fOUS+ChzPPdR+1LyoK4C+YMblvdDdtD56NHI/Cq+6vrvRtr5WMvA+6jZQvjnfAz7UKeC+pTy9Pfn1kj5Fyy2+hn2gPnGhfDwgu+A/FMdGPZ8vX76PtYI+0PsevmQJKD5bdLA/CBEPvkBT/D4Pn1u9OzJFv06RO742NpE+1tV2Ppc4cb6rh80+8/VGOgM+AD68v/C/LlpcvyFtsT7+RJq/ITQ9PyC29j3uZwk/CQ0bPZhc4D8Rc+K98TpuvbJTHL7hWg4/MwuNvhpHTL7FF3Q8b6Xov4ofybwmstg+78BtPtYTNr5VhBy6q+8gvYQHvr5w2eo+t5Akvkk4Tr14dAG+a58YPiJn8j8EsA0+vtJYPv0INr8IZlo9z2/+vkJEfj6/nJm+pSZePt6Anr2jF8q+m/ijvkGBjL9cRIw+NS5+PqvU0j1d3VA+vwQ+Py1/FL9C/fY9HnwkvtCJ6754gFi9ZjQCPwd/oj3psoi/KCAsPftxZT7aAX6+knCaPrH68rugr6A/Q45dPgYm3D3QhFy/eh5Rvggdbz8ovok\\u003d\",\"capturedAt\":\"2026-03-03T09:15:59.4100568\"},{\"embedding\":\"vfetoDw/+Ig9zzMovjKLQD9SN5A/CQMCu6GS8L7gTA6+FJaSvhGwJL1K5WQ9N8x8vYJp0D5kTWo+m7SePkwYz78hphw+RZDev45rML7OTGS+wSo3viKNMD6sn269DtA2vB6+mL6C1Zy+WgAwPg9h5D4FpLq+nkyhvna9qj4+2KE+0vssvqelXL5aScA+7jouvlf8Rj7p2Ey+YoWePaiuXT5R3R++UZnIPoEYLb4Fh4M+9HJsPbO7nb43oVY+sq7NvadBjj4lyrI+7IgOvWupcD47TNq982tEv1NAcb2kAug+jsqsPqLQOb5ruog+h5CEvSFrcj5bkIa/G7EgvxwR3D7xXPC/BNjwPz0Daz5L0Jw+5HqUPbOc2D8CyNm9zgDsvK6dLr7LFHE/AMrcvh7Xub61cQ29UUS0v4b3Uz5YthM+8JIjPw+Whr4lquK9lDyCvjEHD76ARtU+oXr8vYYXu70u1ga+PKqKvY+35z7RpGI+jBmlPvGgOr8grlg93FX6vndjBz6mRRm+DxlAPpWB+70YAC6+luCju8p4wL8YbOg+OKHbPnSNvL21iJI+Rct6PyCSw79MEjq97VoUvqk4OL49kFq9lsz5PvtdJr2PWBy/DGVCPltVCD6ma0G+QMpRPpnprr23JpA/RXdwPkBMjzwsLIC/SHaFvSOIZD79a34\\u003d\",\"capturedAt\":\"2026-03-03T09:16:02.2065564\"},{\"embedding\":\"vlTblj5dl548mq6ovbYhDT18bTG+AXHDvr3ee75cPw+7piDAvUqTpD4KfIi+kNCIvo3v072W7vw86x0Bvk2PXD3BDcy8oboUPA/fUDvYxci+UX+iPmC/xzrHIQA7GxyAvXXci71mQdC9IoMXPewepD2/4s++oXrWvkwkgDzGdbC92ktBvtRUir48nFg+owNwPoJwTz6CPuE9i200PjeywL6ktj6+WWWMvYy0Fr6QK929dmb0PvtjpL5AfLS9CMOOvhCrPz4ztys+YyFJPay/kD4owcY84gcOvXTGLzzOH9S7FFqwPwrkrT6KAiw+Q74Kvksk3j2aRbC6Bg4APloMCj3sr/q8C6dQvG9dDb4G3BY+1BcOvpBmwT6p3MC8WZWUPaAnzL7GtzE+bniKPWAuZj6FIV67sDgAvIeJYD6DQmg+j7KHPfYBuD2Xoyg8C0BUPY4yrD131bQ+bP0lPRrbDj27c4q+YUAfPI2KEL2Dhrw+eO32PeM/brxm4bC92A2Kvxj5krzfVgw+f7ldvIszxz2AyJe+1IcCvaMdIb6hFUI9I8JdPm9ymj3HOHG9tLC0Pock676OKOi91T5Qvp4xMj4iI7K9sw0yPiDJhj7t/PW9MdyWPhsbRL2C1h4+ES3oPjZc5b6hOFY+Y7SgvVBPu72b/Xi+CUz+PhSSrT1Mi4I\\u003d\",\"capturedAt\":\"2026-03-03T09:16:04.6890773\"},{\"embedding\":\"vn//3z2432g+IUvkvn8izD97RlY+1RkEPf26nr7JzPi+CQ+kvL34iL4y0Aw8i24ovh+Dlz5n9SY+zigyPkGN/786vVM9tHOgv5Uddb7Pfr++vTKRvY16Cj6Rbw6+FRhUvfbFzr4VQUS+BNQrPdIExr0HLxC+XPcmvcLiSD54xLI/DPKfvriI7r5TkvY+5RuWvkN6dz7UiQ++qk6jPe8gij5MrJy+iMnwPnjVijxcs6I/E3wiPYjnJ76I+to+0UTnvmxCcj5jdIs/B3q+vkq3OD4UAtq9Q8wzv05EvL4sE3k+2p38PpUb/r6uook+9+C2u5l8cD6+3Aq/LQZcvx+IND77Syi/I7KaPx/RUj32jKw/CDgJPYQfSD8Toim99azCvaEoAL7fPWk/MjE0vhroo77EY6I74iOwv4outryqx4w+7f3FPtEeqr5U/iQ7HLeQvZAiRL5yaCc+tuA4vlLWoL2K1L++a7w/PiZUgD8BCnY+vHWIPvc0+r8HHw498d1yvjKA8j69kdi+rb2iPt14NL2idEO+l7/1vkq+wr9ba7g+MblRPqq6MT1BQ/Y+vb32Py3rCL9E0uA9Q0rYvs79Dr57+C69bHJuPwg/7D4BRPC/KkyJPf3WKz7Xk2i+mKhiPrYX/rum5OA/RClzPgQ47z3CfMK/fAn2vgx/tj8ok9o\\u003d\",\"capturedAt\":\"2026-03-03T09:16:25.9397376\"},{\"embedding\":\"vgAPaz1TkOk9qWVEvkeV/D9WiPw/A3TcPDI0wL7M/zS+Hu98vd1QOL3Jnnw9CyFIvYMNZD5XfLA+okBePjhMi78jAgw+K6MKv41lgL7LCOO+sqcEvhGGbD62tju9Noy8vFcw7L5+YFi+VSDRPiaeZj4AC/y+krgKvkk9fj5YRC8+5YS6vq0Ayr5qXvI+7bDYvk6CQD7pHAq+c0HCPfW+Vz5gvTS+YYpEPoI4S73Horg+8g+rPZbeNb5WoXs+sY4YvYM81D48nIA+6//avcfwSD4jRtG92Om0v05vt73Awv0+lTHiPpaHf75/Gsw+oNU0vQT00T6Dbc6/H/9pvytBiD71fTa/CKvoPzXHzz45faQ+7aW6PYZo7D8FNyq90PFYvRcCjb7G+e8/CfyPvhKLD77By+y9Sopwv4eaJD5jsos+6rGzPwYwGr4NVEm9lcimvf+7lL6GEeE+m9tcvbkxTL0Puji+SdhtvRJYAz7jX0w+n1x1PvdzDr8bEoQ90Uy8vnnJwz6m7Li+AYC+PqCkx72HsXG+ltzevVnVjL8k/Ek+NIm5PonsZb08BG8+bSqlPxRR1r9EiMa9p5iovrX2Cr5FpTy9XFIrPv2Fe7zA4yC/Ek/kPlkDuD7A8sS+WfTmPpxxZL1aouA/QSlDPlPt2T1q22+/SiubvYLLCD8EmV4\\u003d\",\"capturedAt\":\"2026-03-03T09:16:27.6652761\"}]', '2026-03-03 08:16:34'),
(6, 2, 'farmer', 'farmer@farmer.com', '$2a$10$RtZdFiEK39a7VElMjOkireDZRBaRYxOb7sCV6rNoHmSIJuXqTF5Oy', '66666666', 'uploads\\profile_pictures\\user_6.jpg', 'active', NULL, '2026-02-05 19:22:00', '2026-02-06 10:04:04', NULL, NULL, NULL, NULL),
(7, 3, 'jdid', 'jdid@jdid.com', '$2a$10$VT2vC1JUHVGGVwhIeZ8mteSFJnJefo1K2lR14IoaJUSOzu9lYBKP.', '88996633', NULL, 'active', NULL, '2026-02-06 00:04:28', '2026-02-06 17:02:12', NULL, NULL, NULL, NULL),
(8, 4, 'Guest User', 'guest@agricloud.com', '$2a$10$XecHiTuN4RoqOvH2nxIPlerLpgh0doGRbsmQiq8ul5rAC.sjxqup2', NULL, NULL, 'active', NULL, '2026-02-10 13:47:13', '2026-02-10 13:47:13', NULL, NULL, NULL, NULL),
(12, 3, 'youssef', 'youssefkaabachi66@gmail.com', '$2a$10$E8hQirsgHRW0nJ2GRUjA6eiwHWaU7uTrXlbKPcRyqwHgMBCgeVJGK', '55555555', NULL, 'active', NULL, '2026-02-10 23:13:40', '2026-02-10 23:13:40', NULL, NULL, NULL, NULL),
(14, 4, 'Guest_c3a77013', 'guest_c3a77013-de36-4498-844e-f5dec4a6febf@agricloud.com', '$2a$10$bk2RoY7Sn0nDQJ9HHoeGJ.H23FhOwGNCctVwSdsr2gDXD37ghKlU.', NULL, NULL, 'active', NULL, '2026-02-16 21:29:45', '2026-02-16 21:29:45', NULL, NULL, NULL, NULL),
(15, 4, 'Guest_ce7044fe', 'guest_ce7044fe-0859-4888-b2cb-254bcae8a683@agricloud.com', '$2a$10$tPmMZa71Gh.J9ariW/IACesjE5RLoESpRf1ZjdkKtTctpbPqXgmKO', NULL, NULL, 'active', NULL, '2026-02-16 21:46:34', '2026-02-16 21:46:34', NULL, NULL, NULL, NULL),
(16, 4, 'Guest_57571801', 'guest_57571801-6d46-4908-b60b-ba766f8d2d17@agricloud.com', '$2a$10$GdwwnxH/IO2tCH/IKqvXpexwFUKoU8Q65LuZOCDT9Fh/ZmxC0P6gy', NULL, NULL, 'active', NULL, '2026-02-16 21:46:51', '2026-02-16 21:46:51', NULL, NULL, NULL, NULL),
(17, 3, 'faezf', 'aef@a.com', '$2a$10$GniMaGIHLK5YqFWEbJ8M7O/tL4lChffIoBOfFE13P52IV/RtJ/2La', NULL, NULL, 'active', NULL, '2026-02-16 22:37:52', '2026-02-16 22:37:52', NULL, NULL, NULL, NULL),
(18, 4, 'Guest_f94b1601', 'guest_f94b1601-aa36-47ec-a555-384336cfae63@agricloud.com', '$2a$10$zuvj1ViGGclAev/mjD6DCO2kkm6YA0YVplZu4A4jieoiCR0nhgN1C', NULL, NULL, 'active', NULL, '2026-02-17 08:43:12', '2026-02-17 08:43:12', NULL, NULL, NULL, NULL),
(20, 4, 'Guest_2598be2b', 'guest_2598be2b-19db-4f15-b14a-9798087c4844@agricloud.com', '$2a$10$71uTlciQ4iu0OZlCXVV7vOGI9fXyAfDldED5wbslpy.z0Y.VfkSYO', NULL, NULL, 'active', NULL, '2026-03-02 09:38:17', '2026-03-02 09:38:17', NULL, NULL, NULL, NULL),
(21, 2, 'amouna', 'emna_nasraoui@icloud.com', '$2a$10$.lOR1XzlQvFb3NTqG73.N.VKuryfePB9yBGTPmA1ENknxet7DzjDm', '99068438', NULL, 'active', NULL, '2026-03-02 09:39:51', '2026-03-02 09:45:11', NULL, NULL, '[{\"embedding\":\"vgoQSzz6Ojk9vPL5vjdqoD9dyp4/BhCsu95+0L7MpVC+FnPivgdGKr1HLpw9CsfYvUUMNz54Z0o+sn7xPm+7O78gavY+XDqgv41Wwb7mrEm+s6BHvhXXhT6KAeK9WYu+vU9jd751k9S+UmCQPg/SWD3NLe2+kitavkSlcz5ojEI+4j3MvrQhDL5/RqQ+9/T4vnJ2bj7r5Va+joOpPfzIlT5Ng5y+ZF8UPoRp2r3jZ5E+8NSyPSOicr5RlEA+q/HLvbZ5jD5aJOg+3HQ0ver7eD4v6T2935HEv1gA+L3LYqw+mCFIPpL8mr6CZTY+qGGEvLKlpD5zci+/G+IUvyWMaz8DPPa/EImAPzzmnz5aKVY++VhmPRZExD8IU8W9qTBTPLptxb7EYjo/GOyLvi0ueL64+Y+8hqZYv4uWKj5F7kE+9tKxPwcaqr3A5ge9mq70vfgxBr6Dbdo+mBkivdhxFb2WEwq+M2WEvQSd0j7PDFk+nhrzPu1+nL8U87A97wcmvlgUPz6c8Ie+ClzvPqVX5LyqVdC+hG/bvUKtnL8aAv4+N+kcPnq29r3aHRI+RRIIPyCNpb9OglC9nmLMvqRBz75vSOK9KgOoPwIjSLznxQC/ES7qPlgPzj69V3C+fmx2PqCCSr1VfjI/QumcPjaQ5zv8t/C/St74vbK0yj7+vLg\\u003d\",\"capturedAt\":\"2026-03-02T10:40:42.953199\"},{\"embedding\":\"vgihbDzKr5A9uQH1vjAPeD9cyh4/BwDuu+QCEL7L/iC+EEjkvgmcGL0fglo9A4jcvTcxuj58TkQ+sx/NPnokGb8f+pI+X42Av41Io77oxp2+tApSvhnTaz6ECGO9TIdqvUnwob5xu6q+TzzrPhEy9j3OmLW+k2rKvkMgDD5muCo+3+TSvrC3AL6AY4Y+9tW6vnZKiD7n2fq+khA9PfYkJD5OCwq+X4rOPoUG373o0ZU+7mfgPQm7Ar5OU3k+qmLHvbemBj5Wubo+2bW4vfIG/D4v9bu95O04v1hWDb3CM+g+ltjmPpG3Tr6Cqgw+pwywvKG21D5urdC/G7yyvyU2Yj8Dahy/EPOUPz0uRD5ezng+99aQPQjbfD8Hlnu9oR/XPPmier7B8Co/GeO3vizzLb63ikS8cAPgv4t1Oz5FhQY+97ejPwfbq724LAK9laPavgTJwr6C28Q+msTkveJCL72dsQW+K8CEvSHlJj7NL1w+nLzYPuzw8r8UkRA9+nuYvlZm5z6dBTy+DSuuPqXEi7yATAC+gNxMvSPHLL8W3RI+NhHGPnex8L3udp8+PH8VPyBA079Oxva9px50vqKYhL5wN1K9Nc2gPwFbTL0GgnC/D3q7Pleg7T64nz6+fZYbPqE1FL1a0mg/Ql1vPjCYFbuTggC/Sxi2vbElQj75Szw\\u003d\",\"capturedAt\":\"2026-03-02T10:40:48.5683499\"},{\"embedding\":\"vbL12j7T60M+QG37vg28uj8eB+4+xhb+Pbca8L7Pg4i9m9GkPSfNYD2H0rC9F12YvdTqHj43x0w+0A2APkD0Lb6WtiU9s2NEv35J6jxY2Oi+tp1QvoM0Zj343BC9oKeZPQYmuz0oKdS9wtw9PlOj5L3Gzyy9pSxUvS5Z/D17vaA+eR6pvwLD3b5iKck+1TitvLgqmD7tMuK+qdPPPjyAjb0HdVy+3WcXPilFDj2M7ok+/kcxPm+Ptr6S0J4+5op5vh2Jlj42Wc4/ClxuPJJUqD4i4nO8dkOov2+NQb2x5tU+fYm7Pjt8bL7hbaU+0PSWO1lNYD6IZmS+9b3ovyOTuz7uYoa+wLGuPwVmrj4vAB4+mzXJPYXW0D61pBi+YiKSvi36eL5VcwQ/J8DWvjVrLL5dzbg+OO77v0CNLT6Zw8A+1vc1PsBdBL3/L9k+HzEZvSPrIjwQcSI+rRAMvmp8fD3k61i+xAKaPqQr5D8cYcM+mHJ5Pwdqlr5NoQG9t9e2vtEHUD68YFm+AmdFPyd3+DxVmFa+N3KovVzkXL9QDcY+J92DPpqWRL1JAQ0+jwJ6PwicmL8DDcO9s9FAvtZqNr6E7+C+Wm1QPnnD1j5bpoK++k2Dun3JqD6C5FK+m8UOPjFxCz3w8rw/NawDvY3yDz3zws+/gkx8vlN8+D8WoQg\\u003d\",\"capturedAt\":\"2026-03-02T10:40:53.3017899\"},{\"embedding\":\"vmiYoD3A+h4+KGXjvnRK7D947/4+3hGNPYx+nr7J8Ha+BtA4vV4XFL4jGRI8cxkAvgz02D5tTHA+xnzfPjyfB7819AI+D518v5IfIL7WlOS+vn0kvZuCRD6dXf6+D+Pkvgv2Ir44GlK+INOzPeQYLzvoPtC+Zt94vdM4UD6BhwQ/AW+lvr/hFL6CizU+9P3ivlpJZT7asU6+pUgxPfkKxD5SQBu+fwwfPnMsTrx9dTY/DOYfPZ5OC76OZ7Q+w1EzvkhGsD5e5Wc/AGgxvj4k5j4T2gS8/0Tqv1JF1L474l4+0b+SPpNSFr6f8Ak+6jxuOxJVQD6vRDe/KqvdvyLwKT8BEO2/HiMZPyWJTz4QAkI/DnCpPTpEAD8OKQ6+CQk0vY8A3L7seCQ/MlK0viM8Hb7M8NY83fd0v4qUDj0NJ0w+7mjcPugjhr4tLXu8kTxYvVaF3r54NRk+qeKuvk3LCL2xlYS+clCVPgQk6D7+F+0+vfHzPwK+y78Mmf49wMOGvldKJT651zK+iqS6Ps4dlr2B99a+myJLviiCjr9RvMI+IFcCPqXYODwa5Wg+ogztPy7Ugr9HBWg8tGNQvsgIC75vwnm8308+Pwa9xz2whoS/KqX0PhMnzD7dray+jc4kPrPEHrvEHkA/R3ZNPhvMiz20lAW/cYBpvhgaXD8ghJk\\u003d\",\"capturedAt\":\"2026-03-02T10:40:55.4318513\"},{\"embedding\":\"vbUKgDwLR7o9y17Gvi1o5j9NmWo/CxnCvIp1kL7iPHa+BSn2vhSQRrzyMRA9J4TIvaV5FD5ju3I+lU1dPj54yb8dcCY+S+rOv45Mz77HE2q+x6oGvikHyT6tiSO8vN8YPAHepL6H2iK+Y11uPhr7LD4Z2Y6+p7kpvoIq2D4km3Y+yneivqKq9L5WqbA+6nUCvli7jD7nMf++UhJgPX0AlT5DRia+RSGvPnxqQL4OtdM+9UhZPewmAb4t8Ww+tXtfvZ4nHD4WmnU+631CvUSMKD5AZQ69/Lzsv1EdR72feek+hCkKPqOBwr5Ya+Q+gpQYvUO4GD5B3Ka/GrEfvxX5yz7sQdq+973/PzwExD5L1SY+3n3YPcf8jD798OK9xWyhvQNWZr7IhZk+8uyAvh/TfL6wkG+9SFt4v4Sv6j5vkdI+6srbPxUvbb4abai9lC/ivkiCOL6ADYw+peyqvV8Udr0oyPC+Pqp8vZ9jHD7NPaU+en8aPu5zaL8kbvQ9w15gvoQcTD6ncS2+AsGQPouUQL0DuVS+lr2sPBCYgL8RKtQ+MRFPPnGZlr2+M9c+NmMyPyDZSL9NM/q+EdNYvq72BL4iBKa9rHU9Pv2E2r23REi/BRVzPmWlGT6a08a+OQH+PpgobL3XeqQ/RNqgPj+8JTsRWwC/RkQlvSD/nj751AQ\\u003d\",\"capturedAt\":\"2026-03-02T10:40:58.0660509\"}]', '2026-03-02 09:41:01'),
(22, 4, 'Guest_224793a3', 'guest_224793a3-f260-481a-af4e-da3535b9feba@agricloud.com', '$2a$10$b/fJMjqzUhqT9Vk7eBn92egKBYfCSitvUZ8WjH69/jnD6J45PsNjS', NULL, NULL, 'active', NULL, '2026-03-03 06:51:21', '2026-03-03 06:51:21', NULL, NULL, NULL, NULL),
(23, 4, 'Guest_284fdd46', 'guest_284fdd46-0347-4281-9adf-e11eacdb20dd@agricloud.com', '$2a$10$6jZsl4KpC7d.QjEbBEvreu4DqsYDuZYqTt1OIpmBx6WQ9BFs4KWg.', NULL, NULL, 'active', NULL, '2026-03-03 06:56:14', '2026-03-03 06:56:14', NULL, NULL, NULL, NULL),
(24, 4, 'Guest_bdc2ca79', 'guest_bdc2ca79-18e0-4946-a072-af096cd1e037@agricloud.com', '$2a$10$m/qFkiie7K2edIlOCqr9lO8I5cfGa9tBk46vRkHm.RSVU1LogFb7G', NULL, NULL, 'active', NULL, '2026-03-03 07:04:04', '2026-03-03 07:04:04', NULL, NULL, NULL, NULL),
(25, 4, 'Guest_cf4cb4d2', 'guest_cf4cb4d2-0831-4f31-a646-416aa0e2ef53@agricloud.com', '$2a$10$obDfbv0v7YVBqrGCqmzKcO5O4AQI33JzEyYgTELw6K8/iSpkD3V7W', NULL, NULL, 'active', NULL, '2026-03-03 07:04:55', '2026-03-03 07:04:55', NULL, NULL, NULL, NULL),
(26, 4, 'Guest_63c7eb40', 'guest_63c7eb40-c484-4e82-a708-6a5db50c6dfc@agricloud.com', '$2a$10$zczNW2wcbw2XBRaVrYGnmOcvqOmv8VocdtdXL0UukGlYF3xZCJCHy', NULL, NULL, 'active', NULL, '2026-03-03 07:07:47', '2026-03-03 07:07:47', NULL, NULL, NULL, NULL),
(27, 4, 'Guest_0a4c3bf0', 'guest_0a4c3bf0-253e-4732-9e0b-7a4b94bab6a8@agricloud.com', '$2a$10$8mmgmeBNOBmUYsY//7orPu6mM3E6KUOACIIT19L7kklJAEt3qjm2K', NULL, NULL, 'active', NULL, '2026-03-03 07:15:42', '2026-03-03 07:15:42', NULL, NULL, NULL, NULL),
(28, 4, 'Guest_b1e80029', 'guest_b1e80029-cfaf-40f3-881c-3e83ab3387b2@agricloud.com', '$2a$10$WoEpKR1qJPndcmAuwV8QYOA6ezD7uSRnPEqQmsYgSqoLzTw7ly4Q2', NULL, NULL, 'active', NULL, '2026-03-03 07:25:34', '2026-03-03 07:25:34', NULL, NULL, NULL, NULL),
(29, 4, 'Guest_375c474d', 'guest_375c474d-162c-4908-b2f9-82e3e995bad1@agricloud.com', '$2a$10$UYqRNlphu3Sk2.hRnoft.eePTw/xJ.L8GGrFBc.oox.Xpgu4ikFiy', NULL, NULL, 'active', NULL, '2026-03-03 07:54:45', '2026-03-03 07:54:45', NULL, NULL, NULL, NULL),
(30, 3, 'ayman', 'ayman.alnsiri.1k@gmail.com', '$2a$10$4Ew.G4cHovBiFntw.366fuJ9ip8/ovsLIRWUc.WsF5qTOnYsfWsD2', '23722132', NULL, 'active', NULL, '2026-03-03 07:57:15', '2026-03-03 08:01:36', NULL, NULL, '[{\"embedding\":\"vnVgQz41yeg+HL+TvnJX8D9Mt7Y+yC9VPcb94r7G5m6+WLvrPJRHsL5BGA48KvXwvhPlgj4nIUQ+t+mNPestpL8cT2Y9Lcz4v5PB4L5jEkq+q0FCvhtxWT6ewzm+NDDdvYNG+r0zdLS9mm43PgptuL06OOO+X1bDvg3tAD5kVs4/AL5rvuLdTL4MuzI+2+agva/cAD7J8+q+omDDPgHCsz4U4IS+n+RTPmACDDyNcYY/Dc/4PheGXr6cs/g+298iviKjpT4s840/BSQGvgAeGD3a6kK9F/9Yvz8j0b4Tlak+04lOPpyiLL6u4rk+2fqIvIiclD63x6u/HTSSvzEsVD7wi46/FiaPPxydKj32Mz8+7LKGPjS16j7xxhq9/FCsvgNCWr6mHuU/KKXovgtgS76tDQc80MmIv4Q01zzFMKA+0EA1PtYHQL5tAEg9nWmEvig9or48rjk+vqTKvlY6cj0Nvcq+RQZjPl2CBj8YqfA+8kTXPwA37b8AN5s8wdFqvofhmD7cNoO+iDHVPu2FCbyXNNy+iZzevlOV479k56A+QhfvPpI0LDzVszA+009PPyD6iL8oQUQ9EqYwvvebxL5Uyzq+CFkhPwmGnz4tWam/D0WEPkW1Zz64xTa+YyaMPnqr/bwv6qA/KgAwPhxPcD44f5a/b2WJvhW7Sz8scX8\\u003d\",\"capturedAt\":\"2026-03-03T09:01:25.1623699\"},{\"embedding\":\"vYdHFrvA3cg9phjivhfJNj9JvxY/Dpa8vQooer7Z9s698LQsvhvkPrx7KJA9OSA4vbzC9D5xyKo+lWlsPjhs978WLds+ZQhkv4vQSb6+kAa+xPDbvi8mzD6foGi8HCuwPOzOub6Kyye+YbB2PiDa6D44I5K+s7GqvoJWOD4Ld6k+wKM+vo7HnL5rJOg+50sevmOmaz7iqK++VBF+PWMyej4n0LS+NMtuPnu67L4hk0A+66LsPehAqb4nO2o+uQH8vZZXKD4YGx4+4s0yvVotSD5NBUK+BtWHv1Kk+72oVBA+cjBkPqNVbb5D14k+erIuvU6WuT4co0+/Fb1bvxE+5z7r2n6+6d9APzbPJT5GrDQ+2rcYPao3SD71aby9dPW1vI7LrL6+mPo+7g4yviqdVL6qTc+9OsBkv4JIcD5yeGc+6sSWPx0+sL34ATi9leB8vmZuub6CYio+rBDWvUmXJL19YTu+L1kwvckG2D7C/5w+W8tkPuNULL8nH8w9yLIWvo8fLT6kY4e94zYEPoPug7z6XEi+lT2bO8IAIL8FHY4+JtF2Pl/sPL3ppN8+EpjJPyP36r9OaZy+Jqr+vrIdtr4dCRa9qkOwPvyuUL3CZQS++OliPnQKDz6R2qS+O+wUPpoeoL3en/I/QpZCPjQ9T7yu4ni/Rd4/vXpbaj7rDnU\\u003d\",\"capturedAt\":\"2026-03-03T09:01:29.0514848\"},{\"embedding\":\"vl7eHD4hwvs+BWeAvnuAHj9RhLo+46iYPd648L6lw66+JwwmPElMwL5CSAq8uO4wvfacRD4+5Mo+v4DGPgthd78kB1Q9kTs4v46ix75+7sa+sQgUvhfWkD6J0de+QCM3vXYXrb1yJ8q9lEeHPgVnSr0nOsW+ZZkdvfXlUj5LWyE/AV6+vtumsL44dMY+11YjvhcgyD7Ms5++qavxPflVNz47tuO+lf9jPl/cmjxEgqQ/EgmDPa2lY76glrE+ztO8vhmDqj5CSg4++somvgua+j4PB7i9Ut47v0HT4L45f4M+xCyYPrkdIr6kV0I+2R4auwTzID7D6fa/IcW2vy/gpj7jCFC/HacoPxkJsz2mWD4+86soPgFJbD7nK/y+BrNkvfR/cr6o99M/JVn/vgSKX77ABi09JOeGv4STkDxrZ5A+yjQEPtT9ML5rqGo87qR0vgZ8qr5baEI+ue56vl1ijjyaMEi+Z+2PPkQrBj8N6ps+06jTPvu+zL8BYb49buItvmtwYz7ciWW+jHOCPu2Jb7z3B1S+hQXCvkTT0r9aZ1Q+I3iKPqEK6b08roU+yo7sPxzltb8tYpI9RpIgvu4vfL5PN3q9+ptIPwmR6j4M1hy/GF5FPkvo9z7BC+a+U6wuPoh37Dt2/QA/MqLdPgx+8T4N8ty/b8ZFviw+Fj8mZg4\\u003d\",\"capturedAt\":\"2026-03-03T09:01:31.0410885\"},{\"embedding\":\"vYiUirwAWg49rZxgvhhcdj9JvqI/Dpq2vRV3Gr7dB2y99U1wvht3mLycN8A9Onngvcb4ID5xGro+l7hzPjabVb8Vg54+aqCGv4wEHb6/n1m+wuyNvix8xD6g4Ra8FhjAPPSvOb6LfOO+ZOijPiCurD46cGC+tDrYvoJvjD4KmkM+wiaIvo46ar5uk0Y+6WCKvmMfKD7mJ6S+UeBYPWaY5j4moQm+Nm+4PnjOSr4jf0Q+7WsaPe6+T74oiL4+uHAXvZ37Jj4gARE+4jTSvWRScD5J6yi+B4V7v1ScLL2wafY+cOuuPqTVir5EDoQ+ewrQvUvGFD4ajS6/FRxuvxEO1z7u3mK+6bepPzd5Fz5JFjw+3XHwPape4D75BAq9Zfl6vHc5aL6+qSY+79nPvizwa76ppmC9Ny6kv4IJXj51c2M+7C6bPx19O73uN5y9lDc2vl9RRL6DJG0+q1yovUHXKL2F0ru+MTL4vcjWhD7B66g+W7iyPuTYgL8oRIQ9u1cEvo/hLz6kNIm93V87PoGh1b0Hkpq+lq2SO6lHwL8GH+4+KFe+PmAfXr3qjCU+EYTVPyT8r79QAfa+KIIuvrFkEb4iit69n+DgPv6imr3IPHi++NFTPnQJ4T6T1ty+PmCHPpqWTL3b6yQ/QbugPjOExbykW5C/RjIXvYY5sj7tPVo\\u003d\",\"capturedAt\":\"2026-03-03T09:01:33.0172613\"},{\"embedding\":\"vZa5Frxg+8I98HV5vgFVBD9Fobw/Ca2svWUPxr7hezK+GG6uvf9p0L1SxyA9GEi0vgo2VD5lZ1I+n1+NPiSN8b8O2CI+jyaUv4cH+764DD6+rXFUvjAPhz6xj8o8DzbAPXfLXL6Kqfa+dl6JPjv04D542CC+w6zJvnN1dj3W1gY+wJn4vm5RP76Gf3c+7/v6vj5IeD8Db4a+OVXwPXf+1D4BuTG+PzX0Pm3udL4XaYE+7SrgPg3uLL4gABM+saLBvcIySj5SsHU+09novY1yrD424CC+FO3ev1uSdb3G/no+Xx9kPr95sb5FIow+bj76vS9eAj4T0Kq/Ewn/vwkyLT8BDLS+34xFPzbJxj5Tadg+3O9yPZecrD8Ar2O86vfyvKxfKr60loY+8a1JviAtgr6o1a69H1tov3U+qz6KeWM+8PAqPyCXTL3Jz8a9QOFZvi3tNL58izk+nZNgvSwAYr2oEJ++NzwKvba04j63wJo+WlZ0PvbWXr8zadI9YHclvqwMBz6ptJa9ZhkcPmVFNL2zSEK+m1azu3zbAL8Jj8A+MF+fPnJRSL4JWgY+ClMnPydwm79Pjga+Pw76vq7OU74hXSK9JQ5WPwMmNr4OhgS+8ZAqPm8gGj6M0He+ONAYPpXmLL2oDz4/MvjFPjojLbwMDKi/USwPvY/aDj7tTzA\\u003d\",\"capturedAt\":\"2026-03-03T09:01:35.452974\"}]', '2026-03-03 08:01:36'),
(31, 4, 'Guest_eeff6ab1', 'guest_eeff6ab1-6fcf-4509-9fb9-86ba031acead@agricloud.com', '$2a$10$Z3GGYGSS0ynlYDL7d4cv8.zqYhVY6a9.dv5VLSbg6VENu3HnRQOtK', NULL, NULL, 'active', NULL, '2026-03-03 08:14:43', '2026-03-03 08:14:43', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `posts`
--
ALTER TABLE `posts` ADD FULLTEXT KEY `idx_search` (`title`,`content`);

--
-- Indexes for table `products`
--
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`description`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `farms`
--
ALTER TABLE `farms`
  ADD CONSTRAINT `farms_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `farms_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fields`
--
ALTER TABLE `fields`
  ADD CONSTRAINT `fields_ibfk_1` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participations`
--
ALTER TABLE `participations`
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
