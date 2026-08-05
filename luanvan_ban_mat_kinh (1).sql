-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 04, 2026 at 02:55 PM
-- Server version: 9.1.0
-- PHP Version: 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `luanvan_ban_mat_kinh`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE IF NOT EXISTS `banners` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` enum('DESKTOP','MOBILE','BOTH') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOTH',
  `position` enum('HOME_SLIDER','HOME_BANNER_1','HOME_BANNER_2','CATEGORY_BANNER','PRODUCT_BANNER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` int NOT NULL DEFAULT '1',
  `start_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_at` datetime DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` bigint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_banners_created_by` (`created_by`),
  KEY `idx_banners_status_position_priority` (`status`,`position`,`priority`),
  KEY `idx_banners_dates` (`start_at`,`end_at`)
) ;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `image_url`, `link_url`, `platform`, `position`, `priority`, `start_at`, `end_at`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1001, 'Banner 1', 'banner-kinh-1.jpg', '#', 'BOTH', 'HOME_SLIDER', 1, '2026-04-03 09:35:39', NULL, 'ACTIVE', NULL, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1002, 'Banner', 'banner-kinh-2.png', 'index.php?url=cua-hang', 'BOTH', 'HOME_BANNER_1', 2, '2026-04-03 09:35:39', NULL, 'ACTIVE', NULL, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1003, 'Banner 2', 'banner-kinh-3.jpg', 'lien-he', 'BOTH', 'HOME_BANNER_2', 3, '2026-04-03 09:35:39', NULL, 'ACTIVE', NULL, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1004, 'Banner 3', 'banner-kinh-4.png', 'cua-hang', 'BOTH', 'CATEGORY_BANNER', 4, '2026-04-03 09:35:39', NULL, 'ACTIVE', NULL, '2026-06-10 01:56:39', '2026-06-10 01:56:39');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_brands_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `logo_url`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'RayBan', NULL, 'Thương hiệu kính Ray-Ban chính hãng', 'ACTIVE', '2026-06-10 01:56:38', '2026-06-10 01:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `parent_id` bigint DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `uk_categories_parent_name` (`parent_id`,`name`),
  KEY `idx_categories_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2004 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `image_url`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Kính mát nam', 'kinh-mat-nam', NULL, 'Danh mục kính mát nam Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(2, NULL, 'Kính mát nữ', 'kinh-mat-nu', NULL, 'Danh mục kính mát nữ Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(3, NULL, 'Kính mát unisex', 'kinh-mat-unisex', NULL, 'Danh mục kính mát unisex Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(4, NULL, 'Kính thời trang nam', 'kinh-thoi-trang-nam', NULL, 'Danh mục kính thời trang nam Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(5, NULL, 'Kính thời trang nữ', 'kinh-thoi-trang-nu', NULL, 'Danh mục kính thời trang nữ Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(6, NULL, 'Kính thời trang unisex', 'kinh-thoi-trang-unisex', NULL, 'Danh mục kính thời trang unisex Ray-Ban', 'ACTIVE', '2026-04-03 09:00:00', '2026-06-01 09:00:00'),
(7, NULL, 'Kính mát', 'kinh-mat', NULL, 'Các dòng kính mát chống tia UV', 'ACTIVE', '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(8, NULL, 'Gọng kính cận', 'gong-kinh-can', NULL, 'Gọng kính dùng lắp tròng cận', 'ACTIVE', '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1001, NULL, 'Chưa có danh mục', 'chua-co-danh-muc', 'no-image.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1002, NULL, 'Nam', 'nam', 'kinhmat-1.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1004, NULL, 'Nữ', 'nu', 'kinhmat-2.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1006, NULL, 'Gọng kính', 'gong-kinh', 'kinhmat-12.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1016, NULL, 'Kính mát hàng hiệu', 'kinh-mat-hang-hieu', 'kinhmat-3.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1017, NULL, 'Kính mát kim loại', 'kinh-mat-kim-loai', 'kinhmat-34.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1019, NULL, 'Tròng kính đa tròng', 'trong-kinh-da-trong', 'kinhmat-16.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1020, NULL, 'Gọng Kính Cận Nữ', 'gong-kinh-can-nu', 'kinhmat-11.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1021, NULL, 'Gọng Kính Cận Nam', 'gong-kinh-can-nam', 'kinhmat-7.jpg', NULL, 'ACTIVE', '2026-06-10 01:56:39', '2026-06-10 01:56:39');

-- --------------------------------------------------------

--
-- Table structure for table `colors`
--

DROP TABLE IF EXISTS `colors`;
CREATE TABLE IF NOT EXISTS `colors` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RED, BLACK...',
  `hex_code` char(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '#RRGGBB',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `hex_code` (`hex_code`)
) ;

--
-- Dumping data for table `colors`
--

INSERT INTO `colors` (`id`, `name`, `code`, `hex_code`, `created_at`) VALUES
(1, 'Đen', 'BLACK', '#000000', '2026-06-10 01:56:38'),
(2, 'Trắng', 'WHITE', '#FFFFFF', '2026-06-10 01:56:38'),
(3, 'Đỏ', 'RED', '#FF0000', '2026-06-10 01:56:38'),
(4, 'Xanh dương', 'BLUE', '#0000FF', '2026-06-10 01:56:38'),
(5, 'Nâu', 'BROWN', '#8B4513', '2026-06-10 01:56:38'),
(6, 'Vàng', 'YELLOW', '#FFFF00', '2026-06-10 01:56:38'),
(7, 'Xanh gradient', 'BLUE_GRADIENT', '#4F8AC9', '2026-06-10 01:56:38'),
(8, 'Nâu gradient', 'BROWN_GRADIENT', '#A47551', '2026-06-10 01:56:38'),
(9, 'Xanh lá đậm', 'DARK_GREEN', '#006400', '2026-06-10 01:56:38'),
(10, 'Xanh lá', 'GREEN', '#008000', '2026-06-10 01:56:38'),
(11, 'Xám gradient', 'GREY_GRADIENT', '#9CA3AF', '2026-06-10 01:56:38'),
(12, 'Xám nhạt', 'LIGHT_GREY', '#D1D5DB', '2026-06-10 01:56:38'),
(13, 'Cam gradient', 'ORANGE_GRADIENT', '#FFA500', '2026-06-10 01:56:38'),
(14, 'Bạc', 'SILVER', '#C0C0C0', '2026-06-10 01:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `frame_materials`
--

DROP TABLE IF EXISTS `frame_materials`;
CREATE TABLE IF NOT EXISTS `frame_materials` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kim loại, Nhựa, Titanium...',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `frame_materials`
--

INSERT INTO `frame_materials` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'METAL', 'Kim loại', '2026-06-10 01:56:38'),
(2, 'PLASTIC', 'Nhựa', '2026-06-10 01:56:38'),
(3, 'TITANIUM', 'Titanium', '2026-06-10 01:56:38'),
(4, 'ACETATE', 'Acetate', '2026-06-10 01:56:38'),
(5, 'TR90', 'TR-90', '2026-06-10 01:56:38'),
(6, 'FLEX', 'Flex', '2026-06-10 01:56:38'),
(7, 'ACETATE_METAL', 'Acetate kết hợp kim loại', '2026-04-03 09:10:00'),
(8, 'NYLON', 'Nylon', '2026-04-03 09:10:00'),
(9, 'RUBBER_NYLON', 'Nylon phủ cao su', '2026-04-03 09:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `frame_shapes`
--

DROP TABLE IF EXISTS `frame_shapes`;
CREATE TABLE IF NOT EXISTS `frame_shapes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tròn, Vuông, Mắt mèo...',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `frame_shapes`
--

INSERT INTO `frame_shapes` (`id`, `code`, `name`, `created_at`) VALUES
(1, 'ROUND', 'Tròn', '2026-06-10 01:56:38'),
(2, 'SQUARE', 'Vuông', '2026-06-10 01:56:38'),
(3, 'OVAL', 'Oval', '2026-06-10 01:56:38'),
(4, 'CAT_EYE', 'Mắt mèo', '2026-06-10 01:56:38'),
(5, 'PILOT', 'Pilot', '2026-06-10 01:56:38'),
(6, 'BUTTERFLY', 'Butterfly', '2026-06-10 01:56:38'),
(7, 'RECTANGLE', 'Rectangle', '2026-06-10 01:56:38'),
(8, 'GEOMETRIC', 'Geometric', '2026-06-10 01:56:38'),
(9, 'AVIATOR', 'Aviator', '2026-04-03 09:05:00'),
(10, 'BROWLINE', 'Browline', '2026-04-03 09:05:00'),
(11, 'PHANTOS', 'Phantos', '2026-04-03 09:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `home_layouts`
--

DROP TABLE IF EXISTS `home_layouts`;
CREATE TABLE IF NOT EXISTS `home_layouts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `section_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key` (`section_key`)
) ;

--
-- Dumping data for table `home_layouts`
--

INSERT INTO `home_layouts` (`id`, `section_key`, `section_name`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1001, 'banner', 'Banner trang chủ', 1, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1002, 'categories', 'Danh mục nổi bật', 2, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1003, 'new_products', 'Sản phẩm mới', 3, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1004, 'best_sellers', 'Sản phẩm bán chạy', 4, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1005, 'brands', 'Thương hiệu', 5, 0, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1006, 'news', 'Tin tức', 6, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1007, 'services', 'Dịch vụ', 7, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00'),
(1008, 'support', 'Hỗ trợ', 8, 1, '2026-06-10 01:56:39', '2026-07-11 10:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `inventories`
--

DROP TABLE IF EXISTS `inventories`;
CREATE TABLE IF NOT EXISTS `inventories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint NOT NULL,
  `variant_id` bigint NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `min_stock_level` int DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventories_warehouse_variant` (`warehouse_id`,`variant_id`),
  KEY `idx_inventories_variant` (`variant_id`),
  KEY `idx_inventories_warehouse` (`warehouse_id`),
  KEY `idx_inventories_variant_warehouse` (`variant_id`,`warehouse_id`)
) ;

--
-- Dumping data for table `inventories`
--

INSERT INTO `inventories` (`id`, `warehouse_id`, `variant_id`, `quantity`, `min_stock_level`, `updated_at`) VALUES
(1, 1, 1001, 35, 10, '2026-04-15 09:00:00'),
(2, 1, 1002, 39, 10, '2026-04-19 13:18:00'),
(3, 1, 1006, 43, 10, '2026-04-23 17:36:00'),
(4, 1, 1014, 47, 10, '2026-04-24 13:54:00'),
(5, 1, 1015, 51, 10, '2026-04-28 18:12:00'),
(6, 1, 1016, 55, 10, '2026-05-02 17:30:00'),
(7, 1, 1017, 59, 10, '2026-05-03 12:48:00'),
(8, 1, 1018, 63, 10, '2026-05-07 17:06:00'),
(9, 1, 1020, 67, 10, '2026-05-11 13:24:00'),
(10, 1, 1021, 71, 10, '2026-05-12 16:42:00'),
(11, 1, 1023, 75, 10, '2026-06-16 19:00:42'),
(12, 1, 1024, 79, 10, '2026-05-20 11:18:00'),
(13, 1, 1025, 38, 10, '2026-05-21 15:36:00'),
(14, 1, 1026, 42, 10, '2026-05-25 19:54:00'),
(15, 1, 1027, 46, 10, '2026-05-29 16:12:00'),
(16, 1, 1028, 49, 10, '2026-08-04 11:06:52'),
(17, 1, 1029, 54, 10, '2026-06-03 11:48:00'),
(18, 1, 1030, 59, 10, '2026-08-04 15:30:43'),
(19, 1, 1031, 62, 10, '2026-06-03 10:06:00'),
(20, 1, 1032, 66, 10, '2026-08-04 15:30:43'),
(21, 1, 1036, 70, 10, '2026-05-27 14:00:00'),
(22, 1, 1037, 74, 10, '2026-05-28 18:18:00'),
(23, 1, 1038, 78, 10, '2026-06-16 19:00:42'),
(24, 1, 1039, 26, 10, '2026-08-04 15:30:43'),
(25, 1, 1040, 19, 10, '2026-08-04 19:58:12'),
(28, 1, 2000, 22, 10, '2026-08-04 15:40:30');

-- --------------------------------------------------------

--
-- Table structure for table `lens_sizes`
--

DROP TABLE IF EXISTS `lens_sizes`;
CREATE TABLE IF NOT EXISTS `lens_sizes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '50, 52, 54...',
  `bridge_width` int DEFAULT NULL COMMENT 'Cầu kính mm',
  `temple_length` int DEFAULT NULL COMMENT 'Càng kính mm',
  `lens_width` int DEFAULT NULL COMMENT 'Chiều ngang tròng mm',
  `lens_height` int DEFAULT NULL COMMENT 'Chiều cao tròng mm',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lens_sizes`
--

INSERT INTO `lens_sizes` (`id`, `name`, `bridge_width`, `temple_length`, `lens_width`, `lens_height`, `created_at`) VALUES
(1, '50', 18, 140, 50, 42, '2026-06-10 01:56:38'),
(2, '52', 18, 145, 52, 44, '2026-06-10 01:56:38'),
(3, '54', 19, 145, 54, 46, '2026-06-10 01:56:38'),
(4, '56', 20, 150, 56, 48, '2026-06-10 01:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(4, '2026_07_10_013605_add_two_factor_columns_to_users_table', 1),
(6, '2026_07_10_034500_add_zalopay_to_payment_enums', 1),
(7, '0001_01_01_000001_create_cache_table', 2),
(8, '0001_01_01_000002_create_jobs_table', 2),
(9, '2026_07_10_013652_create_personal_access_tokens_table', 2),
(10, '2026_07_10_041000_add_paypal_to_payment_enums', 3),
(11, '2026_07_10_050000_add_sepay_to_payment_enums', 4),
(12, '2026_07_10_060000_keep_only_cod_and_vnpay_payment_methods', 5),
(13, '2026_08_04_110000_add_cancel_confirmation_fields_to_orders_table', 6),
(14, '2026_08_04_120000_create_try_on_snapshots_table', 7),
(15, '2026_08_04_113000_add_order_confirmation_email_sent_at_to_orders_table', 8),
(16, '2026_08_04_130000_add_performance_indexes', 9),
(17, '2026_08_04_151000_merge_return_warehouses_into_normal_type', 10),
(18, '2026_08_04_152000_move_reserved_quantity_into_quantity_and_drop_column', 11),
(19, '2026_08_04_153000_keep_only_import_export_and_sale_out_stock_transaction_types', 12),
(20, '2026_08_04_154000_rename_legacy_stock_transaction_codes_to_pn', 13);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint NOT NULL,
  `address_id` bigint DEFAULT NULL,
  `recipient_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_address` varchar(700) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('COD','VNPAY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COD',
  `payment_status` enum('UNPAID','PAID','FAILED','REFUNDED','PARTIALLY_REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNPAID',
  `status` enum('PENDING','AWAITING_PAYMENT','CONFIRMED','DELAY','DELIVERING','DELIVERED','CANCELLED','RETURN_PENDING','RETURNED','EXCHANGED','LOST_IN_TRANSIT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `subtotal_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping_fee` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `promotion_id` bigint DEFAULT NULL,
  `note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_by` bigint DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cancel_confirmation_token_hash` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `cancel_requested_at` timestamp NULL DEFAULT NULL,
  `cancel_confirmed_at` timestamp NULL DEFAULT NULL,
  `order_confirmation_email_sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `fk_orders_address` (`address_id`),
  KEY `fk_orders_promotion` (`promotion_id`),
  KEY `fk_orders_confirmed_by` (`confirmed_by`),
  KEY `idx_orders_user_created` (`user_id`,`created_at`),
  KEY `idx_orders_status_created` (`status`,`created_at`),
  KEY `idx_orders_payment_created` (`payment_method`,`created_at`),
  KEY `idx_orders_recipient_phone` (`recipient_phone`)
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `address_id`, `recipient_name`, `recipient_phone`, `shipping_address`, `payment_method`, `payment_status`, `status`, `subtotal_amount`, `discount_amount`, `shipping_fee`, `total_amount`, `promotion_id`, `note`, `cancelled_reason`, `confirmed_by`, `delivered_at`, `created_at`, `updated_at`, `cancel_confirmation_token_hash`, `cancel_reason`, `cancel_requested_at`, `cancel_confirmed_at`, `order_confirmation_email_sent_at`) VALUES
(1010, 'ORD20260401090000010', 1006, NULL, 'Di Tiểu Bảo', '0909135986', 'Can tho', 'COD', 'UNPAID', 'CONFIRMED', 300000.00, 0.00, 0.00, 300000.00, NULL, '', NULL, NULL, NULL, '2026-04-01 09:00:00', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1011, 'ORD20260402062514012', 1006, NULL, 'Di Tiểu Bảo', '0909135986', 'Can tho', 'COD', 'UNPAID', 'DELIVERING', 340000.00, 0.00, 0.00, 340000.00, NULL, 'Gói sách kĩ giúp em lần trước mua bị rách', NULL, NULL, NULL, '2026-04-02 06:25:14', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1012, 'ORD20260403035028014', 1007, NULL, 'Lê Châu Khả Hi', '0336216654', 'Kiên Giang', 'COD', 'UNPAID', 'DELIVERING', 280000.00, 0.00, 0.00, 280000.00, NULL, 'Hello my friend', NULL, NULL, NULL, '2026-04-03 03:50:28', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1013, 'ORD20260404011542016', 1009, NULL, 'Trần Văn A', '0909135969', 'Cái Răng, Cần Thơ', 'COD', 'UNPAID', 'CONFIRMED', 720000.00, 0.00, 0.00, 720000.00, NULL, 'Đóng gói hàng kĩ', NULL, NULL, NULL, '2026-04-04 01:15:42', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1014, 'ORD20260404224056018', 1006, NULL, 'Di Tiểu Bảo', '0909135986', 'Can tho', 'COD', 'UNPAID', 'DELIVERED', 1220000.00, 0.00, 0.00, 1220000.00, NULL, 'hi', NULL, NULL, '2026-04-07 22:40:56', '2026-04-04 22:40:56', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1015, 'ORD20260405200610020', 1006, NULL, 'Di Tiểu Bảo', '0909135986', 'Can tho', 'COD', 'UNPAID', 'DELIVERED', 320000.00, 0.00, 0.00, 320000.00, NULL, 'Chúc 1 ngày vui', NULL, NULL, '2026-04-08 20:06:10', '2026-04-05 20:06:10', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1016, 'ORD20260406160124022', 1006, NULL, 'Di Tiểu Bảo', '0909135986', 'Can tho', 'COD', 'UNPAID', 'DELIVERING', 540000.00, 0.00, 0.00, 540000.00, NULL, 'Mua hang 29/11/2023', NULL, NULL, NULL, '2026-04-06 16:01:24', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1017, 'ORD20260407132638024', 1010, NULL, 'Mai Hảo Long', '0909135985', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 376000.00, 0.00, 0.00, 376000.00, NULL, 'Gói hàng cẩn thận giao nhanh giúp tôi ', NULL, NULL, NULL, '2026-04-07 13:26:38', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1018, 'ORD20260408105152026', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Cái Răng, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 246000.00, 0.00, 0.00, 246000.00, NULL, 'Giao hàng nhanh nha, đang cần gấp', NULL, NULL, NULL, '2026-04-08 10:51:52', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1019, 'ORD20260409081706028', 1006, NULL, 'Di Tiểu Bảo', '0909246546', 'Quận Đống Đa, Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 102000.00, 0.00, 0.00, 102000.00, NULL, 'Mong là sách đọc hay', NULL, NULL, '2026-04-12 08:17:06', '2026-04-09 08:17:06', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1022, 'ORD20260410054220032', 1011, NULL, 'Đặng tuấn Kiệt', '0336246546', 'Sóc Trăng', 'COD', 'UNPAID', 'DELIVERING', 230000.00, 0.00, 0.00, 230000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, NULL, '2026-04-10 05:42:20', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1023, 'ORD20260411013734034', 1011, NULL, 'Đặng tuấn Kiệt', '0909006764', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 438000.00, 0.00, 0.00, 438000.00, NULL, 'Hello', NULL, NULL, NULL, '2026-04-11 01:37:34', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1024, 'ORD20260411230248036', 1011, NULL, 'Đặng tuấn Kiệt', '0909006764', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 197000.00, 0.00, 0.00, 197000.00, NULL, '', NULL, NULL, NULL, '2026-04-11 23:02:48', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1025, 'ORD20260412202802038', 1011, NULL, 'Đặng tuấn Kiệt', '0909006764', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 88000.00, 0.00, 0.00, 88000.00, NULL, '', NULL, NULL, NULL, '2026-04-12 20:28:02', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1026, 'ORD20260413175316040', 1011, NULL, 'Đặng tuấn Kiệt', '0909006764', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 352000.00, 0.00, 0.00, 352000.00, NULL, '', NULL, NULL, NULL, '2026-04-13 17:53:16', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1027, 'ORD20260414151830042', 1010, NULL, 'Mai Hảo Long', '0909135985', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'CONFIRMED', 209000.00, 0.00, 0.00, 209000.00, NULL, '', NULL, NULL, NULL, '2026-04-14 15:18:30', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1028, 'ORD20260415124344044', 1010, NULL, 'Mai Hảo Long', '0909135399', 'Long Hồ, Vĩnh Long', 'COD', 'UNPAID', 'PENDING', 180000.00, 0.00, 0.00, 180000.00, NULL, 'Mua hàng cho bạn ở quê', NULL, NULL, NULL, '2026-04-15 12:43:44', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1029, 'ORD20260416083858046', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'DELIVERING', 306000.00, 0.00, 0.00, 306000.00, NULL, '', NULL, NULL, NULL, '2026-04-16 08:38:58', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1030, 'ORD20260417060412048', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Quận Mỹ Đình, Hà Nội', 'COD', 'UNPAID', 'CONFIRMED', 378000.00, 0.00, 0.00, 378000.00, NULL, 'Hello 2023', NULL, NULL, NULL, '2026-04-17 06:04:12', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1031, 'ORD20260418032926050', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Long Biên, Hà Nội', 'COD', 'UNPAID', 'PENDING', 353000.00, 0.00, 0.00, 353000.00, NULL, 'Giao hàng nhanh giúp tôi', NULL, NULL, NULL, '2026-04-18 03:29:26', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1032, 'ORD20260419005440052', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 100000.00, 0.00, 0.00, 100000.00, NULL, 'Giao nhanh', NULL, NULL, NULL, '2026-04-19 00:54:40', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1033, 'ORD20260419221954054', 1011, NULL, 'Đặng tuấn Kiệt', '0336216546', 'Quận Cầu Giấy Hà Nội', 'COD', 'UNPAID', 'PENDING', 788000.00, 0.00, 0.00, 788000.00, NULL, 'Sách hay quóaa', NULL, NULL, NULL, '2026-04-19 22:19:54', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1034, 'ORD20260420181508056', 1010, NULL, 'Mai Hảo Long', '0909135329', 'Quận Cầu Giấy Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 428000.00, 0.00, 0.00, 428000.00, NULL, 'Giao hàng nhanh giúp tôi', NULL, NULL, '2026-04-23 18:15:08', '2026-04-20 18:15:08', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1035, 'ORD20260421154022058', 1010, NULL, 'Mai Hảo Long', '0336216546', 'Quận Cầu Giấy Hà Nội', 'COD', 'UNPAID', 'CONFIRMED', 519000.00, 0.00, 0.00, 519000.00, NULL, 'Giao hàng nhanh giúp tôi', NULL, NULL, NULL, '2026-04-21 15:40:22', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1036, 'ORD20260422130536060', 1006, NULL, 'Di Tiểu Bảo', '0336246546', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 1847000.00, 0.00, 0.00, 1847000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, NULL, '2026-04-22 13:05:36', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1037, 'ORD20260423103050062', 1006, NULL, 'Di Tiểu Bảo', '0909135985', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'DELIVERED', 100000.00, 0.00, 0.00, 100000.00, NULL, 'Hello', NULL, NULL, '2026-04-26 10:30:50', '2026-04-23 10:30:50', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1038, 'ORD20260424075604064', 1006, NULL, 'Di Tiểu Bảo', '0909135985', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 228000.00, 0.00, 0.00, 228000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, NULL, '2026-04-24 07:56:04', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1039, 'ORD20260425052118066', 1007, NULL, 'Lê Châu Khả Hi', '0336216123', 'Số 14 Nguyễn Công Trứ, phường Vĩnh Thanh, thành phố Rạch Giá, tỉnh Kiên Giang', 'COD', 'UNPAID', 'CONFIRMED', 362000.00, 0.00, 0.00, 362000.00, NULL, 'Sách hay', NULL, NULL, NULL, '2026-04-25 05:21:18', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1040, 'ORD20260426011632068', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Số 14 Nguyễn Công Trứ, phường Vĩnh Thanh, thành phố Rạch Giá, tỉnh Kiên Giang', 'COD', 'UNPAID', 'PENDING', 517000.00, 0.00, 0.00, 517000.00, NULL, 'Đóng gói hàng kĩ', NULL, NULL, NULL, '2026-04-26 01:16:32', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1041, 'ORD20260426224146070', 1008, NULL, 'Admin', '0336246546', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 800000.00, 0.00, 0.00, 800000.00, NULL, 'Gói hàng kĩ', NULL, NULL, NULL, '2026-04-26 22:41:46', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1042, 'ORD20260427200700072', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Quận Cầu Giấy Hà Nội', 'COD', 'UNPAID', 'CONFIRMED', 287000.00, 0.00, 0.00, 287000.00, NULL, 'Mua hàng nè hihi', NULL, NULL, NULL, '2026-04-27 20:07:00', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1043, 'ORD20260428173214074', 1010, NULL, 'Mai Hảo Long', '0336246546', 'Cái Răng, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 306000.00, 0.00, 0.00, 306000.00, NULL, 'Gói hàng kĩ', NULL, NULL, NULL, '2026-04-28 17:32:14', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1044, 'ORD20260429145728076', 1010, NULL, 'Mai Hảo Long', '0909135329', 'Số 14 Nguyễn Công Trứ, phường Vĩnh Thanh, thành phố Rạch Giá, tỉnh Kiên Giang', 'COD', 'UNPAID', 'PENDING', 339000.00, 0.00, 0.00, 339000.00, NULL, 'Giao hàng nhanh nha', NULL, NULL, NULL, '2026-04-29 14:57:28', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1045, 'ORD20260430105242078', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Số 14 thành phố Rạch Giá, tỉnh Kiên Giang', 'COD', 'UNPAID', 'PENDING', 432000.00, 0.00, 0.00, 432000.00, NULL, 'Test mua hàng 13/12/2023', NULL, NULL, NULL, '2026-04-30 10:52:42', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1046, 'ORD20260501081756080', 1010, NULL, 'Mai Hảo Long', '0909135985', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 100000.00, 0.00, 0.00, 100000.00, NULL, 'Hảo mua hàng nè', NULL, NULL, NULL, '2026-05-01 08:17:56', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1047, 'ORD20260502054310082', 1010, NULL, 'Mai Hảo Long', '0909135985', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 500000.00, 0.00, 0.00, 500000.00, NULL, '', NULL, NULL, NULL, '2026-05-02 05:43:10', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1048, 'ORD20260503030824084', 1007, NULL, 'Lê Châu Khả Hi', '0336216654', 'Kiên Giang', 'COD', 'UNPAID', 'PENDING', 547000.00, 0.00, 0.00, 547000.00, NULL, '', NULL, NULL, NULL, '2026-05-03 03:08:24', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1049, 'ORD20260504003338086', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 126000.00, 0.00, 0.00, 126000.00, NULL, '', NULL, NULL, NULL, '2026-05-04 00:33:38', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1050, 'ORD20260504202852088', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Cần Thơ', 'COD', 'UNPAID', 'PENDING', 640000.00, 0.00, 0.00, 640000.00, NULL, '', NULL, NULL, NULL, '2026-05-04 20:28:52', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1051, 'ORD20260505175406090', 1006, NULL, 'Di Tiểu Bảo', '0336216546', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 300000.00, 0.00, 0.00, 300000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, NULL, '2026-05-05 17:54:06', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1052, 'ORD20260506151920092', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 326000.00, 0.00, 0.00, 326000.00, NULL, 'Giao nhanh', NULL, NULL, NULL, '2026-05-06 15:19:20', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1053, 'ORD20260507124434094', 1006, NULL, 'Di Tiểu Bảo', '0909135985', 'Anh Khánh, Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 384000.00, 0.00, 0.00, 384000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, NULL, '2026-05-07 12:44:34', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1054, 'ORD20260508100948096', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 408000.00, 0.00, 0.00, 408000.00, NULL, 'oki', NULL, NULL, NULL, '2026-05-08 10:09:48', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1055, 'ORD20260509073502098', 1006, NULL, 'Di Tiểu Bảo', '0909135329', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'DELIVERED', 366000.00, 0.00, 0.00, 366000.00, NULL, 'Đóng hàng kĩ', NULL, NULL, '2026-05-12 07:35:02', '2026-05-09 07:35:02', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1056, 'ORD20260510033016100', 1006, NULL, 'Di Tiểu Bảo', '0909135555', 'Hà Nội', 'COD', 'UNPAID', 'PENDING', 326000.00, 0.00, 0.00, 326000.00, NULL, 'GIAO NHANH NHA', NULL, NULL, NULL, '2026-05-10 03:30:16', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1057, 'ORD20260511005530102', 1006, NULL, 'Di Tiểu Bảo', '0909199999', 'xin chào  0909199999', 'COD', 'UNPAID', 'PENDING', 126000.00, 0.00, 0.00, 126000.00, NULL, 'Gói hàng kĩ', NULL, NULL, NULL, '2026-05-11 00:55:30', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1058, 'ORD20260511222044104', 1016, NULL, 'Tran Van A', '0909789456', 'Quận 1 HCM', 'COD', 'UNPAID', 'DELIVERED', 351000.00, 0.00, 0.00, 351000.00, NULL, 'giao hàng nhanh', NULL, NULL, '2026-05-14 22:20:44', '2026-05-11 22:20:44', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1059, 'ORD20260512194558106', 1016, NULL, 'Tran Van A', '0909123000', 'Quận 1, HCM', 'COD', 'UNPAID', 'DELIVERING', 500000.00, 0.00, 0.00, 500000.00, NULL, 'giao hàng nhanh', NULL, NULL, NULL, '2026-05-12 19:45:58', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1060, 'ORD20260513171112108', 1016, NULL, 'Tran Van A', '0909999999', 'Cầu giấy Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 188000.00, 0.00, 0.00, 188000.00, NULL, 'Giao hàng nhanh', NULL, NULL, '2026-05-16 17:11:12', '2026-05-13 17:11:12', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1061, 'ORD20260514130626110', 1016, NULL, 'Tran Van A', '0909123456', 'Hẻm 30 Ninh Châu Nam Định', 'COD', 'UNPAID', 'DELIVERING', 215000.00, 0.00, 0.00, 215000.00, NULL, 'Giao hàng gấp giúp  tôi, cảm ơn.', NULL, NULL, NULL, '2026-05-14 13:06:26', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1062, 'ORD20260515103140112', 1017, NULL, 'Trần Văn C', '0336789123', 'Quận 1, HCM', 'COD', 'UNPAID', 'DELIVERED', 535000.00, 0.00, 0.00, 535000.00, NULL, 'Giao hàng nhanh nha', NULL, NULL, '2026-05-18 10:31:40', '2026-05-15 10:31:40', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1063, 'ORD20260516075654114', 1017, NULL, 'Trần Văn C', '0909123456', 'Cau Giay, Ha Noi', 'COD', 'UNPAID', 'CONFIRMED', 900000.00, 0.00, 0.00, 900000.00, NULL, 'Giao nhanh giúp tôi', NULL, NULL, NULL, '2026-05-16 07:56:54', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1064, 'ORD20260517052208116', 1018, NULL, 'Nguyễn Bảo Ngọc', '0336123456', 'Quân 1 HCM', 'COD', 'UNPAID', 'CONFIRMED', 130000.00, 0.00, 0.00, 130000.00, NULL, 'Giao hàng nhanh nha shop ', NULL, NULL, NULL, '2026-05-17 05:22:08', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1065, 'ORD20260518024722118', 1018, NULL, 'Nguyễn Bảo Ngọc', '0336123456', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'CONFIRMED', 155000.00, 0.00, 0.00, 155000.00, NULL, 'Giao hàng nhanh nhoa, thanks shop', NULL, NULL, NULL, '2026-05-18 02:47:22', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1066, 'ORD20260519001236120', 1019, NULL, 'Trần Bảo Anh', '0336789456', 'Quan 1, HCM', 'COD', 'UNPAID', 'DELIVERED', 355000.00, 0.00, 0.00, 355000.00, NULL, 'Giao hành nhanh nha shop đói quá hihi', NULL, NULL, '2026-05-22 00:12:36', '2026-05-19 00:12:36', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1067, 'ORD20260519200750122', 1019, NULL, 'Trần Bảo Anh', '0336456789', 'Cau Giay, Hà Nội', 'COD', 'UNPAID', 'CONFIRMED', 210000.00, 0.00, 0.00, 210000.00, NULL, 'Giao nhanh nhoaaa', NULL, NULL, NULL, '2026-05-19 20:07:50', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1068, 'ORD20260520173304124', 1020, NULL, 'Trần Anh Quốc', '0336789113', 'Cầu Giay, Ha Noi', 'COD', 'UNPAID', 'DELIVERED', 215000.00, 0.00, 0.00, 215000.00, NULL, 'Giao hàng nhanh. Thanks shop', NULL, NULL, '2026-05-23 17:33:04', '2026-05-20 17:33:04', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1069, 'ORD20260521145818126', 1020, NULL, 'Trần Anh Quốc', '0336115987', 'Quận 7, HCM', 'COD', 'UNPAID', 'DELIVERING', 340000.00, 0.00, 0.00, 340000.00, NULL, 'Giao hàng nhanh. Thanks shop hihi', NULL, NULL, NULL, '2026-05-21 14:58:18', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1070, 'ORD20260522122332128', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 403000.00, 0.00, 0.00, 403000.00, NULL, '', NULL, NULL, NULL, '2026-05-22 12:23:32', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1079, 'ORD20260523094846138', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 75000.00, 0.00, 0.00, 75000.00, NULL, '', NULL, NULL, NULL, '2026-05-23 09:48:46', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1080, 'ORD20260524054400140', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 205000.00, 0.00, 0.00, 205000.00, NULL, '', NULL, NULL, NULL, '2026-05-24 05:44:00', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1081, 'ORD20260525030914142', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 343000.00, 0.00, 0.00, 343000.00, NULL, '', NULL, NULL, NULL, '2026-05-25 03:09:14', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1082, 'ORD20260526003428144', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 179000.00, 0.00, 0.00, 179000.00, NULL, '', NULL, NULL, NULL, '2026-05-26 00:34:28', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1083, 'ORD20260526215942146', 1021, NULL, 'Nguyễn Văn Long', '0336123654', 'Quận 1, HCM', 'COD', 'UNPAID', 'DELIVERING', 112000.00, 0.00, 0.00, 112000.00, NULL, 'GIAO HÀNG NHANH NHA SHOP', NULL, NULL, NULL, '2026-05-26 21:59:42', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1084, 'ORD20260527192456148', 1021, NULL, 'Nguyễn Văn Long', '0336123654', 'Cầu Giay, Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 315500.00, 0.00, 0.00, 315500.00, NULL, 'Giao nhanh nha shop hihi', NULL, NULL, '2026-05-30 19:24:56', '2026-05-27 19:24:56', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1086, 'ORD20260528152010151', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 19500.00, 0.00, 0.00, 19500.00, NULL, '', NULL, NULL, NULL, '2026-05-28 15:20:10', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1087, 'ORD20260529124524153', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'CONFIRMED', 53000.00, 0.00, 0.00, 53000.00, NULL, '', NULL, NULL, NULL, '2026-05-29 12:45:24', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1090, 'ORD20260530101038157', 1022, NULL, 'Nguyen Văn AN', '0336987123', 'Cau Giay, Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 80000.00, 0.00, 0.00, 80000.00, NULL, 'Giao hàng nhanh giúp tôi nha', NULL, NULL, '2026-06-02 10:10:38', '2026-05-30 10:10:38', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1091, 'ORD20260531073552159', 1022, NULL, 'Nguyen Văn AN', '0909136789', 'Quận 2, HCM', 'COD', 'UNPAID', 'DELIVERING', 39000.00, 0.00, 0.00, 39000.00, NULL, 'Giao nhanh nha shop hihi', NULL, NULL, NULL, '2026-05-31 07:35:52', '2026-06-10 02:18:30', NULL, NULL, NULL, NULL, NULL),
(1092, 'ORD20260601050106161', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 27000.00, 0.00, 0.00, 27000.00, NULL, 'Xin chào', NULL, NULL, NULL, '2026-06-01 05:01:06', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1093, 'ORD20260602022620163', 1006, NULL, 'Di Tiểu Bảo', '0909123456', 'Hà Nội', 'COD', 'UNPAID', 'PENDING', 11000.00, 0.00, 0.00, 11000.00, NULL, 'Hi', NULL, NULL, NULL, '2026-06-02 02:26:20', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1094, 'ORD20260602222134165', 1006, NULL, 'Di Tiểu Bảo', '0909155555', 'Hà Nội', 'COD', 'UNPAID', 'PENDING', 500000.00, 0.00, 0.00, 500000.00, NULL, 'Hi', NULL, NULL, NULL, '2026-06-02 22:21:34', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1095, 'ORD20260603194648167', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'PENDING', 2448500.00, 0.00, 0.00, 2448500.00, NULL, 'Giao hàng nhanh nha shop', NULL, NULL, NULL, '2026-06-03 19:46:48', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1097, 'ORD20260604171202170', 1024, NULL, 'Bảo Hân Helia', '0336999111', 'Quận 1, HCM', 'COD', 'UNPAID', 'DELIVERED', 411000.00, 0.00, 0.00, 411000.00, NULL, 'Giao hàng nhanh giúp tôi nha', NULL, NULL, '2026-06-07 17:12:02', '2026-06-04 17:12:02', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1098, 'ORD20260605143716172', 1024, NULL, 'Bảo Hân Helia', '0336999111', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'CONFIRMED', 900000.00, 0.00, 0.00, 900000.00, NULL, 'Gói hàng kĩ nha', NULL, NULL, NULL, '2026-06-05 14:37:16', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1099, 'ORD20260606120230174', 1024, NULL, 'Bảo Hân Helia', '0336999111', 'Quận 1, HCM', 'COD', 'UNPAID', 'PENDING', 1325000.00, 0.00, 0.00, 1325000.00, NULL, 'Gói hàng kĩ nha shop', NULL, NULL, NULL, '2026-06-06 12:02:30', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1100, 'ORD20260607075744176', 1025, NULL, 'Nguyễn Văn E', '0336999111', 'Quận 1, HCM', 'COD', 'UNPAID', 'CONFIRMED', 510000.00, 0.00, 0.00, 510000.00, NULL, 'Giao nhanh nha shop, thanks', NULL, NULL, NULL, '2026-06-07 07:57:44', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1101, 'ORD20260608052258178', 1025, NULL, 'Nguyễn Văn E', '0336999111', 'Hà Đông, Hà Nội', 'COD', 'UNPAID', 'PENDING', 110000.00, 0.00, 0.00, 110000.00, NULL, 'Giao nhanh nha shop', NULL, NULL, NULL, '2026-06-08 05:22:58', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1102, 'ORD20260609024812180', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Ninh Kiều, Cần Thơ', 'COD', 'UNPAID', 'DELIVERED', 1010000.00, 0.00, 0.00, 1010000.00, NULL, 'Giao nhanh nha shop', NULL, NULL, '2026-06-16 18:30:25', '2026-06-09 02:48:12', '2026-06-16 18:35:16', NULL, NULL, NULL, NULL, NULL),
(1103, 'ORD20260610001326182', 1027, NULL, 'Nguyễn Tuấn', '0336999888', 'Hà Đông, Hà Nội', 'COD', 'UNPAID', 'DELIVERED', 640000.00, 0.00, 0.00, 640000.00, NULL, 'Giao nhanh nha shop', NULL, NULL, '2026-06-10 20:30:00', '2026-06-10 00:13:26', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(1104, 'ORD20260610203000184', 1027, NULL, 'Nguyễn Tuấn', '0336999888', 'Hà Đông, Hà Nội', 'COD', 'UNPAID', 'PENDING', 260000.00, 0.00, 0.00, 260000.00, NULL, 'Giao nhanh nha shop', NULL, NULL, NULL, '2026-06-10 20:30:00', '2026-06-10 02:18:31', NULL, NULL, NULL, NULL, NULL),
(2000, 'ORD20260611140347598', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Hồ Chí Minh', 'COD', 'UNPAID', 'RETURNED', 11080000.00, 0.00, 0.00, 11080000.00, NULL, '', NULL, NULL, '2026-06-11 21:04:04', '2026-06-11 21:03:47', '2026-06-11 21:13:05', NULL, NULL, NULL, NULL, NULL),
(2001, 'ORD20260612092208217', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Bắc Ninh', 'COD', 'UNPAID', 'DELIVERED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, '', NULL, NULL, '2026-06-12 16:22:35', '2026-06-12 16:22:08', '2026-06-12 16:28:04', NULL, NULL, NULL, NULL, NULL),
(2002, 'ORD20260612172702672', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Hà Nội', 'COD', 'UNPAID', 'CANCELLED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, '', NULL, NULL, NULL, '2026-06-13 00:27:02', '2026-06-13 00:31:02', NULL, NULL, NULL, NULL, NULL),
(2003, 'ORD20260614140034595', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Cần Thơ', 'COD', 'UNPAID', 'CANCELLED', 15670000.00, 0.00, 0.00, 15670000.00, NULL, '', NULL, NULL, '2026-06-14 21:02:33', '2026-06-14 21:00:34', '2026-06-14 21:03:57', NULL, NULL, NULL, NULL, NULL),
(2004, 'ORD20260614142327179', 2000, NULL, 'minh s', '0916961956', 'minh s|||184, Hồ Chí Minh', 'COD', 'UNPAID', 'PENDING', 5500000.00, 0.00, 0.00, 5500000.00, NULL, '', NULL, NULL, NULL, '2026-06-14 21:23:27', '2026-06-14 21:23:27', NULL, NULL, NULL, NULL, NULL),
(2005, 'ORD20260616112612138', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Cần Thơ', 'COD', 'UNPAID', 'CANCELLED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, '', NULL, NULL, NULL, '2026-06-16 18:26:12', '2026-06-16 18:27:31', NULL, NULL, NULL, NULL, NULL),
(2006, 'ORD20260616113553649', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Cần Thơ', 'COD', 'UNPAID', 'DELIVERED', 10180000.00, 0.00, 0.00, 10180000.00, NULL, '', NULL, NULL, '2026-06-16 18:37:52', '2026-06-16 18:35:53', '2026-06-16 18:38:46', NULL, NULL, NULL, NULL, NULL),
(2007, 'ORD20260616115927218', 1006, NULL, 'Di Tiểu Bảo', '0909155511', 'Di Tiểu Bảo|||Ninh Kiều, Cần Thơ, Cần Thơ', 'COD', 'UNPAID', 'CANCELLED', 16030000.00, 0.00, 0.00, 16030000.00, NULL, '', NULL, NULL, NULL, '2026-06-16 18:59:27', '2026-06-16 19:00:42', NULL, NULL, NULL, NULL, NULL),
(2008, 'ORD202607091409596P2', 1, NULL, 'Quản trị viên', '0900000000', '1231, Hồ Chí Minh', 'COD', 'UNPAID', 'DELIVERING', 5290000.00, 0.00, 0.00, 5290000.00, NULL, '123123', NULL, NULL, NULL, '2026-07-09 14:09:59', '2026-07-09 14:16:22', NULL, NULL, NULL, NULL, NULL),
(2023, 'ORD20260710075322746', 1, NULL, 'Quản trị viên', '0900000000', 'Ninh Kiều, Cần Thơ, Chưa xác định, Chưa xác định, Đà Nẵng', 'VNPAY', 'UNPAID', 'AWAITING_PAYMENT', 5290000.00, 0.00, 0.00, 5290000.00, NULL, NULL, NULL, NULL, NULL, '2026-07-10 07:53:22', '2026-07-10 07:53:22', NULL, NULL, NULL, NULL, NULL),
(2024, 'ORD20260710075351VWF', 1, NULL, 'Quản trị viên', '0900000000', 'Ninh Kiều, Cần Thơ, Chưa xác định, Chưa xác định, Đà Nẵng', 'VNPAY', 'UNPAID', 'DELAY', 5290000.00, 0.00, 0.00, 5290000.00, NULL, NULL, NULL, NULL, NULL, '2026-07-10 07:53:51', '2026-08-03 11:45:16', NULL, NULL, NULL, NULL, NULL),
(2025, 'ORD20260710145510IGT', 1, NULL, 'Quản trị viên', '0900000000', 'Ninh Kiều, Cần Thơ, Chưa xác định, Chưa xác định, Đà Nẵng', 'VNPAY', 'PAID', 'LOST_IN_TRANSIT', 5290000.00, 0.00, 0.00, 5290000.00, NULL, 'VNPay transaction: 15616434', NULL, NULL, NULL, '2026-07-10 14:55:10', '2026-08-02 18:36:46', NULL, NULL, NULL, NULL, NULL),
(2026, 'ORD20260802173313RJJ', 1, NULL, 'Quản trị viên', '0900000000', 'Ninh Kiều, Cần Thơ, Chưa xác định, Chưa xác định, Hồ Chí Minh', 'VNPAY', 'PAID', 'RETURNED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, 'VNPay transaction: 15644004', NULL, NULL, NULL, '2026-08-02 17:34:10', '2026-08-04 15:04:05', NULL, NULL, NULL, NULL, NULL),
(2027, 'ORD20260803105345Q9G', 1, NULL, 'Quản trị viên', '0900000000', 'hdahdhwh, Bắc Kạn', 'VNPAY', 'PAID', 'PENDING', 52900000.00, 15870000.00, 0.00, 37030000.00, 1, 'VNPay transaction: 15644466', NULL, NULL, NULL, '2026-08-03 11:00:32', '2026-08-03 11:00:32', NULL, NULL, NULL, NULL, NULL),
(2028, 'ORD20260804095039F0F', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'RETURNED', 48900000.00, 14670000.00, 0.00, 34230000.00, 1, NULL, NULL, NULL, NULL, '2026-08-04 09:50:39', '2026-08-04 14:46:32', NULL, NULL, NULL, NULL, NULL),
(2029, 'ORD20260804104540HXO', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'CANCELLED', 4890000.00, 978000.00, 0.00, 3912000.00, 2, NULL, NULL, NULL, NULL, '2026-08-04 10:45:40', '2026-08-04 10:48:06', NULL, NULL, '2026-08-04 03:46:55', '2026-08-04 03:48:06', NULL),
(2030, 'ORD20260804110652STR', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'PENDING', 5490000.00, 0.00, 0.00, 5490000.00, NULL, NULL, NULL, NULL, NULL, '2026-08-04 11:06:52', '2026-08-04 11:06:52', NULL, NULL, NULL, NULL, NULL),
(2031, 'ORD20260804122818RBU', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'DELIVERED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, NULL, NULL, NULL, NULL, '2026-08-04 12:28:18', '2026-08-04 12:30:14', NULL, NULL, NULL, NULL, '2026-08-04 05:28:22'),
(2032, 'ORD20260804125245P2A', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'VNPAY', 'PAID', 'DELIVERED', 52900000.00, 1000000.00, 0.00, 51900000.00, 2, 'VNPay transaction: 15645809', NULL, NULL, NULL, '2026-08-04 12:53:17', '2026-08-04 12:54:27', NULL, NULL, NULL, NULL, '2026-08-04 05:53:23'),
(2033, 'ORD20260804125505O7T', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'CANCELLED', 4890000.00, 0.00, 0.00, 4890000.00, NULL, NULL, NULL, NULL, NULL, '2026-08-04 12:55:05', '2026-08-04 12:55:57', NULL, NULL, '2026-08-04 05:55:33', '2026-08-04 05:55:57', '2026-08-04 05:55:11'),
(2034, 'ORD202608041448589LA', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'PENDING', 47610000.00, 0.00, 0.00, 47610000.00, NULL, NULL, NULL, NULL, NULL, '2026-08-04 14:48:58', '2026-08-04 14:48:58', NULL, NULL, NULL, NULL, '2026-08-04 07:48:58'),
(2035, 'ORD20260804145628WW1', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'RETURN_PENDING', 5290000.00, 0.00, 0.00, 5290000.00, NULL, NULL, NULL, NULL, '2026-08-04 15:02:49', '2026-08-04 14:56:28', '2026-08-04 15:05:26', NULL, NULL, NULL, NULL, '2026-08-04 07:56:36'),
(2036, 'ORD20260804153243VNO', 2021, NULL, 'Minh Vu', '0916961956', '283, Quảng Ngãi', 'COD', 'UNPAID', 'DELIVERED', 5290000.00, 0.00, 0.00, 5290000.00, NULL, NULL, NULL, NULL, '2026-08-04 15:48:59', '2026-08-04 15:32:43', '2026-08-04 15:48:59', NULL, NULL, NULL, NULL, '2026-08-04 08:32:47'),
(2037, 'ORD20260804155651U5Q', 2022, NULL, 'Natsu Naruto', '0916961956', '283, Hồ Chí Minh', 'COD', 'UNPAID', 'RETURNED', 52900000.00, 1000000.00, 0.00, 51900000.00, 2, NULL, NULL, NULL, '2026-08-04 16:01:38', '2026-08-04 15:56:51', '2026-08-04 16:07:01', NULL, NULL, NULL, NULL, '2026-08-04 08:56:57');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` bigint NOT NULL,
  `product_id` bigint NOT NULL,
  `variant_id` bigint NOT NULL,
  `product_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lens_size_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_items_order` (`order_id`),
  KEY `fk_order_items_product` (`product_id`),
  KEY `fk_order_items_variant` (`variant_id`),
  KEY `idx_order_items_product_order` (`product_id`,`order_id`)
) ;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `variant_id`, `product_name`, `sku`, `color_name`, `lens_size_name`, `quantity`, `unit_price`, `discount_amount`, `total_price`) VALUES
(1, 1010, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(2, 1010, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(3, 1011, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 2, 110000.00, 0.00, 220000.00),
(4, 1011, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(5, 1012, 1023, 1023, 'Ray-Ban RB2132 New Wayfarer Havana', 'rayban_new_wayfarer_havane_marronClassique_g15-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(6, 1012, 1020, 1020, 'Ray-Ban RB2140 Orange Denim', 'rayban_wayfarer_denimOrange_orangeDegrade-54', 'Đen', '54', 1, 160000.00, 0.00, 160000.00),
(7, 1013, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 2, 160000.00, 0.00, 320000.00),
(8, 1013, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 2, 200000.00, 0.00, 400000.00),
(9, 1014, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 4, 160000.00, 0.00, 640000.00),
(10, 1014, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(11, 1014, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 2, 200000.00, 0.00, 400000.00),
(12, 1015, 1023, 1023, 'Ray-Ban RB2132 New Wayfarer Havana', 'rayban_new_wayfarer_havane_marronClassique_g15-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(13, 1015, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 200000.00, 0.00, 200000.00),
(14, 1016, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(15, 1016, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 200000.00, 0.00, 200000.00),
(16, 1016, 1020, 1020, 'Ray-Ban RB2140 Orange Denim', 'rayban_wayfarer_denimOrange_orangeDegrade-54', 'Đen', '54', 1, 160000.00, 0.00, 160000.00),
(17, 1017, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(18, 1017, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(19, 1017, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 2, 100000.00, 0.00, 200000.00),
(20, 1018, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(21, 1018, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(22, 1019, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 102000.00, 0.00, 102000.00),
(23, 1022, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(24, 1022, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(25, 1023, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 2, 159000.00, 0.00, 318000.00),
(26, 1023, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(27, 1024, 1015, 1015, 'Ray-Ban RB3025 Aviator Polarized', 'rayban_aviator_gun_gris-54', 'Đen', '54', 1, 95000.00, 0.00, 95000.00),
(28, 1024, 1014, 1014, 'Ray-Ban RB3025 Aviator Silver Mirror', 'rayban_aviator_gun_argentFlash-54', 'Đen', '54', 1, 102000.00, 0.00, 102000.00),
(29, 1025, 1021, 1021, 'Ray-Ban RB2132 New Wayfarer Black', 'rayban_new_wayfarer_noir_vertClassique_g15-54', 'Đen', '54', 1, 88000.00, 0.00, 88000.00),
(30, 1026, 1021, 1021, 'Ray-Ban RB2132 New Wayfarer Black', 'rayban_new_wayfarer_noir_vertClassique_g15-54', 'Đen', '54', 4, 88000.00, 0.00, 352000.00),
(31, 1027, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(32, 1027, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(33, 1028, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(34, 1029, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 97000.00, 0.00, 97000.00),
(35, 1029, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(36, 1029, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(37, 1030, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 3, 126000.00, 0.00, 378000.00),
(38, 1031, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 2, 97000.00, 0.00, 194000.00),
(39, 1031, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(40, 1032, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(41, 1033, 1021, 1021, 'Ray-Ban RB2132 New Wayfarer Black', 'rayban_new_wayfarer_noir_vertClassique_g15-54', 'Đen', '54', 1, 88000.00, 0.00, 88000.00),
(42, 1033, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 160000.00, 0.00, 160000.00),
(43, 1033, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 3, 180000.00, 0.00, 540000.00),
(44, 1034, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(45, 1034, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 3, 126000.00, 0.00, 378000.00),
(46, 1035, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(47, 1035, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 2, 180000.00, 0.00, 360000.00),
(48, 1036, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 160000.00, 0.00, 160000.00),
(49, 1036, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(50, 1036, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 2, 126000.00, 0.00, 252000.00),
(51, 1037, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(52, 1038, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(53, 1038, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 102000.00, 0.00, 102000.00),
(54, 1039, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 160000.00, 0.00, 160000.00),
(55, 1039, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 2, 50000.00, 0.00, 100000.00),
(56, 1039, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 102000.00, 0.00, 102000.00),
(57, 1040, 1017, 1017, 'Ray-Ban RB2140 Original Wayfarer Tortoise', 'rayban_wayfarer_havane_marron-54', 'Đen', '54', 1, 187000.00, 0.00, 187000.00),
(58, 1040, 1016, 1016, 'Ray-Ban RB2140 Original Wayfarer Black', 'rayban_wayfarer_noir_vert-54', 'Đen', '54', 1, 90000.00, 0.00, 90000.00),
(59, 1040, 1018, 1018, 'Ray-Ban RB2140 Denim Blue', 'rayban_wayfarer_denimNoir_bleuMirroir-54', 'Đen', '54', 2, 120000.00, 0.00, 240000.00),
(60, 1041, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 5, 160000.00, 0.00, 800000.00),
(61, 1042, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 2, 50000.00, 0.00, 100000.00),
(62, 1042, 1017, 1017, 'Ray-Ban RB2140 Original Wayfarer Tortoise', 'rayban_wayfarer_havane_marron-54', 'Đen', '54', 1, 187000.00, 0.00, 187000.00),
(63, 1043, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(64, 1043, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(65, 1044, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(66, 1044, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(67, 1045, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(68, 1045, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 2, 126000.00, 0.00, 252000.00),
(69, 1046, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(70, 1047, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 5, 100000.00, 0.00, 500000.00),
(71, 1048, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 4, 97000.00, 0.00, 388000.00),
(72, 1048, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 159000.00, 0.00, 159000.00),
(73, 1049, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(74, 1050, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 3, 180000.00, 0.00, 540000.00),
(75, 1050, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(76, 1051, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(77, 1051, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 200000.00, 0.00, 200000.00),
(78, 1052, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(79, 1052, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 2, 100000.00, 0.00, 200000.00),
(80, 1053, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(81, 1053, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 2, 102000.00, 0.00, 204000.00),
(82, 1054, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(83, 1054, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 102000.00, 0.00, 102000.00),
(84, 1054, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(85, 1055, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 2, 120000.00, 0.00, 240000.00),
(86, 1055, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(87, 1056, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(88, 1056, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 200000.00, 0.00, 200000.00),
(89, 1057, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(90, 1058, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 105000.00, 0.00, 105000.00),
(91, 1058, 1018, 1018, 'Ray-Ban RB2140 Denim Blue', 'rayban_wayfarer_denimNoir_bleuMirroir-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(92, 1058, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 126000.00, 0.00, 126000.00),
(93, 1059, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 2, 105000.00, 0.00, 210000.00),
(94, 1059, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(95, 1059, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 2, 95000.00, 0.00, 190000.00),
(96, 1060, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(97, 1060, 1021, 1021, 'Ray-Ban RB2132 New Wayfarer Black', 'rayban_new_wayfarer_noir_vertClassique_g15-54', 'Đen', '54', 1, 88000.00, 0.00, 88000.00),
(98, 1061, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 115000.00, 0.00, 115000.00),
(99, 1061, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(100, 1062, 1021, 1021, 'Ray-Ban RB2132 New Wayfarer Black', 'rayban_new_wayfarer_noir_vertClassique_g15-54', 'Đen', '54', 1, 420000.00, 0.00, 420000.00),
(101, 1062, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 115000.00, 0.00, 115000.00),
(102, 1063, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 800000.00, 0.00, 800000.00),
(103, 1063, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 100000.00, 0.00, 100000.00),
(104, 1064, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 55000.00, 0.00, 55000.00),
(105, 1064, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 50000.00, 0.00, 50000.00),
(106, 1064, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 25000.00, 0.00, 25000.00),
(107, 1065, 1014, 1014, 'Ray-Ban RB3025 Aviator Silver Mirror', 'rayban_aviator_gun_argentFlash-54', 'Đen', '54', 1, 60000.00, 0.00, 60000.00),
(108, 1065, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 25000.00, 0.00, 25000.00),
(109, 1065, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 70000.00, 0.00, 70000.00),
(110, 1066, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 55000.00, 0.00, 55000.00),
(111, 1066, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(112, 1066, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 180000.00, 0.00, 180000.00),
(113, 1067, 1018, 1018, 'Ray-Ban RB2140 Denim Blue', 'rayban_wayfarer_denimNoir_bleuMirroir-54', 'Đen', '54', 1, 40000.00, 0.00, 40000.00),
(114, 1067, 1014, 1014, 'Ray-Ban RB3025 Aviator Silver Mirror', 'rayban_aviator_gun_argentFlash-54', 'Đen', '54', 1, 60000.00, 0.00, 60000.00),
(115, 1067, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 2, 55000.00, 0.00, 110000.00),
(116, 1068, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(117, 1068, 1017, 1017, 'Ray-Ban RB2140 Original Wayfarer Tortoise', 'rayban_wayfarer_havane_marron-54', 'Đen', '54', 1, 40000.00, 0.00, 40000.00),
(118, 1068, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 55000.00, 0.00, 55000.00),
(119, 1069, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(120, 1069, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 220000.00, 0.00, 220000.00),
(121, 1070, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 37000.00, 0.00, 37000.00),
(122, 1070, 1001, 1001, 'Ray-Ban RB3025 Aviator Green', 'rayban_aviator_or_vertFlash-54', 'Đen', '54', 1, 290000.00, 0.00, 290000.00),
(123, 1070, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 2, 38000.00, 0.00, 76000.00),
(124, 1036, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 35000.00, 0.00, 35000.00),
(125, 1036, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 280000.00, 0.00, 280000.00),
(126, 1036, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(127, 1036, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(128, 1036, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 280000.00, 0.00, 280000.00),
(129, 1036, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 37000.00, 0.00, 37000.00),
(130, 1036, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 2, 28000.00, 0.00, 56000.00),
(131, 1036, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 27000.00, 0.00, 27000.00),
(132, 1036, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 3, 29000.00, 0.00, 87000.00),
(133, 1036, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 28000.00, 0.00, 28000.00),
(134, 1036, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 120000.00, 0.00, 120000.00),
(135, 1036, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 28000.00, 0.00, 28000.00),
(136, 1036, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 37000.00, 0.00, 37000.00),
(137, 1079, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 38000.00, 0.00, 38000.00),
(138, 1079, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 37000.00, 0.00, 37000.00),
(139, 1080, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 55000.00, 0.00, 55000.00),
(140, 1080, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 115000.00, 0.00, 115000.00),
(141, 1080, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 35000.00, 0.00, 35000.00),
(142, 1081, 1002, 1002, 'Ray-Ban RB3025 Aviator Brown', 'rayban_aviator_or_vert-54', 'Đen', '54', 1, 280000.00, 0.00, 280000.00),
(143, 1081, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 28000.00, 0.00, 28000.00),
(144, 1081, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 35000.00, 0.00, 35000.00),
(145, 1082, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 115000.00, 0.00, 115000.00),
(146, 1082, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 27000.00, 0.00, 27000.00),
(147, 1082, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 37000.00, 0.00, 37000.00),
(148, 1083, 1006, 1006, 'Ray-Ban RB3025 Aviator Blue Mirror', 'rayban_aviator_cuivre_bleuMirroir-54', 'Đen', '54', 1, 55000.00, 0.00, 55000.00),
(149, 1083, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 29000.00, 0.00, 29000.00),
(150, 1083, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 28000.00, 0.00, 28000.00),
(151, 1084, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 2, 29000.00, 0.00, 58000.00),
(152, 1084, 1024, 1024, 'Ray-Ban RB3016 Clubmaster Black', 'rayban_clubmaster_noir_vert-54', 'Đen', '54', 1, 115000.00, 0.00, 115000.00),
(153, 1084, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 38000.00, 0.00, 38000.00),
(154, 1084, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 5500.00, 0.00, 5500.00),
(155, 1084, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 9, 11000.00, 0.00, 99000.00),
(156, 1086, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 14000.00, 0.00, 14000.00),
(157, 1086, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 5500.00, 0.00, 5500.00),
(158, 1087, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 11000.00, 0.00, 11000.00),
(159, 1087, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 15500.00, 0.00, 15500.00),
(160, 1087, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 1, 26500.00, 0.00, 26500.00),
(161, 1090, 1027, 1027, 'Ray-Ban RB3447 Round Metal Black', 'rayban_round_noir_bleuClairDegrade-54', 'Đen', '54', 2, 26500.00, 0.00, 53000.00),
(162, 1090, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 2, 5500.00, 0.00, 11000.00),
(163, 1090, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 2, 8000.00, 0.00, 16000.00),
(164, 1091, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 8000.00, 0.00, 8000.00),
(165, 1091, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 2, 15500.00, 0.00, 31000.00),
(166, 1092, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 2, 5500.00, 0.00, 11000.00),
(167, 1092, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 2, 8000.00, 0.00, 16000.00),
(168, 1093, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 11000.00, 0.00, 11000.00),
(169, 1094, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 500000.00, 0.00, 500000.00),
(170, 1095, 1025, 1025, 'Ray-Ban RB3016 Clubmaster Havana', 'rayban_clubmaster_havane_vert-54', 'Đen', '54', 1, 585000.00, 0.00, 585000.00),
(171, 1095, 1026, 1026, 'Ray-Ban RB3447 Round Metal Gold', 'rayban_round_or_vert-54', 'Đen', '54', 1, 613500.00, 0.00, 613500.00),
(172, 1095, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 1250000.00, 0.00, 1250000.00),
(173, 1097, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 400000.00, 0.00, 400000.00),
(174, 1097, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 11000.00, 0.00, 11000.00),
(175, 1098, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 400000.00, 0.00, 400000.00),
(176, 1098, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 500000.00, 0.00, 500000.00),
(177, 1099, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Đen', '54', 1, 425000.00, 0.00, 425000.00),
(178, 1099, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 400000.00, 0.00, 400000.00),
(179, 1099, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 500000.00, 0.00, 500000.00),
(180, 1100, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 110000.00, 0.00, 110000.00),
(181, 1100, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 400000.00, 0.00, 400000.00),
(182, 1101, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 110000.00, 0.00, 110000.00),
(183, 1102, 1030, 1030, 'Ray-Ban RB4171 Erika Polarized', 'rayban_erika_noir_vert-54', 'Đen', '54', 1, 500000.00, 0.00, 500000.00),
(184, 1102, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 110000.00, 0.00, 110000.00),
(185, 1102, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Đen', '54', 1, 400000.00, 0.00, 400000.00),
(186, 1103, 1014, 1014, 'Ray-Ban RB3025 Aviator Silver Mirror', 'rayban_aviator_gun_argentFlash-54', 'Đen', '54', 1, 270000.00, 0.00, 270000.00),
(187, 1103, 1029, 1029, 'Ray-Ban RB4171 Erika Black', 'rayban_erika_noir_grisDegrade-54', 'Đen', '54', 1, 150000.00, 0.00, 150000.00),
(188, 1103, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 2, 110000.00, 0.00, 220000.00),
(189, 1104, 1016, 1016, 'Ray-Ban RB2140 Original Wayfarer Black', 'rayban_wayfarer_noir_vert-54', 'Đen', '54', 1, 150000.00, 0.00, 150000.00),
(190, 1104, 1031, 1031, 'Ray-Ban RB4165 Justin Black', 'rayban_justin_noir_vert-54', 'Đen', '54', 1, 110000.00, 0.00, 110000.00),
(191, 2000, 1032, 1032, 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique-54', 'Xanh lá', '54', 1, 5790000.00, 0.00, 5790000.00),
(192, 2000, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(193, 2001, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(194, 2002, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(195, 2003, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(196, 2003, 1039, 1039, 'Ray-Ban RB4202 Andy Black', 'rayban_andy_noir_vert_classique-54', 'Xanh lá', '54', 1, 4890000.00, 0.00, 4890000.00),
(197, 2003, 1038, 1038, 'Ray-Ban RB4246 Clubround Black', 'rayban_clubround_noir_vertClassique_g15-54', 'Xanh lá', '54', 1, 5490000.00, 0.00, 5490000.00),
(198, 2004, 1041, 2000, 'Bánh kem bắp', 'GLASS0ADB97-C14-S1', 'Bạc', '50', 1, 5500000.00, 0.00, 5500000.00),
(199, 2005, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(200, 2006, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(201, 2006, 1039, 1039, 'Ray-Ban RB4202 Andy Black', 'rayban_andy_noir_vert_classique-54', 'Xanh lá', '54', 1, 4890000.00, 0.00, 4890000.00),
(202, 2007, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(203, 2007, 1023, 1023, 'Ray-Ban RB2132 New Wayfarer Havana', 'rayban_new_wayfarer_havane_marronClassique_g15-54', 'Nâu', '54', 1, 5250000.00, 0.00, 5250000.00),
(204, 2007, 1038, 1038, 'Ray-Ban RB4246 Clubround Black', 'rayban_clubround_noir_vertClassique_g15-54', 'Xanh lá', '54', 1, 5490000.00, 0.00, 5490000.00),
(205, 2008, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(224, 2023, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(225, 2024, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(226, 2025, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(227, 2026, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(228, 2027, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 10, 5290000.00, 0.00, 52900000.00),
(229, 2028, 1039, 1039, 'Ray-Ban RB4202 Andy Black', 'rayban_andy_noir_vert_classique-54', 'Xanh lá', '54', 10, 4890000.00, 0.00, 48900000.00),
(230, 2029, 1039, 1039, 'Ray-Ban RB4202 Andy Black', 'rayban_andy_noir_vert_classique-54', 'Xanh lá', '54', 1, 4890000.00, 0.00, 4890000.00),
(231, 2030, 1028, 1028, 'Ray-Ban RB3447 Round Metal Copper', 'rayban_round_cuivre_pinkBrownDegrade-54', 'Nâu gradient', '54', 1, 5490000.00, 0.00, 5490000.00),
(232, 2031, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(233, 2032, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 10, 5290000.00, 0.00, 52900000.00),
(234, 2033, 1039, 1039, 'Ray-Ban RB4202 Andy Black', 'rayban_andy_noir_vert_classique-54', 'Xanh lá', '54', 1, 4890000.00, 0.00, 4890000.00),
(235, 2034, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 9, 5290000.00, 0.00, 47610000.00),
(236, 2035, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(237, 2036, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 1, 5290000.00, 0.00, 5290000.00),
(238, 2037, 1040, 1040, 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique-54', 'Xanh lá đậm', '54', 10, 5290000.00, 0.00, 52900000.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `token_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_password_reset_user` (`user_id`),
  KEY `idx_password_resets_user_used_expires` (`user_id`,`used_at`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(2, 1, '8e4ec717abe409cd33e974a9d480ad7d5efac242a8d1974cd7464a8163a9cd29', '2026-07-09 16:58:33', '2026-07-09 15:58:45', '2026-07-09 15:58:33'),
(3, 1, '74168f1fb3ed4adf070eb1baacc19b9fb9310f47d9d159ebe6642dc63acde554', '2026-07-09 16:58:45', '2026-07-09 15:59:03', '2026-07-09 15:58:45'),
(5, 1006, '09fb560786e579f003050f2ac315fe525096058bd77513ddc984e3e229fc08d3', '2026-07-09 17:11:48', '2026-07-09 16:27:37', '2026-07-09 16:11:48'),
(6, 1006, 'a16a0066e2d6240f2ded6722d6e2ff6c6a4965ba80e1ea1f9e93aae08747901c', '2026-07-09 17:27:37', '2026-07-09 16:27:37', '2026-07-09 16:27:37'),
(7, 1006, 'ff03bc4cc9abb7eb9c6ba83eb194e96233446d2e25d1fd37e8747e38cebe49b9', '2026-07-09 17:27:37', '2026-07-10 01:47:46', '2026-07-09 16:27:37'),
(8, 1006, '7faafe5976c1ddba8c37958ef2baaece25b910c0500e15fa666f6f79a653ffda', '2026-07-10 02:47:46', '2026-07-10 01:53:24', '2026-07-10 01:47:46'),
(10, 1006, '8468c58e62840e162a2c8348ebe3a1b8d5e8e562fbd741949ef6441c6e3b2bfc', '2026-07-10 02:53:24', '2026-07-10 01:58:47', '2026-07-10 01:53:24'),
(11, 1006, '61d484f54b190826d986525a9d83f1a998fa173712a60eb5680268f34f13aa3d', '2026-07-10 02:58:47', NULL, '2026-07-10 01:58:47');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` bigint NOT NULL,
  `payment_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` enum('COD','VNPAY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','EXPIRED','REFUNDED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `paid_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `transaction_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_message` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_code` (`payment_code`),
  KEY `idx_payments_order_status` (`order_id`,`status`)
) ;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_code`, `method`, `amount`, `status`, `paid_at`, `expired_at`, `transaction_no`, `bank_code`, `response_code`, `response_message`, `created_at`, `updated_at`) VALUES
(15, 2023, 'ORD20260710075322746', 'VNPAY', 5290000.00, 'PENDING', NULL, '2026-07-10 08:08:22', NULL, NULL, 'CREATED', 'VNPay payment URL created', '2026-07-10 07:53:22', '2026-07-10 07:53:22'),
(16, 2024, 'ORD20260710075351VWF', 'VNPAY', 5290000.00, 'PENDING', NULL, '2026-07-10 08:08:51', NULL, NULL, 'CREATED', 'VNPay payment URL created', '2026-07-10 07:53:51', '2026-07-10 07:53:51'),
(17, 2025, 'ORD20260710145510IGT', 'VNPAY', 5290000.00, 'SUCCESS', '2026-07-10 14:57:14', '2026-07-10 15:25:10', '15616434', 'NCB', '00', 'Giao dịch thành công', '2026-07-10 14:55:10', '2026-07-10 14:57:14'),
(18, 2026, 'ORD20260802173313RJJ', 'VNPAY', 5290000.00, 'SUCCESS', '2026-08-02 17:34:10', NULL, '15644004', 'NCB', '00', 'Giao dịch thành công', '2026-08-02 17:34:10', '2026-08-02 17:34:10'),
(19, 2027, 'ORD20260803105345Q9G', 'VNPAY', 37030000.00, 'SUCCESS', '2026-08-03 11:00:32', NULL, '15644466', 'NCB', '00', 'Giao dịch thành công', '2026-08-03 11:00:32', '2026-08-03 11:00:32'),
(20, 2028, 'ORD20260804095039F0F', 'COD', 34230000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 09:50:39', '2026-08-04 09:50:39'),
(21, 2029, 'ORD20260804104540HXO', 'COD', 3912000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 10:45:40', '2026-08-04 10:45:40'),
(22, 2030, 'ORD20260804110652STR', 'COD', 5490000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 11:06:52', '2026-08-04 11:06:52'),
(23, 2031, 'ORD20260804122818RBU', 'COD', 5290000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 12:28:18', '2026-08-04 12:28:18'),
(24, 2032, 'ORD20260804125245P2A', 'VNPAY', 51900000.00, 'SUCCESS', '2026-08-04 12:53:17', NULL, '15645809', 'NCB', '00', 'Giao dịch thành công', '2026-08-04 12:53:17', '2026-08-04 12:53:17'),
(25, 2033, 'ORD20260804125505O7T', 'COD', 4890000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 12:55:05', '2026-08-04 12:55:05'),
(26, 2034, 'ORD202608041448589LA', 'COD', 47610000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 14:48:58', '2026-08-04 14:48:58'),
(27, 2035, 'ORD20260804145628WW1', 'COD', 5290000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 14:56:28', '2026-08-04 14:56:28'),
(28, 2036, 'ORD20260804153243VNO', 'COD', 5290000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 15:32:43', '2026-08-04 15:32:43'),
(29, 2037, 'ORD20260804155651U5Q', 'COD', 51900000.00, 'PENDING', NULL, NULL, '', '', 'COD_PENDING', 'Thanh toán khi nhận hàng.', '2026-08-04 15:56:51', '2026-08-04 15:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `category_id` bigint DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `summary` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('DRAFT','PUBLISHED','HIDDEN') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_posts_category` (`category_id`),
  KEY `fk_posts_created_by` (`created_by`),
  KEY `idx_posts_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2004 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `category_id`, `title`, `slug`, `thumbnail_url`, `summary`, `content`, `status`, `created_by`, `published_at`, `created_at`, `updated_at`) VALUES
(1001, 1002, 'Vệ sinh mắt kính đúng cách tại nhà: Bí quyết từ chuyên gia', 've-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia', 'bv-1.png', 'Muốn kính trong, ít vệt và bền lâu, bạn cần vệ sinh đúng cho cả tròng kính lẫn gọng kính, đặc biệt khi tròng có lớp phủ chống phản quang hoặc chống bám dầu. Bài viết chia sẻ cách vệ sinh mắt kính tại nhà bằng nước sạch, xà phòng dịu nhẹ, vải microfiber và dung dịch lau kính, đồng thời chỉ ra các sai lầm khiến kính nhanh mờ và ố vàng.     Vệ sinh mắt kính thế nào là đúng?   Vệ sinh mắt kính là quá trình loại bỏ các tác nhân gây ô nhiễm như dầu nhờ', '<p><i><strong>Muốn kính trong, ít vệt và bền lâu, bạn cần vệ sinh đúng cho cả tròng kính lẫn gọng kính, đặc biệt khi tròng có lớp phủ chống phản quang hoặc chống bám dầu. Bài viết chia sẻ cách vệ sinh mắt kính tại nhà bằng nước sạch, xà phòng dịu nhẹ, vải microfiber và dung dịch lau kính, đồng thời chỉ ra các sai lầm khiến kính nhanh mờ và ố vàng.</strong></i></p><h2><strong>Vệ sinh mắt kính thế nào là đúng?</strong></h2><p>Vệ sinh mắt kính là quá trình loại bỏ các tác nhân gây ô nhiễm như dầu nhờn, tế bào chết, bụi mịn và mảng bám môi trường trên tròng kính và gọng kính.</p><p>Khi bề mặt bị bẩn, đường đi của ánh sáng bị biến đổi, gây hiện tượng tán xạ và giảm độ tương phản. Khi nhìn qua tròng kính bị bẩn, mắt bạn phải làm việc vất vả hơn để nhìn rõ, dễ gây mỏi mắt và đau đầu.</p><p>Vệ sinh đúng kỹ thuật là yếu tố then chốt để duy trì tầm nhìn sắc nét và kéo dài tuổi thọ cho chiếc kính của bạn.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-1.jpg\" alt=\"Vệ sinh mắt kính đúng cách giúp bạn duy trì tuổi thọ và độ trong suốt trong quá trình sử dụng\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-1.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-1-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Nắm rõ bí quyết vệ sinh mắt kính là cách để bạn kéo dài tuổi thọ và độ trong suốt khi sử dụng</i></p><h2><strong>Dụng cụ vệ sinh mắt kính cần có</strong></h2><p>Để đảm bảo an toàn cho các lớp <a href=\"https://kinhhaitrieu.com/vang-phu-lop-phu-trong-kinh-la-gi\">váng phủ</a>, bạn cần chuẩn bị các dụng cụ chuyên dụng sau:</p><p><strong>Khăn lau microfiber:</strong> Cấu trúc sợi polyme siêu nhỏ giúp nhấc bổng hạt bụi và hấp thụ dầu mỡ thay vì đẩy chúng loang lổ trên bề mặt.</p><p><strong>Dung dịch trung tính:</strong> Dung dịch vệ sinh kính lý tưởng là loại có nồng độ pH trung tính, không chứa các chất mài mòn hay các thành phần hóa học gây hại cho lớp phủ chống phản xạ (AR). Trong môi trường gia đình, nước rửa chén dịu nhẹ có thể được sử dụng, giúp tách dầu nhờn khỏi tròng kính mà không phản ứng với lớp phủ tròng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-2.jpg\" alt=\"Dụng cụ cần thiết để vệ sinh mắt kính tại nhà\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-2-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Một số dụng cụ cần thiết để bạn vệ sinh mắt kính đơn giản tại nhà</i></p><p>Bạn cũng có thể tham khảo bảng so sánh chi tiết dưới đây để đánh giá mức độ hiệu quả của các loại dung dịch:</p><figure class=\"table\"><table><tbody><tr><td><strong>Loại dung dịch</strong></td><td><strong>Hiệu quả làm sạch</strong></td><td><strong>Độ an toàn cho lớp phủ</strong></td><td><strong>Ghi chú từ chuyên gia</strong></td></tr><tr><td>Nước rửa chén dịu nhẹ</td><td>Rất cao</td><td>Tuyệt đối</td><td>Phù hợp khi vệ sinh tại nhà.</td></tr><tr><td>Dung dịch chuyên dụng</td><td>Cao</td><td>Cao</td><td>Tiện lợi, hỗ trợ khô nhanh và chống bám hơi nước.</td></tr><tr><td>Cồn y tế (70-90 độ)</td><td>Cực cao</td><td>Thấp</td><td>Gây giòn nhựa <a href=\"https://kinhhaitrieu.com/acetate-la-gi-uu-nhuoc-diem\">Acetate</a> và làm yếu liên kết lớp phủ.</td></tr><tr><td>Nước lau kính cửa sổ</td><td>Trung bình</td><td>Rất thấp</td><td>Chứa Amoniac/Axit gây bong tróc (peeling) lớp váng phủ.</td></tr></tbody></table></figure><h2><strong>Những sai lầm “chí mạng” cần tuyệt đối tránh</strong></h2><p>Với kinh nghiệm của tôi, phần lớn các trường hợp hỏng kính mà khách hàng mang đến đều xuất phát từ những thói quen vệ sinh “tiện tay” nhưng gây hại đến tròng kính.</p><h3><strong>1. Dùng khăn giấy và áo thun</strong></h3><p>Nhiều người dùng tin rằng khăn giấy hoặc áo thun cotton là đủ mềm để lau kính. Tuy nhiên, khăn giấy có sợi thô và cứng. Khi chà xát khăn giấy lên kính, các sợi này tạo ra hàng ngàn vết xước siêu nhỏ.</p><p>Theo thời gian, các vết xước này tích tụ khiến tròng kính bị mờ đục và làm mất hiệu lực của lớp phủ chống chói. Đối với áo thun, bụi mịn bám trên vải từ môi trường sẽ đóng vai trò như các hạt mài mòn khi bị ép xuống bề mặt kính.</p><h3><strong>2. Hóa chất tẩy rửa mạnh</strong></h3><p>Các loại nước lau kính cửa sổ thông thường, thuốc tẩy, amoniac hay giấm đều chứa các tác nhân oxy hóa mạnh hoặc axit/kiềm cao. Lớp phủ chống phản xạ trên tròng kính cao cấp dễ bị ảnh hưởng bởi hóa chất mạnh. Các hóa chất này sẽ phá vỡ liên kết giữa các tầng phủ, dẫn đến hiện tượng bong tróc hoặc tạo ra các vết loang lổ “cầu vồng” trên kính.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-3.jpg\" alt=\"Sử dụng hóa chất quá mạnh gây nên những vết loang lổ trên bề mặt tròng kính\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-3.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-3-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-3-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-3-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Hóa chất tẩy rửa quá mạnh sẽ làm bong tróc lớp phủ trên tròng kính và để lại những mảng loang lổ khó chịu</i></p><h3><strong>3. Mẹo baking soda và kem đánh răng</strong></h3><p>Tôi cũng từng chứng kiến nhiều khách hàng làm theo các hướng dẫn trên mạng, ví dụ như dùng kem đánh răng để xóa vết xước hoặc dùng baking soda để tẩy rửa chuyên sâu. Nhưng dưới góc độ chuyên gia, đây đều là những phương pháp phản khoa học đối với mắt kính:</p><ul><li><strong>Kem đánh răng:</strong> Chứa các hạt mài mòn như silica hoặc canxi cacbonat được thiết kế để đánh bóng men răng. Khi áp dụng lên tròng kính nhựa, nó sẽ mài mòn hoàn toàn các lớp phủ bảo vệ, tạo ra một vùng mờ đục vĩnh viễn.</li><li><strong>Baking soda:</strong> Là một hợp chất có tính nhám cơ học và tính kiềm. Việc chà xát hỗn hợp baking soda lên kính làm mỏng tròng và gây ra quang sai thị giác.</li></ul><h2><strong>Cách vệ sinh mắt kính theo 5 bước đơn giản tại nhà</strong></h2><p>Dựa trên khuyến nghị của các tập đoàn quang học hàng đầu như ZEISS và Essilor, quy trình vệ sinh bao gồm 5 bước bắt buộc:</p><p><strong>Bước 1: Rửa trôi bụi khô</strong></p><p>Đây là bước quan trọng nhất nhưng thường bị bỏ qua. Việc xả nước ấm (không quá 40 độ C) giúp loại bỏ các hạt bụi thô và cát bám trên bề mặt. Nếu bạn lau kính ngay khi còn khô, những hạt bụi này sẽ bị kẹt giữa khăn và tròng kính, tạo ra các vết trầy xước.</p><p><strong>Bước 2: Dùng xà phòng dịu nhẹ</strong></p><p>Sử dụng một giọt nước rửa chén không chứa dưỡng chất hoặc xà phòng rửa tay dịu nhẹ. Tạo bọt trên đầu ngón tay và nhẹ nhàng xoa đều lên tròng, gọng kính và ve mũi.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-4.jpg\" alt=\"Dùng xà phòng dịu nhẹ xoa đều lên bề mặt tròng kính để làm sạch các vết bẩn\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-4.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-4-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Sử dụng xà phòng dịu nhẹ là cách đơn giản để bạn vệ sinh mắt kính tại nhà</i></p><p><strong>Bước 3: Xả sạch bọt xà phòng</strong></p><p>Rửa lại kính dưới vòi nước chảy để đảm bảo sạch lớp xà phòng. Các vết mờ vằn vện thường thấy sau khi lau kính là do dư lượng xà phòng chưa được rửa sạch.</p><p><strong>Bước 4: Thấm khô đúng cách</strong></p><p>Lắc nhẹ kính để loại bỏ nước đọng. Sử dụng một khăn bông sạch (loại không đổ lông) để thấm nhẹ nước trên bề mặt. Lưu ý thực hiện thao tác thấm thay vì lau mạnh trong giai đoạn này để giảm thiểu ma sát khi bề mặt tròng kính vẫn còn độ ẩm.</p><p><strong>Bước 5: Lau hoàn thiện bằng khăn microfiber chuyên dụng</strong></p><p>Sử dụng khăn microfiber khô và sạch để lau nhẹ nhàng theo chuyển động tròn hoặc một chiều. Bước này giúp bề mặt khô sạch, ít vệt hơn, mang lại độ trong suốt tối ưu cho thấu kính.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-5.jpg\" alt=\"Sau khi thực hiện tất cả các bước, bạn cần sử dụng khăn microfiber để lau sạch kính\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-5.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-5-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Sử dụng khăn microfiber để lau khô bề mặt tròng kính sau khi đã thực hiện tất cả các bước</i></p><h2><strong>Cách vệ sinh gọng kính và ve mũi</strong></h2><p>Người Việt Nam thường có sống mũi thấp và cánh mũi rộng, cộng với khí hậu nóng ẩm dẫn đến việc da mặt tiết nhiều dầu và mồ hôi hơn. <a href=\"https://kinhhaitrieu.com/ve-dem-mui-mat-kinh\">Ve mũi</a> (đệm mũi) trở thành nơi dễ bám mồ hôi và dầu, nên cần ưu tiên vệ sinh.</p><p>Theo kinh nghiệm của mình, tôi khuyên bạn nên sử dụng tăm bông hoặc bàn chải đánh răng lông cực mềm thấm dung dịch xà phòng để làm sạch các kẽ hở giữa ve mũi và gọng, nơi tay người không thể chạm tới. Đặc biệt, đối với người da dầu, việc sử dụng các loại ve mũi bằng silicon mềm sẽ bám chắc hơn nhưng cũng dễ thấm dầu hơn, do đó cần thay thế định kỳ mỗi 3-6 tháng.</p><h2><strong>Cách xử lý các vết bẩn thường gặp</strong></h2><p>Trong quá trình sinh hoạt và tham gia các hoạt động thường ngày, mắt kính của bạn phải đối mặt với nhiều tác nhân từ môi trường, gây ra các vết bẩn khó chịu. Chúng đa phần có cấu tạo khác nhau nên cần các phương pháp xử lý riêng biệt để vừa làm sạch vừa bảo vệ lớp phủ bề mặt.</p><h3><strong>&nbsp;1. Vân tay và dầu nhờn</strong></h3><p>Dấu vân tay và dầu nhờn là một trong những loại vết bẩn thường gặp nhất đối với người đeo kính. Nếu bạn chỉ dùng khăn khô lau đi lau lại, vết dầu sẽ bị tán rộng ra khắp bề mặt thấu kính thay vì được loại bỏ.</p><p>Cách xử lý đúng: Sử dụng một giọt dung dịch lau kính chuyên dụng để tách những vết bẩn này ra khỏi bề mặt tròng kính, sau đó rửa lại bằng nước.</p><p>Ví dụ đơn giản: Sau một ngày làm việc, vùng tròng kính gần sát gò má thường dính một lớp màng mờ do mồ hôi và dầu. Bạn không nên dùng khăn microfiber lau ngay lúc đó mà nên rửa qua dung dịch lau kính chuyên dụng để làm sạch hoàn toàn lớp màng nhờn này.</p><h3><strong>2. Dính kem chống nắng và mỹ phẩm</strong></h3><p>Mắt kính bị dính kem chống nắng và mỹ phẩm là vấn đề mà nhiều phụ nữ thường xuyên gặp phải. Khi bám vào kính, chúng tạo ra các vệt trắng đục và rất khó lau sạch bằng cách thông thường.</p><p>Cách xử lý: Sử dụng nước ấm nhẹ (không quá 40 độ C) kết hợp nước rửa chén. Nhiệt độ ấm giúp làm mềm cấu trúc sáp trong mỹ phẩm, tạo điều kiện cho xà phòng hòa tan chất bẩn nhanh hơn.</p><p>Đặc biệt với khí hậu tại Việt Nam, nếu không xử lý đúng cách, kem chống nắng và mỹ phẩm sẽ để lại những vết bẩn khó chịu, khiến việc vệ sinh mắt kính của bạn khó khăn hơn. Ví dụ, nếu bạn đi bôi kem chống nắng và tình cờ chạm tay vào mắt kính, hãy rửa kính ngay khi có thể. Nếu để khô dưới nắng, lớp kem này sẽ khó làm sạch và dễ để lại vệt/mảng bám.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-6.jpg\" alt=\"Kem chống nắng/mỹ phẩm là một trong số tác nhân gây nên các vết bẩn khó chịu trên kính\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-6.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-6-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Bạn cần xử lý ngay các vết bẩn từ kem chống nắng/mỹ phẩm trước khi chúng khô lại và tạo nên các mảng bám khó chịu</i></p><h3><strong>3. Bụi mịn và hạt cát</strong></h3><p>Bụi mịn và hạt cát trong môi trường thường chứa các hạt silica, có độ cứng cao hơn tròng kính nhựa thông thường. Nếu không xử lý đúng cách, chúng sẽ tạo nên những vết xước dăm trên bề mặt tròng mà mắt thường không thể nhìn thấy.</p><p>Cách xử lý: Tuân thủ nguyên tắc “xả trước – lau sau”. Bạn phải đặt kính dưới vòi nước chảy để áp lực nước cuốn trôi các hạt bụi thô trước khi thực hiện bất kỳ thao tác lau chùi nào.</p><h3><strong>4. Đi mưa và nước bẩn</strong></h3><p>Nước mưa chứa axit và các tạp chất từ không khí. Khi nước mưa bốc hơi trên mặt kính, nó để lại các tinh thể khoáng và axit đậm đặc, có thể ăn mòn nhẹ lớp phủ bề mặt.</p><p>Cách xử lý: Sau khi đi mưa, cần rửa lại kính bằng nước sạch ngay lập tức và thấm khô hoàn toàn bằng khăn microfiber. Không được để nước tự khô trên mặt kính.</p><p>Trong quá trình làm việc tại Kính Hải Triều, tôi từng gặp phải nhiều trường hợp: Nhiều người sau khi đi mưa về thường để kính tự khô. Kết quả là trên tròng kính xuất hiện các vết đốm tròn mờ không thể lau sạch. Đây là hiện tượng “ố nước”, nếu để lâu, các đốm này có thể ăn sâu vào lớp phủ, buộc phải thay tròng kính mới.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-7.jpg\" alt=\"Sai lầm thường gặp khi vệ sinh mắt kính là để kính tự khô sau khi di chuyển ngoài trời mưa\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-7.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-7-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Để kính tự khô sau khi dính nước mưa là sai lầm mà nhiều người dùng gặp phải</i></p><h3><strong>5. Ố vàng ở ve kính</strong></h3><p>Những vết ố vàng xuất hiện ở ve kính thực chất là phản ứng oxy hóa giữa muối trong mồ hôi và thành phần đồng trong lõi kim loại của gọng kính hoặc ve mũi.</p><p>Cách xử lý: Dùng bàn chải lông siêu mềm (như bàn chải đánh răng trẻ em) kết hợp xà phòng để cọ nhẹ kẽ ve mũi.</p><p>Nếu vệ sinh tại nhà không sạch, bạn nên mang đến cửa hàng để thay ve mũi mới (đối với gọng có ve rời) hoặc vệ sinh định kỳ bằng máy sóng siêu âm để loại bỏ hoàn toàn vi khuẩn và chất gây mùi.</p><h2><strong>Vệ sinh mắt kính bao lâu một lần?</strong></h2><p>Vệ sinh theo tần suất định kỳ là yếu tố quan trọng để bạn duy trì tuổi thọ và ngoại hình của kính. Tần suất vệ sinh khuyến nghị mà bạn có thể tham khảo:</p><figure class=\"table\"><table><tbody><tr><td>Tần suất</td><td>Hoạt động</td><td>Mục đích</td></tr><tr><td>Hàng ngày</td><td>Lau bằng khăn microfiber</td><td>Loại bỏ dấu vân tay và bụi mịn tức thời</td></tr><tr><td>Hàng tuần</td><td>Vệ sinh bằng nước và xà phòng</td><td>Loại bỏ dầu nhờn và vi khuẩn tích tụ</td></tr><tr><td>Hàng tháng</td><td>Vệ sinh sâu các khe kẽ gọng</td><td>Ngăn ngừa mốc xanh và oxy hóa <a href=\"https://kinhhaitrieu.com/ban-le-mat-kinh\">bản lề</a></td></tr><tr><td>Mỗi 6 tháng</td><td>Kiểm tra tại cửa hàng chuyên nghiệp</td><td>Vệ sinh sóng siêu âm và cân chỉnh khung kính</td></tr></tbody></table></figure><p>Lưu ý: Nếu bạn hoạt động trong môi trường nhiều bụi/đi mưa nhiều, có thể tăng tần suất vệ sinh mắt kính lên.</p><h2><strong>Cách bảo quản để kính ít bẩn: Gợi ý từ chuyên gia</strong></h2><p>Bên cạnh làm sạch thường xuyên, việc bảo quản đúng cách đóng vai trò ngăn chặn các tác nhân gây ô nhiễm bám sâu vào bề mặt tròng kính.</p><h3><strong>1. Luôn cất kính vào hộp cứng khi không sử dụng</strong></h3><p>Hộp kính giúp bảo vệ chiếc mắt kính của bạn khỏi bụi mịn và các hạt hóa chất lơ lửng trong không khí. Các chuyên gia từ ZEISS (Đức) nhấn mạnh rằng bụi môi trường chứa các hạt silica có độ cứng rất cao. Khi không có hộp bảo vệ, các hạt này sẽ bám vào bề mặt, và chỉ cần một tác động nhỏ cũng đủ gây ra các vết xước dăm.</p><p>Cách thực hiện: Trước khi đặt kính vào hộp, hãy đảm bảo khăn lau microfiber được quấn quanh toàn bộ bề mặt tròng kính. Điều này giúp cố định kính, tránh việc tròng kính va chạm với thành hộp khi di chuyển.</p><p>Lưu ý: Tuyệt đối không để kính trần trong túi xách hoặc túi áo cùng với chìa khóa, tiền xu. Các vật dụng này sẽ trực tiếp phá hủy lớp váng bảo vệ, tạo ra những vết xước sâu.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-8.jpg\" alt=\"Bảo quản kính trong hộp khi không sử dụng là cách để bạn hạn chế bụi bẩn từ môi trường\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-8.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-8-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Bảo quản trong hộp khi không sử dụng để giúp kính luôn mới và hạn chế bụi bẩn</i></p><h3><strong>2. Không đặt mặt tròng kính tiếp xúc với bề mặt phẳng</strong></h3><p>Nhiều người thường mắc phải sai lầm chí mạng: Đặt úp tròng kính xuống bề mặt phẳng như mặt bàn. Đây là nguyên nhân hàng đầu gây ra các vết trầy tại tâm nhìn (optical center). Theo tài liệu từ Hoya (Nhật Bản), lớp phủ ngoài cùng của tròng kính cao cấp thường là lớp kỵ nước (Hydrophobic) và chống tĩnh điện. Khi úp tròng kính xuống mặt phẳng, ma sát sẽ phá vỡ liên kết tĩnh điện này, khiến kính dễ bám bụi hơn gấp 2 lần so với thông thường.</p><p>Bạn cần phải ghi nhớ: Luôn đặt kính nằm ngửa (mặt tròng hướng lên trên) hoặc gập <a href=\"https://kinhhaitrieu.com/cac-bo-phan-cua-kinh-mat#cang-kinh\">càng kính</a> và đặt kính đứng vững.</p><h3><strong>3. Không để kính trong môi trường nhiệt độ cao</strong></h3><p>Nhiệt độ cao là yếu tố gây tổn hại lớn nhất đối với những loại <a href=\"https://kinhhaitrieu.com/trong-kinh-chiet-suat-cao-la-gi-chon-loai-nao\">tròng kính chiết suất cao</a>. Hiệp hội nhãn khoa Hoa Kỳ cảnh báo rằng: nhiệt độ bên trong ô tô đậu dưới nắng có thể lên tới 60 – 70 độ C. Lúc này, phôi tròng kính nhựa và các lớp phủ kim loại sẽ giãn nở với tốc độ khác nhau.</p><h3><strong>4. Mang theo bộ vệ sinh nhỏ</strong></h3><p>Là một người đeo kính trong hơn 5 năm, tôi nhận ra rằng: Việc xử lý ngay các vết bẩn mới (vân tay, bụi đường) bằng dung dịch chuyên dụng và khăn sạch sẽ hiệu quả hơn nhiều so với việc để chúng tích tụ lâu ngày, tạo thành mảng bám cứng đầu gây mòn lớp phủ.</p><p>Vì vậy, luôn mang theo một bộ vệ sinh mắt kính nhỏ (gồm 1 lọ dung dịch mini và khăn microfiber) giúp bạn giải quyết nhanh chóng các vết bẩn, mang lại tầm nhìn trong suốt và thẩm mỹ.</p><h2><strong>Khi nào cần mang kính đến kỹ thuật viên chuyên nghiệp?</strong></h2><p>Dù việc vệ sinh hằng ngày giúp duy trì độ trong suốt của mặt kính, nhưng có những vấn đề thuộc về cấu trúc phức tạp bắt buộc phải được xử lý bởi kỹ thuật viên nhãn khoa với trang thiết bị chuyên dụng.</p><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/X-SZgNu2lJg/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>Dịch vụ vệ sinh kính miễn phí tại Kính Hải Triều có gì?</i></p><h3><strong>1. Cần vệ sinh sâu bằng công nghệ sóng siêu âm</strong></h3><p>Bạn chỉ có thể làm sạch bề mặt tròng kính tại nhà. Nhưng mồ hôi, bã nhờn và tế bào chết thường tích tụ sâu trong các kẽ hở mà khăn lau hay bàn chải không thể chạm tới, như: rãnh lắp tròng, khe bản lề, và hốc vít.</p><p>Với những vị trí này, máy rửa sóng siêu âm sử dụng tần số cao tạo ra các bọt khí siêu vi len lỏi vào mọi ngóc ngách để đánh tan các mảng bám sinh học mà không làm trầy xước kính.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-9.jpg\" alt=\"Quy trình vệ sinh mắt kính bằng máy móc chuyên dụng tại Kính Hải Triều\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-9.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/ve-sinh-mat-kinh-dung-cach-tai-nha-bi-quyet-tu-chuyen-gia-9-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Máy vệ sinh kính chuyên dụng tại Kính Hải Triều</i></p><h3><strong>2. Cân chỉnh khung kính</strong></h3><p>Gọng kính bị rộng, cong vênh hoặc một bên cao một bên thấp vừa ảnh hưởng đến thẩm mỹ vừa làm sai lệch tâm quang học. Việc tự bẻ gọng tại nhà rất dễ gây gãy gọng hoặc mẻ cạnh tròng kính do không kiểm soát được lực.</p><p>Lúc này, bạn cần mang kính đến những địa chỉ uy tín. Ở đó, kỹ thuật viên có các bộ kìm chuyên dụng và máy sấy nhiệt để điều chỉnh chính xác theo cấu trúc khuôn mặt của từng khách hàng.</p><p><img src=\"data:image/svg+xml,%3Csvg%20viewBox%3D%220%200%20900%20720%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3C%2Fsvg%3E\" alt=\"Bạn nên tìm tới các địa chỉ uy tín để căn chỉnh khung kính trong trường hợp bị xô lệch\" width=\"900\" height=\"720\"><i>Kính Hải Triều hỗ trợ bảo trì sản phẩm trọn đời cho khách hàng</i></p><h3><strong>3. Có dấu hiệu bong tróc lớp phủ</strong></h3><p>Nếu bạn thấy tròng kính xuất hiện các vết rạn như mạng nhện, đây là dấu hiệu của sự hỏng hóc lớp phủ do nhiệt độ hoặc hóa chất. Tình trạng này không thể lau sạch và cần được thay thế để bảo vệ thị lực.</p><h2><strong>Câu hỏi thường gặp khi vệ sinh mắt kính</strong></h2><p>Dựa trên kiến thức và kinh nghiệm của mình, tôi sẽ giải đáp cho bạn một vài câu hỏi thường gặp khi vệ sinh mắt kính tại nhà.</p><h3><strong>1. Lau kính bằng cồn được không?</strong></h3><p>Bạn không nên tự dùng cồn mạnh/không rõ nồng độ; thay vào đó chỉ dùng sản phẩm chuyên dụng cho tròng kính. Các chuyên gia từ ZEISS khuyến nghị chỉ nên sử dụng các loại khăn lau tẩm cồn được thiết kế chuyên biệt cho mắt kính, nơi nồng độ cồn đã được kiểm soát để không làm hại lớp phủ.</p><h3><strong>&nbsp;2. Dùng giấm lau kính có sao không?</strong></h3><p>Tôi không khuyến khích bạn sử dụng giấm để lau kính bởi đây là một loại axit acetic tự nhiên có tính ăn mòn cao. Mặc dù nó có thể làm sạch các vết ố canxi, nhưng đồng thời cũng tấn công vào cấu trúc các lớp phủ bảo vệ thấu kính, làm giảm độ bền của kính theo thời gian.</p><h3><strong>3. Khăn ướt lau kính có an toàn không?</strong></h3><p>Khăn ướt lau kính chuyên dụng (Lens Wipes) là một giải pháp tuyệt vời cho sự tiện lợi. Chúng thường được làm từ các sợi siêu mịn thấm dung dịch cồn bay hơi nhanh, giúp làm sạch dầu mỡ và khử trùng bề mặt thấu kính mà không để lại vết vằn. Tuy nhiên, cần đảm bảo thấu kính không có bụi cát thô trước khi lau bằng khăn ướt để tránh trầy xước.</p><h3><strong>4. Có nên rửa kính bằng nước nóng?</strong></h3><p>Tròng và các lớp phủ có giãn nở khác nhau theo nhiệt độ. Khi tiếp xúc với nước nóng (như nước sôi hoặc nước tắm nóng), chúng sẽ giãn nở nhanh hơn các lớp phủ kim loại mỏng bên trên, gây ra các vết rạn nứt li ti và phá hủy hoàn toàn chức năng quang học của kính.</p><p>Hãy chỉ sử dụng nước ấm dưới 40 độ C – đủ sạch mà không gây hại lớp phủ.</p><h3><strong>5. Vì sao kính nhanh ố vàng?</strong></h3><p>Với các gọng kính nhựa trong suốt (Clear Frames), hiện tượng ố vàng là hệ quả của quá trình oxy hóa polymer khi tiếp xúc thường xuyên với tia UV từ ánh nắng mặt trời, kết hợp với các axit béo và mồ hôi từ da người. Sử dụng gọng kính làm từ vật liệu Acetate cao cấp hoặc Titanium có thể giúp hạn chế tối đa tình trạng này so với các loại nhựa rẻ tiền.</p><p>Hy vọng qua bài viết này, bạn đã nắm rõ cách vệ sinh mắt kính tại nhà cũng như những lưu ý trong quá trình thực hiện. Nếu chiếc kính của bạn đang gặp các vấn đề về mảng bám cứng đầu, lệch gọng hoặc lỏng ốc, hãy để các chuyên gia tại Kính Hải Triều hỗ trợ bạn.</p>', 'PUBLISHED', 1, '2026-01-11 10:37:31', '2026-01-11 10:37:31', '2026-04-03 23:41:41'),
(1005, 1002, 'Cách tháo mắt kính ra khỏi gọng an toàn, dễ làm tại nhà', 'cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha', 'bv2.jpg', 'Bạn đang muốn vệ sinh kính chuyên sâu hoặc tự thay tròng tại nhà nhưng lại lo sợ làm hỏng kính? Đừng lo lắng! Với kinh nghiệm 3 năm trong lĩnh vực mắt kính cao cấp tại Hải Triều, tôi sẽ hướng dẫn cách tháo mắt kính ra khỏi gọng để bạn có thể thực hiện dễ dàng và an toàn.     Tại sao bạn cần biết cách tháo mắt kính ra khỏi gọng?   Nắm vững cách tháo mắt kính ra khỏi gọng mang lại cho bạn nhiều lợi ích thiết thực về kinh tế, sức khỏe và thẩm mỹ.&nb', '<p><i><strong>Bạn đang muốn vệ sinh kính chuyên sâu hoặc tự thay tròng tại nhà nhưng lại lo sợ làm hỏng kính? Đừng lo lắng! Với kinh nghiệm 3 năm trong lĩnh vực mắt kính cao cấp tại Hải Triều, tôi sẽ hướng dẫn cách tháo mắt kính ra khỏi gọng để bạn có thể thực hiện dễ dàng và an toàn.</strong></i></p><h2><strong>Tại sao bạn cần biết cách tháo mắt kính ra khỏi gọng?</strong></h2><p>Nắm vững cách tháo mắt kính ra khỏi gọng mang lại cho bạn nhiều lợi ích thiết thực về kinh tế, sức khỏe và thẩm mỹ.&nbsp;</p><h3><strong>1. Tiết kiệm thời gian và chi phí</strong></h3><p>Gọng kính thường có tuổi thọ lâu hơn tròng kính. Nếu gọng kính của bạn vẫn còn tốt, hợp thời trang hoặc có giá trị kỷ niệm, bạn không cần phải bỏ tiền mua một cặp kính hoàn toàn mới khi:</p><ul><li><strong>Độ cận thay đổi:</strong> Mắt bạn có thể tăng hoặc giảm độ theo thời gian. Thay vì mua kính mới, bạn chỉ cần tháo tròng cũ và lắp tròng mới đúng độ vào gọng cũ.</li><li><strong>Tròng kính bị hỏng:</strong> Tròng kính rất dễ bị trầy xước, bong tróc lớp phủ hoặc nứt vỡ do va đập. Việc thay thế tròng mới giúp khôi phục tầm nhìn rõ nét mà không lãng phí gọng kính.</li></ul><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-1.jpg\" alt=\"Biết cách tháo mắt kính ra khỏi gọng giúp bạn linh động trong quá trình vệ sinh và thay thế\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-1.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-1-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Biết cách tháo mắt kính ra khỏi gọng giúp bạn chủ động hơn trong quá trình thay thế và vệ sinh</i></p><h3><strong>2. Chủ động trong chăm sóc mắt kính</strong></h3><p>Các rãnh gọng kính là nơi tích tụ dầu mỡ, da chết và bụi bẩn mà các phương pháp vệ sinh thông thường không thể tiếp cận được. Việc tách rời tròng và gọng cho phép bạn làm sạch hoàn toàn các kẽ hở, ngăn ngừa hiện tượng ăn mòn kim loại hoặc làm giòn nhựa do axit từ mồ hôi tích tụ lâu ngày.</p><p>Trong trường hợp kính bị rơi vào bùn đất hoặc chất bẩn khó lau, việc tháo rời là cách tốt nhất để làm sạch hoàn toàn.</p><h3><strong>3. Nâng cấp và thay đổi công năng sử dụng</strong></h3><p>Bạn có thể tháo tròng kính trong suốt để thay bằng tròng kính mát, tròng lọc <a href=\"https://kinhhaitrieu.com/anh-sang-xanh-la-gi-co-o-dau-tac-hai-len-mat-va-da-the-nao\">ánh sáng xanh</a>, hoặc tròng đổi màu.</p><p>Đối với những bệnh nhân đã phẫu thuật thay thủy tinh thể (như phẫu thuật <a href=\"https://kinhhaitrieu.com/duc-thuy-tinh-the-la-gi\">đục thủy tinh thể</a> hoặc Clear Lens Exchange), họ có thể không cần kính cận nữa. Khi đó, họ có thể tháo tròng có độ ra để lắp tròng không độ hoặc tròng kính mát để bảo vệ mắt.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-2.jpg\" alt=\"Trong một số trường hợp, bạn cũng có thể thay thế tròng có độ bằng tròng không độ\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-2-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Trong một số trường hợp, bạn có thể thay thế tròng có độ bằng tròng không độ</i></p><h2><strong>Hướng dẫn A-Z cách tháo mắt kính ra khỏi gọng</strong></h2><p>Với 3 năm kinh nghiệm làm việc trong lĩnh vực mắt kính cao cấp tại Hải Triều, tôi sẽ hướng dẫn bạn cách tháo mắt kính ra khỏi gọng, áp dụng cho 3 chất liệu phổ biến nhất hiện nay: Gọng nhựa, gọng kim loại và gọng khoan.</p><h3><strong>1. Cách tháo tròng kính gọng nhựa (Acetate, TR90)</strong></h3><p>Đối với gọng nhựa nguyên khung, tròng kính được giữ bằng sức căng của viền gọng. Vì vậy chúng ta cần dùng nhiệt để làm mềm nhựa và dùng lực tay để đẩy.</p><p><strong>Cách thực hiện:</strong></p><p><strong>Làm nóng:</strong> Sử dụng máy sấy tóc (khoảng 40-50°C) sấy quanh viền gọng từ 30-60 giây. hoặc ngâm gọng trong nước ấm (không quá nóng) khoảng 1 phút để nhựa giãn nở.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-3.jpg\" alt=\"Sử dụng máy sấy để làm gọng nhựa giãn nở\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-3.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-3-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Cách làm này giúp bạn dễ dàng tháo tròng kính ra khỏi gọng</i></p><p><strong>Thao tác:</strong> Cầm chắc gọng kính. Đặt ngón tay cái vào mặt sau của tròng kính (phía tiếp xúc với mắt), tốt nhất là ở góc phía mũi (nơi gọng mỏng và linh hoạt nhất). Dùng lực dứt khoát đẩy tròng kính ra phía trước.</p><p>Nếu gọng làm bằng vật liệu TR90 (siêu dẻo), bạn có thể không cần dùng nhiệt mà có thể tháo “nguội” nếu khéo léo. Nhưng theo kinh nghiệm của tôi, dùng nhiệt vẫn an toàn hơn nếu bạn là người lần đầu tháo mắt kính ra khỏi gọng.</p><h3><strong>2. Cách tháo tròng kính gọng kim loại (Thép, Titanium)</strong></h3><p>Khác với gọng nhựa, gọng kim loại thường sử dụng ốc vít để kẹp chặt tròng kính. Vì vậy thao tác sẽ có đôi chút khác biệt:</p><p><strong>Tìm vị trí ốc:</strong> Quan sát kỹ phần viền gọng, thường ốc giữ tròng nằm ở phía thái dương (nơi càng kính gắn vào) hoặc đôi khi ở phía <a href=\"https://kinhhaitrieu.com/tim-hieu-cac-kieu-cau-mui-co-tren-mat-kinh-uu-nhuoc-diem\">cầu mũi</a>.</p><p><strong>Vặn ốc:</strong> Dùng tua vít chuyên dụng loại nhỏ (thường là đầu dẹt hoặc chữ thập 1.4mm – 1.6mm) vặn ngược chiều kim đồng hồ để nới lỏng ốc.</p><p>Tôi có mẹo cho bạn ở bước này: Bạn không cần tháo rời hẳn con ốc ra, chỉ cần nới lỏng cho đến khi khe hở ở viền gọng tách ra đủ rộng.</p><p><strong>Lấy tròng:</strong> Khi viền gọng đã hở, nhẹ nhàng đẩy tròng kính ra ngoài. Sau đó vặn chặt ốc lại để giữ form gọng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-4.jpg\" alt=\"Với gọng kim loại, bạn cần xác định vị trí ốc vít để có thể tháo tròng ra khỏi gọng\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-4.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-4-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Với gọng kính bằng kim loại, cần phải dùng tua vít chuyên dụng để nới lỏng ốc</i></p><h3><strong>3. Cách tháo tròng kính gọng xẻ cước/gọng khoan</strong></h3><p>Gọng xẻ cước chỉ có viền kim loại hoặc nhựa ở nửa trên, nửa dưới được giữ bằng một sợi dây cước nylon trong suốt chạy trong rãnh tròng kính. Vì vậy, chúng ta phải kéo sợi dây cước ra khỏi rãnh kính để tháo tròng ra.</p><p><strong>Chuẩn bị:</strong> Bạn cần một đoạn dây ruy-băng nhỏ, dây chỉ nha khoa chắc chắn hoặc một miếng nhựa mỏng.</p><p><strong>Thao tác:</strong> Luồn đoạn dây ruy-băng vào giữa tròng kính và sợi dây cước (thường bắt đầu ở góc dưới hoặc góc phía mũi). Một tay giữ chặt khung kính phía trên. Tay kia cầm dây ruy-băng kéo mạnh xuống dưới và ra ngoài. Sợi cước sẽ bật ra khỏi rãnh kính, và bạn có thể lấy tròng ra dễ dàng.</p><p><strong>Lưu ý khi thực hiện:</strong> Tuyệt đối không dùng vật sắc nhọn như dao/kéo để cạy dây cước vì sẽ làm xước tròng hoặc đứt dây.</p><p>Với gọng kính khoan, tròng kính được khoan lỗ và gắn trực tiếp vào cầu mũi và càng kính bằng ốc vít hoặc chốt nhựa. Tôi khuyên bạn không nên tự tháo tròng của loại kính này, bởi tròng kính tại điểm khoan chịu lực rất lớn. Nếu làm sai kỹ thuật, tròng kính rất dễ bị nứt vỡ ngay tại lỗ khoan.</p><h2><strong>Một số lưu ý để tháo mắt kính ra khỏi gọng an toàn</strong></h2><p>Lý thuyết là vậy, nhưng để tháo tròng kính một cách an toàn nhất, bạn cần phải nắm rõ một vài nguyên tắc sau đây.</p><p>Đầu tiên, bạn luôn phải trải một tấm khăn dày dưới mặt bàn làm việc. Nếu tròng kính bật ra bất ngờ và rơi xuống, khăn sẽ ngăn nó bị vỡ hoặc trầy xước.</p><p>Thứ hai, rửa sạch kính và tay trước khi làm để tránh trơn trượt và làm bẩn lớp phủ tròng kính.</p><p>Cuối cùng, và cũng là điều quan trọng nhất, đó là không dùng lực quá mạnh. Nếu bạn cảm thấy phải dùng hết sức mà tròng vẫn không ra, hãy dừng lại. Có thể bạn đang thao tác sai loại gọng hoặc gọng quá cũ/giòn. Cố gắng tiếp tục có thể làm gãy gọng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-5.jpg\" alt=\"cach thao mat kinh ra khoi gong an toan de lam tai nha 5\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-5.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-5-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Không nên dùng lực quá mạnh khi tháo tròng</i></p><h2><strong>Sai lầm cần tránh khi tháo mắt kính ra khỏi gọng</strong></h2><p>Dưới đây là những sai lầm phổ biến nhất bạn cần tuyệt đối tránh để bảo vệ cả gọng kính và tròng kính của mình:</p><h3><strong>1. Sử dụng nhiệt (đối với gọng nhựa)</strong></h3><p><strong>Làm nóng quá mức</strong></p><p>Nhiều người sử dụng máy sấy ở nhiệt độ quá cao hoặc ngâm kính vào nước sôi. Điều này có thể làm chảy nhựa, làm biến dạng gọng kính vĩnh viễn khiến không thể lắp tròng lại được. Nghiêm trọng hơn, nhiệt độ cao đột ngột sẽ gây ra hiện tượng rạn nứt lớp phủ – tức là lớp phủ chống chói/chống <a href=\"https://kinhhaitrieu.com/tia-uv-la-gi-co-o-dau-cac-chi-so-cua-tia-uv-va-tac-hai-len-mat-da\">tia UV</a> trên tròng kính bị giãn nở không đồng đều với vật liệu tròng, tạo ra các vết rạn chân chim li ti làm hỏng tầm nhìn.</p><p><strong>Tháo khi gọng còn lạnh</strong></p><p>Nhựa (đặc biệt là nhựa cũ) trở nên giòn và dễ gãy ở nhiệt độ thấp. Cố gắng đẩy tròng ra khi gọng chưa được làm ấm đủ sẽ dẫn đến nguy cơ cao bị gãy viền gọng hoặc cầu mũi.</p><p><strong>Làm nóng toàn bộ thay vì cục bộ</strong></p><p>Đối với một số vật liệu nhựa nhạy cảm như Optyl, việc làm nóng toàn bộ có thể làm khung kính mất form. Bạn chỉ nên tập trung nhiệt vào phần viền gọng xung quanh tròng kính.</p><h3><strong>2. Sai lầm về kỹ thuật thao tác</strong></h3><p><strong>Vặn xoắn gọng kính</strong></p><p>Tuyệt đối không vặn xoắn khung kính như vắt khăn để tròng bật ra. Hành động này sẽ làm cong vênh gọng kim loại hoặc gãy gọng nhựa, khiến kính bị lệch tâm khi đeo lại.</p><p><strong>Đẩy sai vị trí</strong></p><p>Một sai lầm phổ biến là cố gắng đẩy tròng ra từ phía thái dương (phía đuôi mắt). Thực tế, điểm yếu và linh hoạt nhất của gọng thường nằm ở góc phía mũi hoặc góc trên. Bắt đầu từ sai vị trí sẽ khiến việc tháo trở nên khó khăn hơn nhiều.</p><p>Ngoài ra, tròng kính luôn phải được tháo bằng cách đẩy từ mặt trong (mặt lõm – concave side) ra mặt ngoài (mặt lồi). Đẩy ngược lại có thể làm hỏng rãnh gọng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-6.jpg\" alt=\"Lưu ý chiều tác dụng lực lên tròng kính khi tháo\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-6.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-6-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Lưu ý hướng đẩy để tránh việc tròng kính bị hỏng hóc</i></p><h3><strong>3. Sử dụng dụng cụ không phù hợp</strong></h3><p>Lưu ý bạn không được sử dụng dao, kéo, tua vít đầu nhọn hoặc dao rọc giấy để cạy tròng kính. Chúng rất dễ trượt tay gây xước sâu lên tròng kính hoặc làm hỏng lớp sơn của gọng.</p><p>Đối với gọng kim loại, việc dùng tua vít quá lớn hoặc quá nhỏ so với đầu ốc sẽ làm toét đầu ốc, khiến bạn không thể vặn ra được nữa và phải mang ra tiệm để khoan phá ốc.</p><h2><strong>Câu hỏi thường gặp: Giải đáp cùng chuyên gia</strong></h2><p>Với kinh nghiệm tiếp xúc và tư vấn cho hàng nghìn khách hàng, tôi sẽ tổng hợp và giải đáp tất tần tật những thắc mắc phổ biến của người dùng khi tháo mắt kính ra khỏi gọng.</p><h3><strong>1. Tôi có thể lắp tròng kính cũ sang một gọng kính mới được không?</strong></h3><p>Về lý thuyết là có thể, nhưng thực tế rất khó và phụ thuộc vào sự tương thích. Tròng kính cũ đã được mài cắt theo hình dáng cụ thể của gọng cũ. Bạn chỉ có thể lắp sang gọng mới nếu hình dạng viền của hai gọng giống hệt nhau.&nbsp;</p><p>Nếu gọng mới khác kiểu dáng hoặc kích thước, tròng kính sẽ không vừa khớp. Ngoài ra, tâm kính phải trùng với đồng tử mắt khi lắp vào gọng mới để đảm bảo thị lực, điều này rất khó đạt được nếu chuyển đổi giữa các gọng khác nhau.</p><h3><strong>2. Tại sao tròng kính của tôi lại khó tháo ra dù đã dùng lực mạnh?</strong></h3><p>Có thể do hai nguyên nhân chính:</p><ul><li>Gọng nhựa bị lạnh/cứng: Nhựa <a href=\"https://kinhhaitrieu.com/acetate-la-gi-uu-nhuoc-diem\">Acetate</a> hoặc nhựa cũ thường co lại và cứng khi ở nhiệt độ phòng. Bạn cần làm ấm gọng để nhựa giãn nở.</li><li>Chưa tháo ốc (gọng kim loại): Với gọng kim loại, nếu chưa nới lỏng ốc vít khóa gọng, tròng kính sẽ bị kẹp chặt. Cố đẩy trong trường hợp này sẽ làm mẻ tròng.</li></ul><h3><strong>3. Sau khi tháo ra, tôi nên bảo quản tròng kính như thế nào?</strong></h3><p>Bạn nên bọc tròng kính trong khăn vải microfiber sạch và cất vào hộp cứng hoặc ngăn chứa có lót mềm,. Tránh để tròng kính ở nơi có nhiệt độ cao (như cốp xe máy, taplo ô tô) hoặc nơi có ánh nắng trực tiếp vì nhiệt độ có thể làm hỏng lớp phủ hoặc làm cong vênh tròng kính.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-7.jpg\" alt=\"Nên bảo quản tròng kính sau khi tháo lắp trên vải Micro Fiber\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-7.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/02/cach-thao-mat-kinh-ra-khoi-gong-an-toan-de-lam-tai-nha-7-600x480.jpg 600w\" sizes=\"100vw\" width=\"900\" height=\"720\"><i>Sau khi tháo tròng, hãy bọc chúng bằng khăn Micro Fiber chuyên dụng</i></p><h3><strong>4. Tự tháo tròng kính tại nhà có làm mất bảo hành không?</strong></h3><p>Thường là có. Hầu hết các chính sách bảo hành của nhà sản xuất chỉ bao gồm lỗi kỹ thuật hoặc vật liệu, không bao gồm các hư hỏng do người dùng tự ý can thiệp hoặc sửa đổi (như tự tháo lắp làm gãy gọng hoặc xước tròng). Nếu bạn làm gãy gọng hoặc vỡ tròng trong quá trình tự tháo, bạn sẽ không được bảo hành.</p><h3><strong>5. Trường hợp nào cần sự trợ giúp của chuyên gia?</strong></h3><p>Bạn nên tìm tới những địa chỉ uy tín, có đội ngũ kỹ thuật viên giàu chuyên môn để được hỗ trợ trong một số trường hợp:</p><ul><li>Gọng kính của bạn là gọng khoan hoặc có thiết kế không vít phức tạp.</li><li>Bạn đang sử dụng tròng chiết suất cao, mỏng và dễ mẻ cạnh.</li><li>Kính của bạn đã bị cong vênh, biến dạng từ trước.</li></ul><p>Tại Kính Hải Triều, chúng tôi hỗ trợ vệ sinh chuyên sâu, bảo trì gọng kính trọn đời. Với đội ngũ cử nhân Khúc xạ nhãn khoa giàu kinh nghiệm, chiếc kính của bạn sẽ được chăm sóc bằng các thiết bị chuyên dụng.</p><p>Việc biết cách tháo mắt kính ra khỏi gọng an toàn là một kỹ năng cần thiết để bạn chủ động bảo vệ tuổi thọ cho chiếc kính của mình. Tuy nhiên, nếu bạn sở hữu những dòng gọng kính cao cấp hoặc tròng kính kỹ thuật số đắt tiền, đừng ngần ngại tìm đến sự trợ giúp chuyên nghiệp để tránh những rủi ro không đáng có.</p><p>Bạn đang gặp khó khăn với chiếc kính của mình? Hãy đến ngay Kính Hải Triều để được chuyên gia tư vấn và hỗ trợ!</p>', 'PUBLISHED', 1, '2026-01-12 10:37:31', '2026-01-12 10:37:31', '2026-04-03 23:43:09');
INSERT INTO `posts` (`id`, `category_id`, `title`, `slug`, `thumbnail_url`, `summary`, `content`, `status`, `created_by`, `published_at`, `created_at`, `updated_at`) VALUES
(1008, 1002, 'Hướng dẫn chọn màu kính phù hợp với da từ A-Z', 'huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z', 'bv3.jpg', 'Chọn màu kính phù hợp với da: Những điều bạn cần biết   Một chiếc kính phù hợp sẽ cộng hưởng với sắc tố da, giúp làn da trông rạng rỡ, khỏe mạnh và che đi các khuyết điểm như quầng thâm hay nếp nhăn. Ngược lại, màu kính xung đột sẽ tạo ra các bóng mờ không mong muốn, khiến gương mặt trông thiếu sức sống.   1. Xác định tông da (Undertone)   Sai lầm phổ biến nhất của người dùng là chỉ chọn kính dựa trên màu da bề mặt (Surface Tone). Để chọn màu kín', '<h2><strong>Chọn màu kính phù hợp với da: Những điều bạn cần biết</strong></h2><p>Một chiếc kính phù hợp sẽ cộng hưởng với sắc tố da, giúp làn da trông rạng rỡ, khỏe mạnh và che đi các khuyết điểm như quầng thâm hay nếp nhăn. Ngược lại, màu kính xung đột sẽ tạo ra các bóng mờ không mong muốn, khiến gương mặt trông thiếu sức sống.</p><h3><strong>1. Xác định tông da (Undertone)</strong></h3><p>Sai lầm phổ biến nhất của người dùng là chỉ chọn kính dựa trên màu da bề mặt (Surface Tone). Để chọn màu kính chính xác tuyệt đối, bạn cần hiểu rõ về Undertone – sắc tố bất biến nằm dưới lớp biểu bì.</p><figure class=\"table\"><table><tbody><tr><td><strong>Đặc điểm</strong></td><td><strong>Surface Tone (Màu da bề mặt)</strong></td><td><strong>Undertone (Sắc tố dưới da)</strong></td></tr><tr><td><strong>Định nghĩa</strong></td><td>Màu da quan sát bằng mắt thường (trắng, ngăm, nâu).</td><td>Sắc tố quyết định bởi mạch máu và melanin ẩn dưới da.</td></tr><tr><td><strong>Tính chất</strong></td><td>Thay đổi theo thời tiết, ánh sáng hoặc lão hóa.</td><td><strong>Bất biến</strong> theo thời gian.</td></tr><tr><td><strong>Phân loại</strong></td><td>Sáng, Trung bình, Ngăm, Đen.</td><td>Lạnh (Cool), Ấm (Warm), Trung tính (Neutral).</td></tr></tbody></table></figure><h3><strong>2. Cách kiểm tra tông da qua mạch cổ tay đơn giản tại nhà</strong></h3><p>Kiểm tra mạch máu là cách thức kinh điển dựa trên nguyên lý tán xạ ánh sáng để xác định tông da:</p><ul><li>Tông da Lạnh (Cool Undertone): Các tĩnh mạch ở cổ tay có màu xanh dương hoặc tím. Da thường có sắc đỏ hoặc hồng.</li><li>Tông da Ấm (Warm Undertone): Tĩnh mạch có màu xanh lá cây hoặc xanh ô-liu. Đây là hiệu ứng khi ánh sáng xanh dương của mạch máu nhìn qua lớp da có sắc tố vàng.</li><li>Tông da Trung tính (Neutral Undertone): Khó phân biệt rõ ràng giữa xanh lá và xanh dương, hoặc mạch máu trùng hoàn toàn vào màu da.</li></ul><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-1.jpg\" alt=\"Làm sao để chọn màu kính phù hợp với da? Cách xác định tông da đơn giản\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-1.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Bạn có thể dễ dàng xác định tông da bằng cách xem màu mạch máu dưới cổ tay</i></p><h2><strong>Hướng dẫn chọn màu gọng kính theo từng sắc tố da phổ biến</strong></h2><p>Mọi thứ bạn biết về cách chọn màu kính có thể đã hoàn toàn sai lầm! Với cương vị là chuyên gia trong lĩnh vực mắt kính cao cấp tại Kính Hải Triều, tôi sẽ chỉ cho bạn bí mật chọn màu gọng theo sắc tố da giúp gương mặt sáng bừng sức sống.</p><h3><strong>1. Da trắng sáng: Tự do phá cách với mọi bảng màu</strong></h3><p>Những người sở hữu làn da trắng thường có nhiều lựa chọn, nhưng bí quyết để không bị “nhợt nhạt” nằm ở việc chọn đúng độ bão hòa màu.</p><ul><li><strong>Da trắng tông lạnh (Cool):</strong> Ưu tiên các màu có gốc xanh dương như đen, bạc, xám ghi hoặc xanh navy để tạo sự tương phản sắc nét.</li><li><strong>Da trắng tông ấm (Warm):</strong> Những gam màu như vàng hồng, vàng nhạt hoặc các tông màu đào sẽ giúp da trông hồng hào và đầy sức sống.</li></ul><p>Lưu ý: Tránh các màu quá tối hoặc gọng nhựa đen dày nếu bạn có khuôn mặt nhỏ, vì chúng có thể “nuốt chửng” các đường nét tự nhiên.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-2.jpg\" alt=\"Người có tông da trắng thường hợp với những mẫu kính gọng đen\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-2-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-2-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-2-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Gọng kính đen là lựa chọn phù hợp cho người có tông da trắng</i></p><h3><strong>2. Da trung tính (da vàng): Ưu tiên các tông màu trung tính và nâu đồi mồi</strong></h3><p>Đây là sắc tố da phổ biến nhất của người Việt Nam, thường thuộc nhóm Warm hoặc Neutral-Warm.</p><ul><li>Màu sắc khuyên dùng: Màu nâu Socola, xanh rêu đậm hoặc đỏ Burgundy là “vũ khí” giúp da trông trắng sáng và sang trọng hơn.</li><li>Họa tiết Đồi mồi (Tortoise): Sự pha trộn giữa sắc nâu, vàng và đen là lựa chọn hoàn hảo giúp gương mặt vừa mang nét trí thức vừa cổ điển.</li></ul><p>Lưu ý: Hạn chế màu vàng chanh hoặc các màu Nude quá trùng với màu da vì sẽ khiến khuôn mặt bị “chìm” và trông vàng hơn.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-3.jpg\" alt=\"Họa tiết đồi mồi là lựa chọn lý tưởng dành cho tông da trung tính\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-3.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-3-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-3-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-3-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Làm thế nào để chọn màu kính phù hợp với da trung tính? Gọng đồi mồi là đáp án thích hợp nhất</i></p><h2><strong>Da ngăm nên chọn gọng kính màu gì để sáng da và thời thượng?</strong></h2><p>Với kinh nghiệm tiếp xúc và tư vấn cho hàng nghìn khách hàng, tôi nhận thấy nỗi lo âu chung của người da ngăm là sợ bị tối mặt khi đeo kính. Nhưng thực tế, da ngăm lại là nền tảng cho những gam màu rực rỡ, giúp bạn thêm phần tự tin.</p><h3><strong>1. Những gam màu “cứu cánh”: Vàng đồng, đồng thau, ghi xám</strong></h3><p>Sự tương phản cao chính là chìa khóa để làm nổi bật thần thái của người da ngăm.</p><ul><li>Màu vàng (Gold): Lựa chọn số 1 giúp làn da rạng rỡ hơn dưới ánh sáng.</li><li>Màu đỏ Ruby hoặc xanh Cobalt: Những sắc độ mạnh này tạo hiệu ứng Pop-up hiện đại, cực kỳ tôn da trên nền da nâu.</li><li>Xám chì: Mang lại vẻ đẹp hiện đại, tinh tế và nam tính mà không gây xung đột với tông da ấm.</li></ul><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-4.jpg\" alt=\"Người da ngăm nên chọn màu kính phù hợp với da như thế nào? Kính gọng vàng là đáp án hoàn hảo\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-4.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-4-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-4-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-4-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Các gam màu vàng, vàng đồng rất phù hợp với người có nước da ngăm</i></p><h3><strong>2. Tại sao da ngăm nên tránh những màu kính quá sặc sỡ hoặc màu bạc sáng?</strong></h3><p>Một số màu sắc có thể tạo ra tác dụng ngược, làm da trông xám xịt hoặc mệt mỏi:</p><ul><li>Màu bạc sáng: Thường xung đột mạnh với làn da ngăm, khiến gương mặt trông lạnh lẽo và thiếu sức sống.</li><li>Màu nâu đục: Các tông màu trùng với màu da sẽ làm khuôn mặt bị tối đi và thiếu điểm nhấn.</li></ul><h3><strong>3. Gợi ý các mẫu gọng kính Titanium mờ dành cho người da ngăm</strong></h3><p>Mặc dù chất liệu bóng giúp bắt sáng tốt, nhưng gọng Titanium mờ lại mang đến phong cách thể thao, mạnh mẽ và công nghệ cho người có làn da ngăm.</p><p>Ưu điểm: <a href=\"https://kinhhaitrieu.com/titanium-la-gi-uu-nhuoc-diem\">Titanium</a> màu Gunmetal hoặc xám than Graphite giúp tạo nên vẻ ngoài vững chãi, tin cậy.</p><p>Theo kinh nghiệm tư vấn của tôi, nếu bạn chọn gọng nhám, hãy ưu tiên thiết kế có các chi tiết kim loại sáng nhỏ ở <a href=\"https://kinhhaitrieu.com/ban-le-mat-kinh\">bản lề</a> hoặc <a href=\"https://kinhhaitrieu.com/tim-hieu-cac-kieu-cau-mui-co-tren-mat-kinh-uu-nhuoc-diem\">cầu kính</a> để duy trì độ tươi tỉnh cho gương mặt. Đối với da lão hóa hoặc da quá tối, tôi khuyên dùng bề mặt bóng (Glossy finish) để tối ưu hiệu ứng làm sáng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-5.jpg\" alt=\"Gọng Titanium màu xám graphite phù hợp với người có nước da ngăm\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-5.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-5-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-5-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-5-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\">Nếu bạn có nước da ngăm, hoàn toàn có thể cân nhắc các mẫu gọng Titanium màu xám graphite</p><h2><strong>Bí thuật phối màu gọng kính với màu tóc</strong></h2><p>Nếu màu da là lớp nền hoàn hảo, thì màu tóc chính là khung tranh định hình phong cách. Đừng để chiếc kính đắt tiền bị ‘lạc quẻ’ chỉ vì không ăn nhập với mái tóc của bạn. Để dễ hình dung, tôi sẽ bật mí cho bạn những bí quyết phối màu gọng kính với màu tóc mà không phải ai cũng biết!</p><h3><strong>1. Tóc đen: Màu kính tạo điểm nhấn cá tính</strong></h3><p>Tóc đen tạo ra độ tương phản tự nhiên cao và khung viền sắc nét cho khuôn mặt. Bạn có thể chọn kính màu đen hoặc đồi mồi tối để tạo vẻ ngoài chuyên nghiệp, nghiêm túc và liền mạch cho môi trường công sở.</p><p>Ngoài ra, nếu bạn muốn tạo điểm nhấn thời trang, các màu rực rỡ như đỏ tươi, xanh dương, tím hoặc các màu Neon là sự lựa chọn phù hợp.</p><p>Gợi ý chất liệu: Gọng kính kim loại màu bạc hoặc vàng sẽ nổi bật rõ rệt trên nền tóc đen.</p><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/7BR5zCbp9yE/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>Cùng Kính Hải Triều khám phá TOP 5 mẫu gọng kính nam dẫn đầu xu hướng</i></p><h3><strong>2. Tóc nâu/đỏ: Màu kính ấm áp, sang trọng</strong></h3><p>Đây là nhóm màu tóc đa dạng nhất, đòi hỏi sự tinh tế trong việc chọn tông nóng – lạnh:</p><ul><li>Tóc nâu ấm (nâu hạt dẻ, ánh vàng/đỏ): Hợp nhất với gọng kính màu vàng, đỏ, nâu hoặc hổ phách.</li><li>Tóc nâu lạnh (ánh khói, nâu đen lì): Ưu tiên gọng kính màu đen, xanh dương, hồng hoặc bạc.</li><li>Tóc đỏ: Màu xanh lá cây là lựa chọn tuyệt vời nhất vì là màu bổ sung của màu đỏ trên bánh xe màu sắc. Ngoài ra, màu đồng hoặc cam cháy cũng tạo sự hòa hợp rất tốt.</li></ul><p>Lưu ý: Gọng đồi mồi (Tortoiseshell) là sự lựa chọn hoàn hảo dành tóc nâu, tạo hiệu ứng tone-sur-tone cực kỳ sang trọng.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-6.jpg\" alt=\"Gọng đồi mồi là sự kết hợp hoàn hảo với người có màu tóc nâu\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-6.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-6-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-6-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2025/12/huong-dan-chon-mau-kinh-phu-hop-voi-da-tu-a-z-6-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Gọng kính đồi mồi sinh ra là dành cho mái tóc nâu</i></p><h3><strong>3. Tóc vàng/bạch kim: Màu kính hiện đại, nổi bật</strong></h3><p>Mái tóc sáng màu đóng vai trò như một tấm canvas trắng, cho phép bạn đeo các màu kính tươi sáng mà không sợ rối mắt.</p><ul><li>Tóc vàng mật ong: Chọn gọng màu Nâu, Hồng đào (Coral) hoặc Đồi mồi sáng.</li><li>Tóc bạch kim/vàng khói: Đẹp nhất với gọng Xanh dương, Đen hoặc các màu tông lạnh/Pastel.</li></ul><p>Lưu ý: Tránh màu Vàng nhạt hoặc Bạc quá mảnh vì kính sẽ bị “lẫn” vào màu tóc, làm khuôn mặt nhạt nhòa.</p><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/AXFOf6DYD5E/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>Tìm hiểu TOP 3 gọng kính hàng hiệu hot nhất Việt Nam</i></p><h2><strong>5 sai lầm phổ biến khi chọn màu kính mà bạn nên tránh</strong></h2><p>Đắt tiền không đồng nghĩa với đẹp nếu bạn mắc phải những lỗi phối màu sơ đẳng này. Dưới đây là 5 sai lầm kinh điển mà ngay cả những người sành sỏi cũng thường mắc phải:</p><ul><li><strong>Chọn màu kính trùng khít màu da:</strong> Chọn màu trùng hoàn toàn màu da làm chiếc kính bị “tàng hình”, khiến khuôn mặt trông mệt mỏi và đơn điệu.</li><li><strong>Bỏ qua chất liệu và bề mặt:</strong> Gọng kính đen nhám (Matte Black) trên làn da ngăm hoặc da lão hóa sẽ làm khuôn mặt tối tăm và già đi.</li><li><strong>Chạy theo “Trend” mà phớt lờ Undertone:</strong> Ví dụ, da vàng sậm cố đeo kính Rose Gold tông lạnh sẽ khiến da trông vàng vọt, thiếu sức sống.</li><li><strong>Tư duy “Màu đen hợp với tất cả”:</strong> Với người da quá trắng xanh hoặc khuôn mặt nhỏ, kính gọng nhựa đen dày sẽ “nuốt chửng” khuôn mặt, tạo cảm giác nặng nề.</li><li><strong>Không tính đến tủ quần áo và môi trường:</strong> Chọn kính đỏ rực rỡ nhưng tủ đồ toàn màu cam sẽ gây ra sự lệch tông khó chịu trong trang phục hàng ngày.</li></ul><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/qEh0aI13kvo/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>ĐỪNG BỎ QUA nếu bạn vẫn chưa biết cách đo kích thước mắt kính chính xác!</i></p><h2><strong>Câu hỏi thường gặp khi chọn màu kính phù hợp với da</strong></h2><p>Đọc đến đây, hẳn bạn đã biết bí quyết chọn màu kính phù hợp với da. Nhưng trước khi đưa ra quyết định, hãy cùng tôi dành ra 1 phút để giải đáp các câu hỏi sau, bởi chúng sẽ giúp bạn mua hàng với 100% sự tự tin.</p><h3><strong>1. Da ngăm đeo kính màu đen có bị tối mặt không?</strong></h3><p>Đây là nỗi lo phổ biến nhất của những người sở hữu làn da bánh mật, nhưng câu trả lời nằm ở hiệu ứng bề mặt (finish) của chất liệu.</p><p><strong>Sai lầm cần tránh:</strong> Gọng kính màu đen nhám trên làn da ngăm có thể làm khuôn mặt trông tối tăm, thiếu sức sống và khiến bạn trông già đi. Những màu nâu trùng hoàn toàn với sắc tố da cũng sẽ làm khuôn mặt bị “chìm” và thiếu điểm nhấn.</p><p><strong>Lời khuyên của tôi:</strong> Bạn hoàn toàn có thể đeo kính đen nếu chọn bề mặt nhựa bóng <a href=\"https://kinhhaitrieu.com/acetate-la-gi-uu-nhuoc-diem\">Acetate</a> hoặc kim loại sáng để bắt sáng tốt hơn, tạo hiệu ứng tươi tỉnh cho gương mặt.</p><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/5DkHDyEpmdM/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>Tìm hiểu A-Z các chất liệu gọng kính nên đeo nhất hiện nay</i></p><h3><strong>2. Có nên chọn màu kính trùng với màu mắt không?</strong></h3><p>Câu trả lời là CÓ. Đây được coi là một chiến lược phối màu tinh tế và cực kỳ hiệu quả để nhấn mạnh vẻ đẹp tự nhiên của đôi mắt.</p><ul><li><strong>Mắt nâu hoặc đen (đặc trưng của người Châu Á):</strong> Việc đeo kính màu đồi mồi, vàng nâu hoặc đen sẽ giúp đôi mắt trông sâu, có hồn và cuốn hút hơn.</li><li><strong>Mắt xanh hoặc xám (khi đeo kính áp tròng màu)</strong>: Lựa chọn gọng kính có tông màu tương đồng (tone-on-tone) như xám hoặc xanh dương sẽ làm màu mắt như “phát sáng” và trở nên trong trẻo hơn.</li></ul><h3><strong>3. Làm thế nào để màu kính hài hòa với cả màu tóc và màu da?</strong></h3><p>Để chiếc kính trở thành cầu nối hoàn hảo giữa màu da và mái tóc, bạn cần áp dụng quy trình phân tích tổng hòa:</p><ul><li>Ưu tiên Undertone của da: Đây là yếu tố bất biến và gốc rễ nhất. Hãy chọn bảng màu phù hợp với tông da (ấm, lạnh hoặc trung tính) trước khi xét đến màu tóc.</li><li>Tạo dòng chảy với màu tóc: Tóc đen hoặc nâu đậm có thể đi cùng gọng kính màu tối để tạo sự chuyên nghiệp (Monochrome), hoặc màu rực rỡ để tạo điểm nhấn thời trang (High Contrast).</li><li>Giải pháp cho người hay đổi màu tóc: Nếu bạn thường xuyên nhuộm tóc, hãy chọn những màu kính trung tính như đen hoặc đồi mồi vì chúng có khả năng thích nghi cao nhất với mọi sắc độ tóc từ bạch kim đến nâu đỏ.</li></ul><h2><strong>Lời kết</strong></h2><p>Việc lựa chọn màu kính phù hợp với da không chỉ dừng lại ở sở thích cá nhân mà là một quá trình phân tích tổng hòa giữa khoa học màu sắc, đặc điểm sinh học (Undertone) và phong cách sống. Một chiếc kính được chọn lựa kỹ lưỡng sẽ là công cụ đắc lực để nâng tầm diện mạo, khéo léo che giấu khuyết điểm và khẳng định vị thế cá nhân.</p><p>Tuy nhiên, mọi quy tắc đều có ngoại lệ. Trong thế giới thời trang cao cấp, sự tự tin của người đeo mới là yếu tố quyết định vẻ đẹp cuối cùng. Đôi khi, việc phá vỡ quy tắc một cách có chủ đích thông qua việc thử trực tiếp nhiều màu sắc mới lạ lại chính là cách để bạn kiến tạo nên một phong cách cho riêng mình.</p><p>Đến ngay showroom Kính Hải Triều – đại lý chính hãng ủy quyền của 30+ thương hiệu mắt kính cao cấp để trải nghiệm và nhận sự tư vấn từ đội ngũ chuyên gia nhé!</p>', 'PUBLISHED', 1, '2026-01-13 10:40:31', '2026-01-13 10:40:31', '2026-04-03 23:45:43'),
(1009, 1009, 'TOP 7 gọng kính cận nữ mặt tròn đẹp theo xu hướng 2026', 'top-7-gong-kinh-can-nu-mat-tron-dep-theo-xu-huong-2026', 'bv4.jpg', 'Nguyên tắc chọn gọng kính cận nữ mặt tròn   Gương mặt tròn đặc trưng bởi sự cân đối giữa chiều dài và chiều rộng. Chị em mặt tròn có đường xương hàm viền cong mềm mại, thường gắn liền với nét đẹp ngọt ngào, trong sáng. Ngũ quan nhỏ nhắn giúp người mặt tròn trông trẻ hơn so với tuổi, nhưng khi đeo kính, bạn cần chọn gọng thật khéo để khuôn mặt không bị đầy và kém cân đối hơn.  Hãy để kính cận trở thành điểm nhấn tinh tế trên khuôn mặt nữ tròn bằng', '<h2><strong>Nguyên tắc chọn gọng kính cận nữ mặt tròn</strong></h2><p>Gương mặt tròn đặc trưng bởi sự cân đối giữa chiều dài và chiều rộng. Chị em mặt tròn có đường xương hàm viền cong mềm mại, thường gắn liền với nét đẹp ngọt ngào, trong sáng. Ngũ quan nhỏ nhắn giúp người mặt tròn trông trẻ hơn so với tuổi, nhưng khi đeo kính, bạn cần chọn gọng thật khéo để khuôn mặt không bị đầy và kém cân đối hơn.</p><p>Hãy để kính cận trở thành điểm nhấn tinh tế trên khuôn mặt nữ tròn bằng cách nhớ rõ nguyên tắc sau: <strong>Sự tương phản tạo nên sự cân bằng</strong></p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2.jpg\" alt=\"Nữ mặt tròn đeo kính hình vuông theo nguyên tắc tương phản\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-1-2-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Nguyên tắc vàng khi chọn kính: Tương phản để cân bằng</i></p><p>Trong nguyên tắc này, bạn chỉ cần chọn <a href=\"https://kinhhaitrieu.com/danh-muc/gong-kinh-can/gong-kinh-can-nu\">gọng kính cận nữ</a> có hình dạng trái ngược với dáng mặt là được. Mặt mềm mại chọn kính góc cạnh, mặt góc cạnh chọn kính mềm mại. Với nữ mặt tròn, bạn nên ưu tiên gọng vuông, chữ nhật hoặc đa giác nhẹ. Các đường thẳng và góc cạnh giúp giảm cảm giác đầy ở hai bên má, đồng thời làm khung mặt trông gọn và rõ nét hơn khi đeo kính.</p><p>Bạn hãy nhấp vào <a href=\"https://kinhhaitrieu.com/cach-chon-kinh-phu-hop-voi-khuon-mat-nu\">cách chọn kính phù hợp với khuôn mặt nữ</a> để hiểu rõ hơn về những nguyên tắc chọn mắt kính được tư vấn từ chuyên gia.</p><h2><strong>7 dáng gọng kính cận nữ mặt tròn đang được yêu thích</strong></h2><p>Dưới đây là những gọng kính cận nữ mặt tròn thịnh hành trên thị trường hiện nay, đặc biệt là tại Kính Hải Triều.</p><h3><strong>1. Gọng kính mắt mèo sắc sảo</strong></h3><p>Gọng mắt mèo là một trong những dáng kính thường được chọn bởi chị em yêu thích sự quyến rũ và thanh lịch. Điểm đắt giá của kiểu gọng này nằm ở phần đuôi hơi xếch lên, giúp thu hút ánh nhìn vào phần chân mày và đuôi mắt. Chính điều này làm giảm sự chú ý vào gò má đầy của gương mặt tròn, từ đó giúp khuôn mặt trông thon và sắc nét hơn.</p><p>Trong các bộ sưu tập gọng mắt mèo cao cấp, <strong>Versace</strong> là cái tên đáng chú ý nhất cho nàng mặt tròn đang tìm sự sắc sảo pha chút cổ điển. Những mẫu mắt mèo từ Versace thường có phần đuôi kính nâng vừa phải, đủ để kéo ánh nhìn lên phần chân mày mà không tạo cảm giác quá sắc hay mất đi nét nữ tính. Đây là kiểu kính mà bạn đeo lên là khuôn mặt thay đổi ngay, không cần thêm phụ kiện nào khác.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1.jpg\" alt=\"Gương mặt nữ tròn trở nên sắc sảo hơn với gọng mắt mèo\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-2-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Thanh lịch cổ điển pha chút sắc sảo, gương mặt giữ trọn nét nữ tính</i></p><p>Nếu bạn muốn một dáng mắt mèo có chiều sâu thương hiệu hơn, Burberry và Gucci là hai cái tên đáng dừng lại. <strong>Burberry</strong> xử lý dáng mắt mèo theo hướng Restrained Elegance (Thanh lịch hạn chế). Các dòng kính của họ thường có đường đuôi kính nâng vừa đủ, không phô, phù hợp với phong cách công sở lịch lãm hoặc những nàng muốn sự tinh tế làm ngôn ngữ chính.</p><p><strong>Gucci</strong> thì đa dạng hơn nhiều: Từ dòng mắt mèo cổ điển đến những thiết kế có điểm nhấn logo tinh tế. Hầu hết các sản phẩm từ Gucci đều đủ góc cạnh để tạo cấu trúc tốt cho khuôn mặt tròn, trong khi vẫn giữ được cái duyên dáng đặc trưng của nhà mốt Ý.</p><p>Dù chị em chọn thương hiệu nào, nguyên tắc vẫn là một: Ưu tiên phom có góc cạnh ở đuôi kính, đó là chi tiết tạo ra sự khác biệt thực sự trên khuôn mặt tròn</p><h3><strong>2. Gọng kính chữ nhật hiện đại</strong></h3><p>Một trong những gọng kính cận nữ mặt tròn đẹp và dễ đeo nhất chính là gọng chữ nhật hiện đại. Những đường thẳng và bo góc tinh tế của dáng kính này giúp cân bằng lại các đường nét mềm vốn có trên khuôn mặt tròn. Kính chữ nhật được lòng nhiều chị em vì tính linh hoạt cao của nó. Rất dễ ứng dụng trong nhiều hoàn cảnh, từ đi học, đi làm cho đến đeo hằng ngày. Nếu bạn thích sự gọn gàng, bền đẹp và hiện đại thì đây là dáng kính rất đáng để bắt đầu.</p><p>Tìm hiểu về gọng kính cận nữ mặt tròn dáng chữ nhật, có những cái tên nổi bật mà tôi nghĩ đến đầu tiên đó là Montblanc, Oakley và Exfash.</p><p>Gọng kính cận nữ mặt tròn của <strong>Montblanc</strong> được kết hợp giữa nhựa <a href=\"https://kinhhaitrieu.com/acetate-la-gi-uu-nhuoc-diem\">Acetate</a> cao cấp và Titanium. Trên càng kính thường có biểu tượng ngôi sao 6 cánh nhỏ đầy tinh tế. Với cấu trúc gọng chữ nhật hẹp theo chiều đứng, giúp gương mặt tròn “kéo dài” về mặt thị giác.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1.jpg\" alt=\"Nữ mặt tròn đeo kính chữ nhật trẻ trung, hiện đại\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-3-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Tinh tế vượt thời gian, nhẹ nhàng tôn nét tròn đầy</i></p><p>Điểm chung về kính chữ nhật của <strong>Oakley</strong> là các đường nét công nghiệp cứng cáp, giúp gương mặt tròn trông năng động và bớt mềm yếu. Một điểm đặc biệt về gọng kính của Oakley là sử dụng chất liệu độc quyền O-Matter và đệm tai Unobtainium giúp tăng độ bám.</p><p>Bộ sưu tập gọng kính cận nữ mặt tròn của <strong>Exfash</strong> chủ yếu là gọng kim loại mảnh với thiết kế Minimalist (phong cách tối giản) không viền hoặc viền cực nhỏ. Điều này làm gương mặt không bị “bí”, tạo cảm giác nhẹ nhàng, nữ tính nhưng vẫn có độ thẳng của khung kính.</p><p>Tuy nhiên khi xét về tính ứng dụng, thì dù là thương hiệu nào thì gọng kính chữ nhật hiện đại vẫn luôn phát huy hiệu quả trong việc tái cấu trúc gương mặt tròn.</p><h3><strong>3. Gọng kính vuông bản to</strong></h3><p>Gọng vuông bản to được nhiều chị em chọn vì nó tạo khung mặt rất tốt cho mặt tròn. Với gương mặt có phần má đầy hoặc đường nét khá mềm, một chiếc gọng có viền rõ và bản gọng đủ hiện diện sẽ giúp tổng thể trông sắc hơn ngay lập tức. Theo quan sát cá nhân, tôi thấy dáng gọng này đặc biệt phù hợp với những chị em đang theo đuổi phong cách cá tính nhưng vẫn cân đối hài hoà.</p><p>Trong bộ sưu tập gọng kính cận nữ mặt tròn này, <strong>Gucci</strong> nổi bật lên với thiết kế góc vuông 90 độ sắc nét, đối lập hoàn hảo với đường cong của má, tạo cảm giác khung mặt rõ và cân đối hơn.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1.jpg\" alt=\"Nữ mặt tròn đeo kính gọng vuông bản to cực hợp\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-4-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Gương mặt tròn thêm cân đối và cuốn hút với gọng kính vuông</i></p><p>Còn nếu bạn thích là người dẫn đầu, thì hãy đến với những chiếc kính của <strong>Molsion</strong>. Molsion nổi bật với thiết kế Trend-forward (xu hướng tiên phong), thường dùng nhựa trong hoặc màu trà nhẹ cho gọng kính. Gọng màu trong suốt giúp kính không chiếm không gian mặt quá nhiều, mà vẫn định hình được khuôn vuông cho mặt tròn.</p><p>Tuy mỗi thương hiệu mang một phong cách thiết kế khác nhau, nhưng chị em đều có thể yên tâm vì các gọng kính cận nữ mặt tròn có dáng vuông bản to đáp ứng rất tốt nguyên tắc “tương phản” cho mặt nữ tròn.</p><h3><strong>4. Gọng kính đa giác thanh mảnh</strong></h3><p>Gọng kính đa giác thanh mảnh là xu hướng của những chị em thích sự nhẹ mặt nhưng vẫn muốn có nét riêng. Hiệu ứng mà dáng kính này mang lại thường không quá mạnh như gọng vuông bản to, nhưng lại rất tinh tế và mới mẻ.</p><p>Một chiếc gọng được xem là phù hợp khi tạo được dấu ấn thị giác mà không khiến tổng thể bị nặng nề. Và gọng đa giác thanh mảnh làm khá tốt hai điều này: Vừa đủ sắc để tôn mặt, vừa đủ gọn để giữ cảm giác thanh thoát.</p><p>Nàng mặt tròn đang tìm kiếm gọng kính đa giác có thiết kế phá cách, hiện đại thì hãy quan tâm đến những thương hiệu như Chopard, RayBan hay Eyescloud.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1.jpg\" alt=\"Gọng kính đa giác mảnh mang phong cách công sở\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-5-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Thanh lịch pha chút sắc sảo, đường viền tinh tế giúp gương mặt thon</i></p><p>Càng kính của <strong>Chopard</strong> được lấy cảm hứng từ trang sức Ice Cube cùng các đường cắt đa diện tinh xảo. Những góc cắt li ti của gọng kính tạo ra nhiều điểm phản chiếu, từ đó giúp gương mặt tròn có chiều sâu và đa chiều hơn.</p><p>Khác với Chopard, <strong>RayBan</strong> lại nổi bật với dòng Hexagonal kim loại mảnh kết hợp với biểu tượng dập chìm ở đuôi càng kính. Chiếc kính hình lục giác của RayBan với các cạnh phẳng, sẽ giúp gương mặt tròn của bạn trông sắc sảo và mang hơi hướng cổ điển thời thượng hơn.</p><p>Nếu bạn đang tìm kiếm một chiếc gọng kính cận nữ mặt tròn có thiết kế trẻ trung, dễ đeo trong sinh hoạt hằng ngày, thì <strong>Eyescloud</strong> là thương hiệu lý tưởng dành cho bạn. Các dáng kính đa giác nhẹ nhàng của hãng này sẽ giúp bạn đổi qua phong cách hiện đại, mà không sợ bị “dừ” hay quá cứng nhắc.</p><p>Dù là thương hiệu nào, thì gọng kính đa giác thanh mảnh luôn là sự lựa chọn sáng suốt cho nàng mặt tròn: tinh gọn, dễ đeo, duyên dáng.</p><h3><strong>5. Gọng kính Aviator có Brow Bar</strong></h3><p>Với nàng mặt tròn, Aviator không phải lúc nào cũng dễ đeo. Nhưng nếu là phiên bản có Brow Bar (thanh ngang ở phần trên gọng) rõ và phom gọn, thì đây là một dáng kính rất đáng thử. Điểm quan trọng nhất của kiểu này là phần thanh ngang phía trên. Chi tiết này giúp phần nửa trên gương mặt có điểm tựa thị giác rõ hơn, từ đó làm tổng thể trông cân đối và đỡ mềm hơn.</p><p>Một cái tên không thể không nhắc đến khi nói về gọng kính Aviator chính là <strong>RayBan</strong>. Đây là thương hiệu hiếm hoi bắt nguồn từ nhu cầu của phi công quân sự, rồi trở thành biểu tượng văn hoá đại chúng trên tay rockstar và ngôi sao Hollywood. Với thiết kế tròng kính hình giọt nước hoặc hình vuông, kính cận RayBan phá vỡ nét tròn trịa của khuôn mặt rất hiệu quả.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2.jpg\" alt=\"Kính Aviator của RayBan đầy khí chất\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-6-2-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Phóng khoáng, biểu tượng và đầy khí chất với kính Aviator</i></p><p>Trong bộ sưu tập kính Aviator, thương hiệu <strong>Cartier</strong> nổi lên với những chiếc kính đắt giá, sang trọng. Tựa như được tạo bằng đôi bàn tay tài hoa của “nghệ nhân” nghề kính, những chiếc đinh vít siêu mỏng mạ vàng hoặc platinum được đính trên thanh ngang mang đến sự quý phái tinh tế. Với thanh ngang cao và mảnh, kính Cartier giúp kéo ánh nhìn lên trên, tạo cảm giác trán cao hơn và mặt dài ra.</p><p><strong>Bolon</strong> thì dễ tiếp cận hơn nếu nàng tìm kiếm một thương hiệu ở phân khúc tầm trung. Thiết kế Asian Fit cùng thanh ngang được làm thanh thoát, kết hợp với chất liệu Titanium giúp chiếc gọng cực kỳ nhẹ, không gây áp lực lên gò má khi cười. Từ đó gương mặt nữ tròn giữ được sự nhẹ nhàng, không bị “đè nặng” bởi phụ kiện.</p><p>Cá nhân tôi cảm nhận rằng: Những chị em mặt tròn có cá tính cao và thích thể hiện chất riêng thì gọng kính Aviator có Brow Bar chính là một lựa chọn hoàn hảo.</p><h3><strong>6. Gọng kính Semi-Rimless</strong></h3><p>Semi-Rimless (gọng bán viền) trở thành lựa chọn ưu tiên của nhiều chị em tìm kiếm vẻ ngoài nhẹ nhàng, tinh tế. Kiểu gọng kính cận nữ mặt tròn này đẹp nhất khi phần khung trên đủ rõ để giữ cấu trúc cho khuôn mặt, còn phần dưới được tiết chế để tổng thể không bị nặng. Nhờ đó, chiếc kính vẫn tạo được đường nét cần thiết nhưng không làm gương mặt bị “đè” xuống.</p><p>Hãy chọn bộ sưu tập kính gọng bán viền của <strong>Burberry</strong> nếu bạn thích sự tối giản nhưng vẫn sang trọng. Bạn có thể dễ dàng thấy những chiếc kính có hoạ tiết kẻ sọc Check đặc trưng của Burberry xuất hiện cùng các siêu sao như Beyoncé, Olivia Cooke hay Laura Dern.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1.jpg\" alt=\"Gọng kính bán viền rất tiện lợi khi đeo thường ngày\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-7-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Hiện đại, tinh gọn giữ nét thanh lịch</i></p><p>Nếu bạn ưu tiên một sản phẩm tập trung vào công năng, thì các thiết kế kính của <strong>Agog</strong> làm rất tốt điều đó. Gọng kính cận nữ mặt tròn Agog thường có cầu kính cao giúp sống mũi trông cao hơn, từ đó thu hẹp khoảng cách hai mắt làm cho mặt bớt tròn.</p><p>Riêng tôi lại thích thương hiệu <strong>Prada</strong>. Kính bán viền của thương hiệu này có phần khung trên dày bằng nhựa màu đậm, tạo nên một đường chân mày giả sắc nét, từ đó kéo sự chú ý khỏi phần hàm tròn và làm mặt trông dài hơn.</p><h3><strong>7. Gọng kính Flat-Top</strong></h3><p>Flat-Top (gọng có phần viền trên đi ngang rõ) là dáng kính tôi đánh giá khá cao khi chọn gọng kính cận nữ mặt tròn. Bởi vì kiểu kính này không chỉ giúp giảm bớt độ tròn, mà còn tăng chất cá tính của các nàng. Cái hay của kiểu này nằm ở phần viền trên đi ngang gần như dứt khoát, tạo ra một đường mạnh ở vùng chân mày. Với gương mặt tròn, đây là chi tiết rất đáng giá vì nó giúp nửa trên gương mặt có cấu trúc rõ hơn, khiến tổng thể bớt “hiền” và bớt tròn đều.</p><p>Nếu bạn thích phong cách năng động và muốn một dáng Flat-top dễ đeo hằng ngày, <strong>Oakley</strong> là lựa chọn đáng tham khảo. Điểm nên ưu tiên ở nhóm này không nằm ở tên thương hiệu, mà là phần viền trên rõ, phom gọng chắc và độ ôm mặt ổn khi đeo lâu.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2.jpg\" alt=\"Nữ mặt tròn đeo kính Flat-top đầy cá tính\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2.jpg 900w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-8-2-100x100.jpg 100w\" sizes=\"100vw\" width=\"900\" height=\"900\"><i>Flat-top mang tinh thần năng động, phóng khoáng và đầy tự do</i></p><p><strong>Molsion</strong> thì mang một phong cách khác: Phóng khoáng và bụi bặm hơn. Gọng Flat-top của thương hiệu này thường đi kèm với các tông màu thời thượng như xám trong suốt hoặc đồi mồi sáng, thể hiện tinh thần trẻ trung hiện đại. Phần đỉnh phẳng của gọng kính thường giúp “ngắt” bớt độ tròn của trán và thái dương, tạo diện mạo thon dài cho tổng thể.</p><p>Bộ sưu tập gọng kính cận nữ mặt tròn Flat-top thì không thể thiếu <strong>Dior</strong>, một thương hiệu thời trang nổi tiếng. Với thiết kế nổi bật ở đường viền phía trên phẳng tuyệt đối, chiếc kính Dior giúp tổng thể gương mặt trông sắc nét và chỉn chu hơn.</p><p>Khi chọn Flat-top cho mặt tròn, bạn nên ưu tiên gọng có chiều ngang nhỉnh hơn phần gò má một chút. Nếu gọng quá hẹp, hai bên má sẽ lộ rõ hơn. Nếu quá to và quá tròn, khuôn mặt lại dễ bị đầy.</p><h2><strong>Xu hướng 2026 – Các kiểu gọng kính nữ nổi bật</strong></h2><p>Thời trang là lĩnh vực luôn thay đổi và cập nhật, điều này bao gồm kính cận. Nếu vài năm trước những mẫu kính nhỏ, tối giản được ưa chuộng mạnh, thì sang 2026, xu hướng lại gọi tên các kiểu gọng hiện đại và cá tính hơn.</p><h3><strong>1. Gọng oversize quay trở lại mạnh mẽ</strong></h3><p>Oversize không phải xu hướng mới, nhưng 2026 là năm nó quay trở lại theo cách đáng chú ý hơn hẳn. Tạp chí InStyle gọi những chiếc kính bản lớn, che gần hết gò má là món phụ kiện không thể bỏ qua của mùa xuân năm nay.</p><p>Trên sàn diễn quốc tế, Celine, Saint Laurent và Balenciaga đều xuất hiện với những chiếc kính chiếm trọn khuôn mặt – không phải vì phô, mà vì đó là cách họ biến chiếc kính thành tuyên ngôn phong cách.</p><p>Với nữ mặt tròn, oversize hoàn toàn có thể đeo đẹp, chỉ cần chọn phom vuông hoặc có góc cạnh thay vì phom tròn bản to. Một chiếc kính lớn đúng dáng sẽ tạo khung mặt ấn tượng, thay vì khiến khuôn mặt trông đầy hơn.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1.jpg\" alt=\"Xu hướng gọng kính oversize được nhiều chị em yêu thích\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-9-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Gọng oversize mang đến vẻ ngoài cá tính và thời thượng</i></p><h3><strong>2. Cat-eye hiện đại tiếp tục được ưa chuộng</strong></h3><p>Từ trải nghiệm tư vấn thực tế, tôi nhận thấy: Kính mắt mèo chưa bao giờ thật sự rời khỏi thời trang. Theo tạp chí Elle, các thiết kế mắt mèo năm nay được gọi bằng tinh thần sculpted lift (Thiết kế tạo khối nâng cao đường nét khuôn mặt). Điểm khác biệt về kính mắt mèo năm nay nằm ở phần đuôi được xử lý gọn, sắc và có tính kiến trúc hơn thay vì cong mềm theo kiểu retro quen thuộc.</p><p>Xu hướng kính mắt mèo xuất hiện vì nhu cầu làm đẹp hiện nay có sự thay đổi. Không chỉ dừng lại ở việc hợp thời mà điều quan trọng là còn phải tôn gương mặt. Nếu những năm trước người ta ưa chuộng trung tính, đơn điệu thì bây giờ kính mắt mèo hiện đại phải tạo được vẻ thanh thoát và nâng được đường nét khuôn mặt. Nói đơn giản hơn, mắt mèo hiện đại không chỉ đẹp mà còn giúp gương mặt trông sắc hơn, đặc biệt với người muốn giữ nét nữ tính nhưng vẫn có điểm nhấn rõ.</p><p>Một trong những thương hiệu kính thể hiện rất rõ tinh thần mắt mèo hiện đại chính là Saint Laurent, với những mẫu kính góc cạnh, sắc nét và giàu tính thời trang. Ở nhóm dễ tiếp cận hơn thì Gentle Monster lại đang làm rất tốt việc đưa dáng mắt mèo vào ngôn ngữ trẻ hơn, mới hơn và hợp thị hiếu châu Á.</p><p>Vậy xu hướng kính mắt mèo có hợp với gọng kính cận nữ mặt tròn không? Câu trả lời là có, chỉ cần bạn chọn mẫu mắt mèo có nhiều đường kẻ thẳng thay vì quá bo tròn.</p><figure class=\"image\"><img style=\"aspect-ratio:480/360;\" src=\"https://i.ytimg.com/vi/Y6dMpGz6XmQ/hqdefault.jpg\" alt=\"YouTube video\" width=\"480\" height=\"360\"></figure><p><i>Xu hướng kính mắt mèo được nhiều người yêu thích vì tính thời trang cao</i></p><h3><strong>3. Gọng kim loại mảnh lên ngôi</strong></h3><p>Nếu kính oversize thiên về sự hiện diện mạnh, thì gọng kim loại mảnh lại nổi lên ở cực còn lại: thanh hơn, tối giản hơn.</p><p>Trong năm 2026, kim loại mảnh là kiểu kính xuất hiện nhiều trong nhóm thời trang thanh lịch. Các báo cáo xu hướng kính cận nữ của Vogue và Fashion United đều cho thấy những thiết kế gọn, mảnh, tinh tế đang dần trở lại song song với xu hướng Office Siren (công sở quyến rũ) và Quiet Luxury (sang trọng tinh giản). Đặc biệt là qua cách Chanel và Dior sử dụng kính kim loại trong các bộ sưu tập gần đây.</p><p>Lý giải cho sự lên ngôi của kiểu gọng này là do ảnh hưởng của lối sống hiện đại. Nơi một chiếc kính phải đẹp nhưng cũng không được gây cảm giác nặng nề khi đeo lâu. Hai trong số nhiều thương hiệu đang dẫn đầu xu hướng gọng kính kim loại mảnh đó là Lindberg và RayBan.</p><p>Khi chọn gọng kính cận mặt nữ tròn, cần lưu ý rằng kim loại mảnh không phải là kiểu kính tôn gương mặt quá tốt. Hãy ưu tiên các phom có góc nhẹ như lục giác, chữ nhật hoặc vuông để mặt tròn trông sắc nét hơn.</p><p><img src=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1.jpg\" alt=\"Gọng kính kim loại mảnh được nhiều chị em chọn vì vẻ đẹp thanh thoát\" srcset=\"https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1.jpg 1200w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1-768x768.jpg 768w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1-300x300.jpg 300w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1-600x600.jpg 600w, https://kinhhaitrieu.com/wp-content/uploads/2026/03/gong-kinh-can-nu-mat-tron-10-1-100x100.jpg 100w\" sizes=\"100vw\" width=\"1200\" height=\"1200\"><i>Thanh thoát, tinh giản nhưng vẫn đủ tinh tế để tôn đường nét gương mặt</i></p><h3><strong>4. Acetate bản rõ giữ vững vị trí trong xu hướng thời trang</strong></h3><p>Dù gọng kim loại mảnh lên ngôi thì Acetate bản to cũng không hề bị hạ nhiệt trong năm 2026. WWD từng nhấn mạnh sự dịch chuyển sang chất liệu Acetate sinh học trong thiết kế của Stella McCartney và Bottega Veneta, điều này cho thấy đây không chỉ là một xu hướng nhất thời, mà còn có thể hướng đến tiêu dùng bền vững hơn.</p><p>Kính Acetate bản rõ được lòng nhiều người hâm mộ vì nó giải quyết được hai nhu cầu cùng lúc. Vừa đủ để hiện diện trên khuôn mặt, nhưng cũng không quá dày và nặng như các mẫu nhựa trước đây. Nhờ khả năng ứng dụng cao, kiểu dáng Acetate bản to đã xuất hiện trong bộ sưu tập của nhiều thương hiệu nổi tiếng như Jacques Marie Mage hay Moscot.</p><p>Xu hướng này chính là tin vui cho những chị em đang tìm kiếm gọng kính cận nữ mặt tròn. Bởi vì kiểu dáng này vừa tạo khung mặt rõ, vừa không làm tổng thể bị nặng, lại vừa hiện đại trẻ trung.</p>', 'PUBLISHED', 1, '2026-01-14 10:37:31', '2026-01-14 10:37:31', '2026-04-03 23:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `post_categories`
--

DROP TABLE IF EXISTS `post_categories`;
CREATE TABLE IF NOT EXISTS `post_categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2000 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_categories`
--

INSERT INTO `post_categories` (`id`, `name`, `slug`, `status`) VALUES
(1001, 'Chưa có chuyên mục', 'chua-co-chuyen-muc', 'ACTIVE'),
(1002, 'Tin mới', 'tin-moi', 'ACTIVE'),
(1009, 'Sản phẩm tốt', 'san-pham-tot', 'ACTIVE');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `product_code` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_id` bigint DEFAULT NULL,
  `category_id` bigint NOT NULL,
  `frame_shape_id` bigint NOT NULL,
  `frame_material_id` bigint DEFAULT NULL,
  `uv_protection` enum('UV380','UV400','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `import_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `base_price` decimal(15,2) NOT NULL,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `thumbnail_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','ACTIVE','INACTIVE','DISCONTINUED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `view_count` int NOT NULL DEFAULT '0',
  `created_by` bigint DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`),
  KEY `fk_products_brand` (`brand_id`),
  KEY `fk_products_shape` (`frame_shape_id`),
  KEY `fk_products_material` (`frame_material_id`),
  KEY `fk_products_created_by` (`created_by`),
  KEY `fk_products_updated_by` (`updated_by`),
  KEY `idx_products_name` (`name`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_category_brand` (`category_id`,`brand_id`),
  KEY `idx_products_status_created` (`status`,`created_at`),
  KEY `idx_products_status_category` (`status`,`category_id`),
  KEY `idx_products_status_brand` (`status`,`brand_id`),
  KEY `idx_products_status_views` (`status`,`view_count`)
) ;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `slug`, `brand_id`, `category_id`, `frame_shape_id`, `frame_material_id`, `uv_protection`, `description`, `import_price`, `base_price`, `sale_price`, `thumbnail_url`, `status`, `view_count`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1001, 'rayban_aviator_or_vertFlash', 'Ray-Ban RB3025 Aviator Green', 'ray-ban-rb3025-aviator-green-rayban-aviator-or-vertflash', 1, 3, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3025 Aviator Green</strong></h2><p>Ray-Ban RB3025 Aviator Green là mẫu kính Ray-Ban chính hãng thuộc dòng RB3025, phù hợp với phong cách kính mát unisex. Thiết kế dáng Aviator, gọng Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3597000.00, 5290000.00, NULL, 'hinh1.jpg', 'ACTIVE', 25, NULL, NULL, '2026-04-05 09:00:00', '2026-07-01 09:36:22'),
(1002, 'rayban_aviator_or_vert', 'Ray-Ban RB3025 Aviator Brown', 'ray-ban-rb3025-aviator-brown-rayban-aviator-or-vert', 1, 3, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3025 Aviator Brown</strong></h2><p>Ray-Ban RB3025 Aviator Brown là mẫu kính Ray-Ban chính hãng thuộc dòng RB3025, phù hợp với phong cách kính mát unisex. Thiết kế dáng Aviator, gọng Metal, tròng màu Brown giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3597000.00, 5290000.00, NULL, 'hinh2.jpg', 'ACTIVE', 24, NULL, NULL, '2026-04-08 10:07:00', '2026-07-26 21:45:46'),
(1006, 'rayban_aviator_cuivre_bleuMirroir', 'Ray-Ban RB3025 Aviator Blue Mirror', 'ray-ban-rb3025-aviator-blue-mirror-rayban-aviator-cuivre-bleumirroir', 1, 3, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3025 Aviator Blue Mirror</strong></h2><p>Ray-Ban RB3025 Aviator Blue Mirror là mẫu kính Ray-Ban chính hãng thuộc dòng RB3025, phù hợp với phong cách kính mát unisex. Thiết kế dáng Aviator, gọng Metal, tròng màu Blue giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh3.jpg', 'ACTIVE', 26, NULL, NULL, '2026-04-11 11:14:00', '2026-04-23 17:36:00'),
(1014, 'rayban_aviator_gun_argentFlash', 'Ray-Ban RB3025 Aviator Silver Mirror', 'ray-ban-rb3025-aviator-silver-mirror-rayban-aviator-gun-argentflash', 1, 3, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3025 Aviator Silver Mirror</strong></h2><p>Ray-Ban RB3025 Aviator Silver Mirror là mẫu kính Ray-Ban chính hãng thuộc dòng RB3025, phù hợp với phong cách kính mát unisex. Thiết kế dáng Aviator, gọng Metal, tròng màu Silver giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh4.jpg', 'ACTIVE', 29, NULL, NULL, '2026-04-11 12:21:00', '2026-04-24 13:54:00'),
(1015, 'rayban_aviator_gun_gris', 'Ray-Ban RB3025 Aviator Polarized', 'ray-ban-rb3025-aviator-polarized-rayban-aviator-gun-gris', 1, 1, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3025 Aviator Polarized</strong></h2><p>Ray-Ban RB3025 Aviator Polarized là mẫu kính Ray-Ban chính hãng thuộc dòng RB3025, phù hợp với phong cách kính mát nam. Thiết kế dáng Aviator, gọng Metal, tròng màu Silver giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 4277000.00, 6290000.00, NULL, 'hinh5.jpg', 'ACTIVE', 32, NULL, NULL, '2026-04-14 13:28:00', '2026-04-28 18:12:00'),
(1016, 'rayban_wayfarer_noir_vert', 'Ray-Ban RB2140 Original Wayfarer Black', 'ray-ban-rb2140-original-wayfarer-black-rayban-wayfarer-noir-vert', 1, 1, 2, 4, 'UV400', '<h2><strong>Ray-Ban RB2140 Original Wayfarer Black</strong></h2><p>Ray-Ban RB2140 Original Wayfarer Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB2140, phù hợp với phong cách kính mát nam. Thiết kế dáng Square, gọng Acetate, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3393000.00, 4990000.00, NULL, 'hinh6.jpg', 'ACTIVE', 35, NULL, NULL, '2026-04-17 09:35:00', '2026-05-02 17:30:00'),
(1017, 'rayban_wayfarer_havane_marron', 'Ray-Ban RB2140 Original Wayfarer Tortoise', 'ray-ban-rb2140-original-wayfarer-tortoise-rayban-wayfarer-havane-marron', 1, 1, 2, 4, 'UV400', '<h2><strong>Ray-Ban RB2140 Original Wayfarer Tortoise</strong></h2><p>Ray-Ban RB2140 Original Wayfarer Tortoise là mẫu kính Ray-Ban chính hãng thuộc dòng RB2140, phù hợp với phong cách kính mát nam. Thiết kế dáng Square, gọng Acetate, tròng màu Brown giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3393000.00, 4990000.00, NULL, 'hinh7.jpg', 'ACTIVE', 38, NULL, NULL, '2026-04-17 10:42:00', '2026-05-03 12:48:00'),
(1018, 'rayban_wayfarer_denimNoir_bleuMirroir', 'Ray-Ban RB2140 Denim Blue', 'ray-ban-rb2140-denim-blue-rayban-wayfarer-denimnoir-bleumirroir', 1, 4, 2, 4, 'UV400', '<h2><strong>Ray-Ban RB2140 Denim Blue</strong></h2><p>Ray-Ban RB2140 Denim Blue là mẫu kính Ray-Ban chính hãng thuộc dòng RB2140, phù hợp với phong cách kính thời trang nam. Thiết kế dáng Square, gọng Acetate, tròng màu Blue Gradient giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3529000.00, 5190000.00, NULL, 'hinh8.jpg', 'ACTIVE', 41, NULL, NULL, '2026-04-20 11:49:00', '2026-05-07 17:06:00'),
(1020, 'rayban_wayfarer_denimOrange_orangeDegrade', 'Ray-Ban RB2140 Orange Denim', 'ray-ban-rb2140-orange-denim-rayban-wayfarer-denimorange-orangedegrade', 1, 5, 2, 4, 'UV400', '<h2><strong>Ray-Ban RB2140 Orange Denim</strong></h2><p>Ray-Ban RB2140 Orange Denim là mẫu kính Ray-Ban chính hãng thuộc dòng RB2140, phù hợp với phong cách kính thời trang nữ. Thiết kế dáng Square, gọng Acetate, tròng màu Orange Gradient giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3529000.00, 5190000.00, NULL, 'hinh9.jpg', 'ACTIVE', 44, NULL, NULL, '2026-04-23 12:56:00', '2026-05-11 13:24:00'),
(1021, 'rayban_new_wayfarer_noir_vertClassique_g15', 'Ray-Ban RB2132 New Wayfarer Black', 'ray-ban-rb2132-new-wayfarer-black-rayban-new-wayfarer-noir-vertclassique-g15', 1, 3, 2, 8, 'UV400', '<h2><strong>Ray-Ban RB2132 New Wayfarer Black</strong></h2><p>Ray-Ban RB2132 New Wayfarer Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB2132, phù hợp với phong cách kính mát unisex. Thiết kế dáng Square, gọng Nylon, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3570000.00, 5250000.00, NULL, 'hinh10.jpg', 'ACTIVE', 47, NULL, NULL, '2026-04-23 13:03:00', '2026-05-12 16:42:00'),
(1023, 'rayban_new_wayfarer_havane_marronClassique_g15', 'Ray-Ban RB2132 New Wayfarer Havana', 'ray-ban-rb2132-new-wayfarer-havana-rayban-new-wayfarer-havane-marronclassique-g15', 1, 6, 2, 8, 'UV400', '<h2><strong>Ray-Ban RB2132 New Wayfarer Havana</strong></h2><p>Ray-Ban RB2132 New Wayfarer Havana là mẫu kính Ray-Ban chính hãng thuộc dòng RB2132, phù hợp với phong cách kính thời trang unisex. Thiết kế dáng Square, gọng Nylon, tròng màu Brown giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3570000.00, 5250000.00, NULL, 'hinh11.jpg', 'ACTIVE', 51, NULL, NULL, '2026-04-26 09:10:00', '2026-06-16 18:52:41'),
(1024, 'rayban_clubmaster_noir_vert', 'Ray-Ban RB3016 Clubmaster Black', 'ray-ban-rb3016-clubmaster-black-rayban-clubmaster-noir-vert', 1, 5, 10, 7, 'UV400', '<h2><strong>Ray-Ban RB3016 Clubmaster Black</strong></h2><p>Ray-Ban RB3016 Clubmaster Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB3016, phù hợp với phong cách kính thời trang nữ. Thiết kế dáng Browline, gọng Acetate + Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3869000.00, 5690000.00, NULL, 'hinh13.jpg', 'ACTIVE', 53, NULL, NULL, '2026-04-29 10:17:00', '2026-05-20 11:18:00'),
(1025, 'rayban_clubmaster_havane_vert', 'Ray-Ban RB3016 Clubmaster Havana', 'ray-ban-rb3016-clubmaster-havana-rayban-clubmaster-havane-vert', 1, 3, 10, 7, 'UV400', '<h2><strong>Ray-Ban RB3016 Clubmaster Havana</strong></h2><p>Ray-Ban RB3016 Clubmaster Havana là mẫu kính Ray-Ban chính hãng thuộc dòng RB3016, phù hợp với phong cách kính mát unisex. Thiết kế dáng Browline, gọng Acetate + Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3869000.00, 5690000.00, NULL, 'hinh14.jpg', 'ACTIVE', 56, NULL, NULL, '2026-04-29 11:24:00', '2026-05-21 15:36:00'),
(1026, 'rayban_round_or_vert', 'Ray-Ban RB3447 Round Metal Gold', 'ray-ban-rb3447-round-metal-gold-rayban-round-or-vert', 1, 3, 1, 1, 'UV400', '<h2><strong>Ray-Ban RB3447 Round Metal Gold</strong></h2><p>Ray-Ban RB3447 Round Metal Gold là mẫu kính Ray-Ban chính hãng thuộc dòng RB3447, phù hợp với phong cách kính mát unisex. Thiết kế dáng Round, gọng Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh16.jpg', 'ACTIVE', 59, NULL, NULL, '2026-05-02 12:31:00', '2026-05-25 19:54:00'),
(1027, 'rayban_round_noir_bleuClairDegrade', 'Ray-Ban RB3447 Round Metal Black', 'ray-ban-rb3447-round-metal-black-rayban-round-noir-bleuclairdegrade', 1, 3, 1, 1, 'UV400', '<h2><strong>Ray-Ban RB3447 Round Metal Black</strong></h2><p>Ray-Ban RB3447 Round Metal Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB3447, phù hợp với phong cách kính mát unisex. Thiết kế dáng Round, gọng Metal, tròng màu Blue Gradient giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh17.jpg', 'ACTIVE', 63, NULL, NULL, '2026-05-05 13:38:00', '2026-07-10 07:28:14'),
(1028, 'rayban_round_cuivre_pinkBrownDegrade', 'Ray-Ban RB3447 Round Metal Copper', 'ray-ban-rb3447-round-metal-copper-rayban-round-cuivre-pinkbrowndegrade', 1, 1, 1, 1, 'UV400', '<h2><strong>Ray-Ban RB3447 Round Metal Copper</strong></h2><p>Ray-Ban RB3447 Round Metal Copper là mẫu kính Ray-Ban chính hãng thuộc dòng RB3447, phù hợp với phong cách kính mát nam. Thiết kế dáng Round, gọng Metal, tròng màu Brown Gradient giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh18.jpg', 'ACTIVE', 65, NULL, NULL, '2026-05-05 09:45:00', '2026-05-30 15:30:00'),
(1029, 'rayban_erika_noir_grisDegrade', 'Ray-Ban RB4171 Erika Black', 'ray-ban-rb4171-erika-black-rayban-erika-noir-grisdegrade', 1, 1, 11, 8, 'UV400', '<h2><strong>Ray-Ban RB4171 Erika Black</strong></h2><p>Ray-Ban RB4171 Erika Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB4171, phù hợp với phong cách kính mát nam. Thiết kế dáng Phantos, gọng Nylon, tròng màu Grey Gradient giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3393000.00, 4990000.00, NULL, 'hinh19.jpg', 'ACTIVE', 68, NULL, NULL, '2026-05-08 10:52:00', '2026-06-03 11:48:00'),
(1030, 'rayban_erika_noir_vert', 'Ray-Ban RB4171 Erika Polarized', 'ray-ban-rb4171-erika-polarized-rayban-erika-noir-vert', 1, 4, 11, 8, 'UV400', '<h2><strong>Ray-Ban RB4171 Erika Polarized</strong></h2><p>Ray-Ban RB4171 Erika Polarized là mẫu kính Ray-Ban chính hãng thuộc dòng RB4171, phù hợp với phong cách kính thời trang nam. Thiết kế dáng Phantos, gọng Nylon, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3937000.00, 5790000.00, NULL, 'hinh21.jpg', 'ACTIVE', 73, NULL, NULL, '2026-05-11 11:59:00', '2026-06-16 18:48:39'),
(1031, 'rayban_justin_noir_vert', 'Ray-Ban RB4165 Justin Black', 'ray-ban-rb4165-justin-black-rayban-justin-noir-vert', 1, 5, 2, 9, 'UV400', '<h2><strong>Ray-Ban RB4165 Justin Black</strong></h2><p>Ray-Ban RB4165 Justin Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB4165, phù hợp với phong cách kính thời trang nữ. Thiết kế dáng Square, gọng Rubber Nylon, tròng màu Light Grey giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3529000.00, 5190000.00, NULL, 'hinh22.png', 'ACTIVE', 74, NULL, NULL, '2026-05-11 12:06:00', '2026-06-03 10:06:00'),
(1032, 'rayban_caravan_or_vert_classique', 'Ray-Ban RB3136 Caravan Gold', 'ray-ban-rb3136-caravan-gold-rayban-caravan-or-vert-classique', 1, 5, 7, 1, 'UV400', '<h2><strong>Ray-Ban RB3136 Caravan Gold</strong></h2><p>Ray-Ban RB3136 Caravan Gold là mẫu kính Ray-Ban chính hãng thuộc dòng RB3136, phù hợp với phong cách kính thời trang nữ. Thiết kế dáng Rectangle, gọng Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3937000.00, 5790000.00, NULL, 'hinh25.jpg', 'ACTIVE', 103, NULL, NULL, '2026-05-14 13:13:00', '2026-07-01 09:56:43'),
(1036, 'rayban_caravan_gun_vert_classique_2', 'Ray-Ban RB3136 Caravan Gunmetal', 'ray-ban-rb3136-caravan-gunmetal-rayban-caravan-gun-vert-classique-2', 1, 5, 7, 1, 'UV400', '<h2><strong>Ray-Ban RB3136 Caravan Gunmetal</strong></h2><p>Ray-Ban RB3136 Caravan Gunmetal là mẫu kính Ray-Ban chính hãng thuộc dòng RB3136, phù hợp với phong cách kính thời trang nữ. Thiết kế dáng Rectangle, gọng Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3937000.00, 5790000.00, NULL, 'hinh26.jpg', 'ACTIVE', 80, NULL, NULL, '2026-05-17 09:20:00', '2026-05-27 14:00:00'),
(1037, 'rayban_cockpit_or_vert_classique', 'Ray-Ban RB3362 Cockpit Gold', 'ray-ban-rb3362-cockpit-gold-rayban-cockpit-or-vert-classique', 1, 6, 9, 1, 'UV400', '<h2><strong>Ray-Ban RB3362 Cockpit Gold</strong></h2><p>Ray-Ban RB3362 Cockpit Gold là mẫu kính Ray-Ban chính hãng thuộc dòng RB3362, phù hợp với phong cách kính thời trang unisex. Thiết kế dáng Aviator, gọng Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3801000.00, 5590000.00, NULL, 'hinh27.jpg', 'ACTIVE', 83, NULL, NULL, '2026-05-17 10:27:00', '2026-05-28 18:18:00'),
(1038, 'rayban_clubround_noir_vertClassique_g15', 'Ray-Ban RB4246 Clubround Black', 'ray-ban-rb4246-clubround-black-rayban-clubround-noir-vertclassique-g15', 1, 2, 1, 7, 'UV400', '<h2><strong>Ray-Ban RB4246 Clubround Black</strong></h2><p>Ray-Ban RB4246 Clubround Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB4246, phù hợp với phong cách kính mát nữ. Thiết kế dáng Round, gọng Acetate + Metal, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3733000.00, 5490000.00, NULL, 'hinh29.jpg', 'ACTIVE', 86, NULL, NULL, '2026-05-20 11:34:00', '2026-06-01 13:36:00'),
(1039, 'rayban_andy_noir_vert_classique', 'Ray-Ban RB4202 Andy Black', 'ray-ban-rb4202-andy-black-rayban-andy-noir-vert-classique', 1, 1, 2, 8, 'UV400', '<h2><strong>Ray-Ban RB4202 Andy Black</strong></h2><p>Ray-Ban RB4202 Andy Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB4202, phù hợp với phong cách kính mát nam. Thiết kế dáng Square, gọng Nylon, tròng màu Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3325000.00, 4890000.00, NULL, 'hinh31.jpg', 'ACTIVE', 93, NULL, NULL, '2026-05-23 12:41:00', '2026-08-04 21:50:28'),
(1040, 'rayban_boyfriend_noir_vert_classique', 'Ray-Ban RB4147 Boyfriend Black', 'ray-ban-rb4147-boyfriend-black-rayban-boyfriend-noir-vert-classique', 1, 1, 7, 8, 'UV400', '<h2><strong>Ray-Ban RB4147 Boyfriend Black</strong></h2><p>Ray-Ban RB4147 Boyfriend Black là mẫu kính Ray-Ban chính hãng thuộc dòng RB4147, phù hợp với phong cách kính mát nam. Thiết kế dáng Rectangle, gọng Nylon, tròng màu Dark Green giúp sản phẩm dễ phối với nhiều kiểu trang phục hằng ngày.</p><p>Sản phẩm có khả năng chống tia UV400, hỗ trợ bảo vệ mắt khi đi nắng, lái xe, du lịch hoặc sử dụng ngoài trời. Kiểu dáng Ray-Ban mang phong cách hiện đại, bền, dễ đeo và phù hợp cho khách hàng yêu thích kính mát thời trang.</p>', 3597000.00, 5290000.00, NULL, 'hinh34.jpg', 'ACTIVE', 138, NULL, NULL, '2026-05-23 13:48:00', '2026-08-04 15:54:09'),
(1041, 'GLASS0ADB97', 'Thêm thử', 'them-thu-1041', 1, 4, 5, 6, 'UV400', NULL, 3000000.00, 5500000.00, 4400000.00, 'anh_san_pham/anh-the-gay-dieu-dung-cua-nu-sinh-so-huu-nhan-sac-cuc-pham-song-mui-di-vao-long-nguoi-dspl-2-1781116780-9231.jpg', 'INACTIVE', 38, NULL, NULL, '2026-06-11 01:39:40', '2026-08-04 08:15:04'),
(1048, 'SP2026080311131116', 'adnh', 'adnh-1048', 1, 1006, 5, 6, 'UV400', '<p>ewqweqw</p>', 500000.00, 5500000.00, NULL, NULL, 'INACTIVE', 0, NULL, NULL, '2026-08-03 11:13:11', '2026-08-04 08:15:00'),
(1049, 'SP2026080414500890', '3123', '3123-1049', 1, 1002, 3, 3, 'UV380', NULL, 5000000.00, 10000000.00, NULL, NULL, 'ACTIVE', 0, NULL, NULL, '2026-08-04 14:50:08', '2026-08-04 14:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `product_id` bigint NOT NULL,
  `variant_id` bigint DEFAULT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_thumbnail` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_product_images_product` (`product_id`),
  KEY `fk_product_images_variant` (`variant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `variant_id`, `image_url`, `alt_text`, `sort_order`, `is_thumbnail`, `created_at`) VALUES
(1, 1001, 1001, 'hinh1.jpg', 'Ray-Ban RB3025 Aviator Green', 1, 1, '2026-04-05 09:00:00'),
(2, 1002, 1002, 'hinh2.jpg', 'Ray-Ban RB3025 Aviator Brown', 1, 1, '2026-04-08 10:07:00'),
(3, 1006, 1006, 'hinh3.jpg', 'Ray-Ban RB3025 Aviator Blue Mirror', 1, 1, '2026-04-11 11:14:00'),
(4, 1014, 1014, 'hinh4.jpg', 'Ray-Ban RB3025 Aviator Silver Mirror', 1, 1, '2026-04-11 12:21:00'),
(5, 1015, 1015, 'hinh5.jpg', 'Ray-Ban RB3025 Aviator Polarized', 1, 1, '2026-04-14 13:28:00'),
(6, 1016, 1016, 'hinh6.jpg', 'Ray-Ban RB2140 Original Wayfarer Black', 1, 1, '2026-04-17 09:35:00'),
(7, 1017, 1017, 'hinh7.jpg', 'Ray-Ban RB2140 Original Wayfarer Tortoise', 1, 1, '2026-04-17 10:42:00'),
(8, 1018, 1018, 'hinh8.jpg', 'Ray-Ban RB2140 Denim Blue', 1, 1, '2026-04-20 11:49:00'),
(9, 1020, 1020, 'hinh9.jpg', 'Ray-Ban RB2140 Orange Denim', 1, 1, '2026-04-23 12:56:00'),
(10, 1021, 1021, 'hinh10.jpg', 'Ray-Ban RB2132 New Wayfarer Black', 1, 1, '2026-04-23 13:03:00'),
(11, 1023, 1023, 'hinh11.jpg', 'Ray-Ban RB2132 New Wayfarer Havana', 1, 1, '2026-04-26 09:10:00'),
(12, 1024, 1024, 'hinh13.jpg', 'Ray-Ban RB3016 Clubmaster Black', 1, 1, '2026-04-29 10:17:00'),
(13, 1025, 1025, 'hinh14.jpg', 'Ray-Ban RB3016 Clubmaster Havana', 1, 1, '2026-04-29 11:24:00'),
(14, 1026, 1026, 'hinh16.jpg', 'Ray-Ban RB3447 Round Metal Gold', 1, 1, '2026-05-02 12:31:00'),
(15, 1027, 1027, 'hinh17.jpg', 'Ray-Ban RB3447 Round Metal Black', 1, 1, '2026-05-05 13:38:00'),
(16, 1028, 1028, 'hinh18.jpg', 'Ray-Ban RB3447 Round Metal Copper', 1, 1, '2026-05-05 09:45:00'),
(17, 1029, 1029, 'hinh19.jpg', 'Ray-Ban RB4171 Erika Black', 1, 1, '2026-05-08 10:52:00'),
(18, 1030, 1030, 'hinh21.jpg', 'Ray-Ban RB4171 Erika Polarized', 1, 1, '2026-05-11 11:59:00'),
(19, 1031, 1031, 'hinh22.png', 'Ray-Ban RB4165 Justin Black', 1, 1, '2026-05-11 12:06:00'),
(20, 1032, 1032, 'hinh25.jpg', 'Ray-Ban RB3136 Caravan Gold', 1, 1, '2026-05-14 13:13:00'),
(21, 1036, 1036, 'hinh26.jpg', 'Ray-Ban RB3136 Caravan Gunmetal', 1, 1, '2026-05-17 09:20:00'),
(22, 1037, 1037, 'hinh27.jpg', 'Ray-Ban RB3362 Cockpit Gold', 1, 1, '2026-05-17 10:27:00'),
(23, 1038, 1038, 'hinh29.jpg', 'Ray-Ban RB4246 Clubround Black', 1, 1, '2026-05-20 11:34:00'),
(24, 1039, 1039, 'hinh31.jpg', 'Ray-Ban RB4202 Andy Black', 1, 1, '2026-05-23 12:41:00'),
(25, 1040, 1040, 'hinh34.jpg', 'Ray-Ban RB4147 Boyfriend Black', 1, 1, '2026-05-23 13:48:00'),
(26, 1041, 2000, 'anh_san_pham/anh-the-gay-dieu-dung-cua-nu-sinh-so-huu-nhan-sac-cuc-pham-song-mui-di-vao-long-nguoi-dspl-2-1781116780-9231.jpg', 'Bánh kem bắp', 1, 1, '2026-06-11 01:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

DROP TABLE IF EXISTS `product_reviews`;
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `product_id` bigint NOT NULL,
  `order_item_id` bigint DEFAULT NULL,
  `rating` tinyint NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('VISIBLE','HIDDEN','PENDING') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VISIBLE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_review_order_item` (`order_item_id`),
  KEY `fk_reviews_user` (`user_id`),
  KEY `fk_reviews_product` (`product_id`),
  KEY `idx_product_reviews_product_status_created` (`product_id`,`status`,`created_at`)
) ;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `user_id`, `product_id`, `order_item_id`, `rating`, `content`, `status`, `created_at`, `updated_at`) VALUES
(1026, 1006, 1032, NULL, 5, 'Sản phẩm tốt nha', 'VISIBLE', '2026-03-01 08:09:42', '2026-03-01 08:09:42'),
(1027, 1008, 1032, NULL, 5, 'Oki lun', 'VISIBLE', '2026-03-01 10:09:42', '2026-03-01 10:09:42'),
(1028, 1006, 1028, NULL, 5, 'Sản phẩm tốt', 'VISIBLE', '2026-03-01 12:48:30', '2026-03-01 12:48:30'),
(1029, 1021, 1032, NULL, 5, 'Sản phẩm tốt quá', 'VISIBLE', '2026-03-01 13:01:19', '2026-03-01 13:01:19'),
(1030, 1006, 1031, NULL, 5, 'Oki', 'HIDDEN', '2026-03-05 23:33:27', '2026-03-05 23:33:27'),
(1031, 1022, 1031, NULL, 5, 'Sản phẩm oki nha', 'VISIBLE', '2026-03-06 12:44:16', '2026-03-06 12:44:16'),
(1032, 1022, 1032, NULL, 5, 'Xin chào các bạn', 'VISIBLE', '2026-03-06 12:44:35', '2026-03-06 12:44:35'),
(1033, 1024, 1031, NULL, 5, 'Đẹp quá, xứng đáng giá tiền', 'VISIBLE', '2026-03-08 23:45:09', '2026-03-08 23:45:09'),
(1034, 1024, 1030, NULL, 5, 'HDPE ngon lun', 'VISIBLE', '2026-03-08 23:50:29', '2026-03-08 23:50:29'),
(1035, 1025, 1032, NULL, 5, 'Sản phẩm hay nha', 'VISIBLE', '2026-03-20 11:41:30', '2026-03-20 11:41:30'),
(1036, 1025, 1001, NULL, 5, 'Sản phẩm dùng tốt', 'VISIBLE', '2026-04-01 11:41:30', '2026-04-01 11:41:30'),
(1037, 1022, 1001, NULL, 5, 'Kính tốt nha', 'HIDDEN', '2026-04-02 09:41:30', '2026-07-09 11:23:07'),
(1038, 1027, 1029, NULL, 5, 'Sản phẩm tốt nha', 'HIDDEN', '2026-04-05 23:25:14', '2026-07-09 10:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `product_id` bigint NOT NULL,
  `sku` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'GLASS7K3M9X-BLACK-54',
  `color_id` bigint NOT NULL,
  `lens_size_id` bigint NOT NULL,
  `variant_price` decimal(15,2) DEFAULT NULL COMMENT 'NULL thì lấy base_price',
  `status` enum('ACTIVE','INACTIVE','OUT_OF_STOCK') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  UNIQUE KEY `uk_variant_product_color_size` (`product_id`,`color_id`,`lens_size_id`),
  KEY `fk_variants_color` (`color_id`),
  KEY `fk_variants_lens_size` (`lens_size_id`),
  KEY `idx_variants_sku` (`sku`),
  KEY `idx_product_variants_product_status` (`product_id`,`status`)
) ;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `sku`, `color_id`, `lens_size_id`, `variant_price`, `status`, `created_at`, `updated_at`) VALUES
(1001, 1001, 'rayban_aviator_or_vertFlash-54', 10, 3, 5290000.00, 'ACTIVE', '2026-04-05 09:00:00', '2026-04-15 09:00:00'),
(1002, 1002, 'rayban_aviator_or_vert-54', 5, 3, 5290000.00, 'ACTIVE', '2026-04-08 10:07:00', '2026-04-19 13:18:00'),
(1006, 1006, 'rayban_aviator_cuivre_bleuMirroir-54', 4, 3, 5490000.00, 'ACTIVE', '2026-04-11 11:14:00', '2026-04-23 17:36:00'),
(1014, 1014, 'rayban_aviator_gun_argentFlash-54', 14, 3, 5490000.00, 'ACTIVE', '2026-04-11 12:21:00', '2026-04-24 13:54:00'),
(1015, 1015, 'rayban_aviator_gun_gris-54', 14, 3, 6290000.00, 'ACTIVE', '2026-04-14 13:28:00', '2026-04-28 18:12:00'),
(1016, 1016, 'rayban_wayfarer_noir_vert-54', 10, 3, 4990000.00, 'ACTIVE', '2026-04-17 09:35:00', '2026-05-02 17:30:00'),
(1017, 1017, 'rayban_wayfarer_havane_marron-54', 5, 3, 4990000.00, 'ACTIVE', '2026-04-17 10:42:00', '2026-05-03 12:48:00'),
(1018, 1018, 'rayban_wayfarer_denimNoir_bleuMirroir-54', 7, 3, 5190000.00, 'ACTIVE', '2026-04-20 11:49:00', '2026-05-07 17:06:00'),
(1020, 1020, 'rayban_wayfarer_denimOrange_orangeDegrade-54', 13, 3, 5190000.00, 'ACTIVE', '2026-04-23 12:56:00', '2026-05-11 13:24:00'),
(1021, 1021, 'rayban_new_wayfarer_noir_vertClassique_g15-54', 10, 3, 5250000.00, 'ACTIVE', '2026-04-23 13:03:00', '2026-05-12 16:42:00'),
(1023, 1023, 'rayban_new_wayfarer_havane_marronClassique_g15-54', 5, 3, 5250000.00, 'ACTIVE', '2026-04-26 09:10:00', '2026-05-16 16:00:00'),
(1024, 1024, 'rayban_clubmaster_noir_vert-54', 10, 3, 5690000.00, 'ACTIVE', '2026-04-29 10:17:00', '2026-05-20 11:18:00'),
(1025, 1025, 'rayban_clubmaster_havane_vert-54', 10, 3, 5690000.00, 'ACTIVE', '2026-04-29 11:24:00', '2026-05-21 15:36:00'),
(1026, 1026, 'rayban_round_or_vert-54', 10, 3, 5490000.00, 'ACTIVE', '2026-05-02 12:31:00', '2026-05-25 19:54:00'),
(1027, 1027, 'rayban_round_noir_bleuClairDegrade-54', 7, 3, 5490000.00, 'ACTIVE', '2026-05-05 13:38:00', '2026-05-29 16:12:00'),
(1028, 1028, 'rayban_round_cuivre_pinkBrownDegrade-54', 8, 3, 5490000.00, 'ACTIVE', '2026-05-05 09:45:00', '2026-05-30 15:30:00'),
(1029, 1029, 'rayban_erika_noir_grisDegrade-54', 11, 3, 4990000.00, 'ACTIVE', '2026-05-08 10:52:00', '2026-06-03 11:48:00'),
(1030, 1030, 'rayban_erika_noir_vert-54', 10, 3, 5790000.00, 'ACTIVE', '2026-05-11 11:59:00', '2026-06-03 15:59:00'),
(1031, 1031, 'rayban_justin_noir_vert-54', 12, 3, 5190000.00, 'ACTIVE', '2026-05-11 12:06:00', '2026-06-03 10:06:00'),
(1032, 1032, 'rayban_caravan_or_vert_classique-54', 10, 3, 5790000.00, 'ACTIVE', '2026-05-14 13:13:00', '2026-06-03 11:13:00'),
(1036, 1036, 'rayban_caravan_gun_vert_classique_2-54', 10, 3, 5790000.00, 'ACTIVE', '2026-05-17 09:20:00', '2026-05-27 14:00:00'),
(1037, 1037, 'rayban_cockpit_or_vert_classique-54', 10, 3, 5590000.00, 'ACTIVE', '2026-05-17 10:27:00', '2026-05-28 18:18:00'),
(1038, 1038, 'rayban_clubround_noir_vertClassique_g15-54', 10, 3, 5490000.00, 'ACTIVE', '2026-05-20 11:34:00', '2026-06-01 13:36:00'),
(1039, 1039, 'rayban_andy_noir_vert_classique-54', 10, 3, 4890000.00, 'ACTIVE', '2026-05-23 12:41:00', '2026-06-03 15:41:00'),
(1040, 1040, 'rayban_boyfriend_noir_vert_classique-54', 9, 3, 5290000.00, 'ACTIVE', '2026-05-23 13:48:00', '2026-08-04 19:58:12'),
(2000, 1041, 'GLASS0ADB97-01', 14, 1, NULL, 'ACTIVE', '2026-06-11 01:39:40', '2026-08-04 08:14:55'),
(2002, 1048, 'SP2026080311131116-01', 1, 1, NULL, 'ACTIVE', '2026-08-03 11:13:11', '2026-08-03 11:13:11'),
(2003, 1049, 'SP2026080414500890-01', 1, 1, NULL, 'ACTIVE', '2026-08-04 14:50:08', '2026-08-04 14:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
CREATE TABLE IF NOT EXISTS `promotions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `promotion_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `discount_type` enum('PERCENT','FIXED_AMOUNT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `max_discount_amount` decimal(15,2) DEFAULT NULL,
  `min_order_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `scope` enum('ORDER','PRODUCT','CATEGORY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ORDER',
  `start_at` datetime NOT NULL,
  `end_at` datetime DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_per_user` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT '0',
  `stackable` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('SCHEDULED','ACTIVE','INACTIVE','EXPIRED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SCHEDULED',
  `created_by` bigint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_code` (`promotion_code`),
  KEY `fk_promotions_created_by` (`created_by`)
) ;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`id`, `promotion_code`, `name`, `description`, `discount_type`, `discount_value`, `max_discount_amount`, `min_order_amount`, `scope`, `start_at`, `end_at`, `usage_limit`, `usage_per_user`, `used_count`, `stackable`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'WELCOME', 'Chào mừng khách hàng mới', 'Mã mẫu lấy ý tưởng từ Givral: giảm 30% toàn đơn hàng.', 'PERCENT', 30.00, NULL, 0.00, 'ORDER', '2026-07-11 10:35:57', NULL, 100, NULL, 2, 0, 'ACTIVE', NULL, '2026-07-11 10:35:57', '2026-08-04 09:50:39'),
(2, 'HELLO', 'CHÀO MỪNG', NULL, 'PERCENT', 20.00, 1000000.00, 500000.00, 'ORDER', '2026-08-04 10:20:00', NULL, 100, 1, 3, 0, 'ACTIVE', NULL, '2026-08-04 10:31:39', '2026-08-04 15:56:51');

-- --------------------------------------------------------

--
-- Table structure for table `return_damage_assessments`
--

DROP TABLE IF EXISTS `return_damage_assessments`;
CREATE TABLE IF NOT EXISTS `return_damage_assessments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `return_request_id` bigint NOT NULL,
  `part_code` enum('FRAME_LEFT','FRAME_RIGHT','LENS_LEFT','LENS_RIGHT','HINGE','NOSE_PAD','ACCESSORY','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `damage_percent` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `damage_level` enum('NONE','LIGHT','MEDIUM','HEAVY','SEVERE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NONE',
  `description` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assessed_by` bigint DEFAULT NULL,
  `assessed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_return_damage_assessor` (`assessed_by`),
  KEY `idx_return_damage_request` (`return_request_id`)
) ;

--
-- Dumping data for table `return_damage_assessments`
--

INSERT INTO `return_damage_assessments` (`id`, `return_request_id`, `part_code`, `part_name`, `damage_percent`, `damage_level`, `description`, `assessed_by`, `assessed_at`) VALUES
(1, 3, 'FRAME_LEFT', 'Gọng trái', 100, 'SEVERE', 'gãy luôn', 1, '2026-06-12 16:28:04'),
(2, 4, 'FRAME_LEFT', 'Gọng trái', 99, 'SEVERE', 'gãy luôn', 1, '2026-06-16 18:35:16'),
(3, 4, 'FRAME_RIGHT', 'Gọng phải', 52, 'HEAVY', 'bị xước nhẹ', 1, '2026-06-16 18:35:16'),
(6, 7, 'FRAME_LEFT', 'Gọng trái', 10, 'LIGHT', '12132321', 1, '2026-08-04 15:04:05'),
(8, 9, 'FRAME_LEFT', 'Gọng trái', 10, 'LIGHT', 'HÁDHQWHD', 1, '2026-08-04 16:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `return_reasons`
--

DROP TABLE IF EXISTS `return_reasons`;
CREATE TABLE IF NOT EXISTS `return_reasons` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('RETURN','EXCHANGE','BOTH') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOTH',
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `return_reasons`
--

INSERT INTO `return_reasons` (`id`, `code`, `name`, `type`, `status`) VALUES
(1, 'WRONG_COLOR', 'Sai màu sắc', 'BOTH', 'ACTIVE'),
(2, 'WRONG_SIZE', 'Sai kích thước tròng', 'BOTH', 'ACTIVE'),
(3, 'WRONG_MODEL', 'Sai model/sản phẩm', 'BOTH', 'ACTIVE'),
(4, 'DEFECTIVE', 'Sản phẩm lỗi từ nhà sản xuất', 'BOTH', 'ACTIVE'),
(5, 'BROKEN_FRAME', 'Gọng bị vỡ', 'BOTH', 'ACTIVE'),
(6, 'DAMAGED_IN_TRANSIT', 'Hư hỏng trong quá trình vận chuyển', 'RETURN', 'ACTIVE'),
(7, 'NOT_AS_DESCRIBED', 'Sản phẩm không như mô tả', 'BOTH', 'ACTIVE'),
(8, 'OTHER', 'Lý do khác', 'BOTH', 'ACTIVE');

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

DROP TABLE IF EXISTS `return_requests`;
CREATE TABLE IF NOT EXISTS `return_requests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `return_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_id` bigint NOT NULL,
  `user_id` bigint NOT NULL,
  `type` enum('RETURN','EXCHANGE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason_id` bigint NOT NULL,
  `reason_detail` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','APPROVED','REJECTED','RECEIVED','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `admin_note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` bigint DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_code` (`return_code`),
  KEY `fk_return_requests_user` (`user_id`),
  KEY `fk_return_requests_reason` (`reason_id`),
  KEY `fk_return_requests_reviewer` (`reviewed_by`),
  KEY `idx_return_requests_order` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `return_requests`
--

INSERT INTO `return_requests` (`id`, `return_code`, `order_id`, `user_id`, `type`, `reason_id`, `reason_detail`, `status`, `admin_note`, `requested_at`, `reviewed_by`, `reviewed_at`, `completed_at`) VALUES
(1, 'RTN20260611140758361', 2000, 1006, 'RETURN', 2, 'jsjsjsjs', 'COMPLETED', '', '2026-06-11 21:07:58', 1, '2026-06-11 21:10:08', '2026-06-11 21:10:08'),
(2, 'RTN20260611141251190', 2000, 1006, 'RETURN', 3, 'wqwqwe', 'COMPLETED', 'dasdasda', '2026-06-11 21:12:51', 1, '2026-06-11 21:13:05', '2026-06-11 21:13:05'),
(3, 'RTN20260612092258853', 2001, 1006, 'RETURN', 4, 'sssss', 'REJECTED', 'vì đã bị gãy do nhà sản xuất vậy nên xin lõi khôgn phù hợp để trả', '2026-06-12 16:22:58', 1, '2026-06-12 16:28:04', NULL),
(4, 'RTN20260616113154624', 1102, 1006, 'RETURN', 5, 'kính bị hư', 'COMPLETED', 'từ chối vì đây là lỗi do khách', '2026-06-16 18:31:55', 1, '2026-06-16 18:35:16', '2026-06-16 18:35:16'),
(5, 'RTN20260616113805570', 2006, 1006, 'RETURN', 1, 'ưeqeq', 'COMPLETED', '', '2026-06-16 18:38:05', 1, '2026-06-16 18:38:46', '2026-06-16 18:38:46'),
(6, 'RTN20260804121006BO3', 2028, 2021, 'RETURN', 6, 'ssss', 'COMPLETED', 'adw', '2026-08-04 12:10:06', 1, '2026-08-04 14:46:32', '2026-08-04 14:46:32'),
(7, 'RTN20260804150315FLJ', 2026, 1, 'RETURN', 6, 'dưaqeqw', 'COMPLETED', '2323', '2026-08-04 15:03:15', 1, '2026-08-04 15:04:05', '2026-08-04 15:04:05'),
(8, 'RTN20260804150526GRT', 2035, 2021, 'RETURN', 5, NULL, 'PENDING', NULL, '2026-08-04 15:05:26', NULL, NULL, NULL),
(9, 'RTN20260804160401GCI', 2037, 2022, 'RETURN', 6, 'HƯEHQHEHQW', 'COMPLETED', 'ĐÃ ĐÁNH GIÁ', '2026-08-04 16:04:01', 1, '2026-08-04 16:07:01', '2026-08-04 16:07:01');

-- --------------------------------------------------------

--
-- Table structure for table `return_request_images`
--

DROP TABLE IF EXISTS `return_request_images`;
CREATE TABLE IF NOT EXISTS `return_request_images` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `return_request_id` bigint NOT NULL,
  `image_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_return_images_request` (`return_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `return_request_images`
--

INSERT INTO `return_request_images` (`id`, `return_request_id`, `image_url`, `created_at`) VALUES
(1, 1, 'return-requests/return-1-1781186878-6259.jpg', '2026-06-11 21:07:58'),
(2, 2, 'return-requests/return-2-1781187171-4240.jpg', '2026-06-11 21:12:51'),
(3, 4, 'return-requests/return-4-1781609515-8104.jpg', '2026-06-16 18:31:55');

-- --------------------------------------------------------

--
-- Table structure for table `return_request_items`
--

DROP TABLE IF EXISTS `return_request_items`;
CREATE TABLE IF NOT EXISTS `return_request_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `return_request_id` bigint NOT NULL,
  `order_item_id` bigint NOT NULL,
  `quantity` int NOT NULL,
  `exchange_variant_id` bigint DEFAULT NULL,
  `condition_note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_return_item_once` (`order_item_id`),
  KEY `fk_return_items_request` (`return_request_id`),
  KEY `fk_return_items_exchange_variant` (`exchange_variant_id`)
) ;

--
-- Dumping data for table `return_request_items`
--

INSERT INTO `return_request_items` (`id`, `return_request_id`, `order_item_id`, `quantity`, `exchange_variant_id`, `condition_note`) VALUES
(1, 1, 191, 1, NULL, 'jsjsjsjs'),
(2, 2, 192, 1, NULL, 'wqwqwe'),
(3, 3, 193, 1, NULL, 'sssss'),
(4, 4, 183, 1, NULL, 'kính bị hư'),
(5, 5, 200, 1, NULL, 'ưeqeq'),
(6, 6, 229, 2, NULL, 'trầy xước'),
(7, 7, 227, 1, NULL, 'qưeqweqwe'),
(8, 8, 236, 1, NULL, NULL),
(9, 9, 238, 1, NULL, 'QƯEQWE');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ADMIN, USER, STORE_MANAGER...',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN', 'Admin tổng', 'Toàn quyền hệ thống', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(2, 'USER', 'Khách hàng', 'Người dùng mua hàng', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(3, 'STAFF', 'Nhân Viên', 'Nhân Viên', 1, '2026-06-10 01:56:38', '2026-07-05 16:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PN/PX/DC + timestamp',
  `type` enum('IMPORT','EXPORT','TRANSFER','ADJUST','RETURN_IN','SALE_OUT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_warehouse_id` bigint DEFAULT NULL,
  `target_warehouse_id` bigint DEFAULT NULL,
  `related_order_id` bigint DEFAULT NULL,
  `status` enum('DRAFT','PENDING','COMPLETED','CANCELLED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `expected_date` date DEFAULT NULL,
  `note` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint DEFAULT NULL,
  `confirmed_by` bigint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `fk_stock_source_warehouse` (`source_warehouse_id`),
  KEY `fk_stock_target_warehouse` (`target_warehouse_id`),
  KEY `fk_stock_created_by` (`created_by`),
  KEY `fk_stock_confirmed_by` (`confirmed_by`),
  KEY `fk_stock_related_order` (`related_order_id`),
  KEY `idx_stock_transactions_type_order` (`type`,`related_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `transaction_code`, `type`, `source_warehouse_id`, `target_warehouse_id`, `related_order_id`, `status`, `expected_date`, `note`, `created_by`, `confirmed_by`, `created_at`, `confirmed_at`) VALUES
(1, 'PN20260611141008815', 'IMPORT', NULL, 1, 2000, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-11 21:10:08', '2026-06-11 21:10:08'),
(2, 'PN20260611141305423', 'IMPORT', NULL, 1, 2000, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-11 21:13:05', '2026-06-11 21:13:05'),
(3, 'SALE_OUT20260612173005544', 'SALE_OUT', 1, NULL, 2002, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2002 sang CONFIRMED', 1, 1, '2026-06-13 00:30:05', '2026-06-13 00:30:05'),
(4, 'PN20260612173102694', 'IMPORT', NULL, 1, 2002, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-13 00:31:02', '2026-06-13 00:31:02'),
(5, 'SALE_OUT20260614140219537', 'SALE_OUT', 1, NULL, 2003, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2003 sang CONFIRMED', 1, 1, '2026-06-14 21:02:19', '2026-06-14 21:02:19'),
(6, 'PN20260614140357611', 'IMPORT', NULL, 1, 2003, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-14 21:03:57', '2026-06-14 21:03:57'),
(7, 'PN20260614141749820', 'IMPORT', NULL, 1, NULL, 'COMPLETED', '2026-06-15', 'test', 1, 1, '2026-06-14 21:17:49', '2026-06-14 21:17:49'),
(8, 'PN20260616113516919', 'IMPORT', NULL, 1, 1102, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-16 18:35:16', '2026-06-16 18:35:16'),
(9, 'SALE_OUT20260616113713340', 'SALE_OUT', 1, NULL, 2006, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2006 sang DELIVERING', 1, 1, '2026-06-16 18:37:13', '2026-06-16 18:37:13'),
(10, 'PN20260616113846958', 'IMPORT', NULL, 1, 2006, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-16 18:38:46', '2026-06-16 18:38:46'),
(11, 'SALE_OUT20260616120032677', 'SALE_OUT', 1, NULL, 2007, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2007 sang DELIVERING', 1, 1, '2026-06-16 19:00:32', '2026-06-16 19:00:32'),
(12, 'PN20260616120042586', 'IMPORT', NULL, 1, 2007, 'COMPLETED', NULL, NULL, 1, 1, '2026-06-16 19:00:42', '2026-06-16 19:00:42'),
(13, 'PN20260626023918743', 'IMPORT', NULL, 1, NULL, 'COMPLETED', '2026-06-27', '', 1, 1, '2026-06-26 09:39:18', '2026-06-26 09:39:18'),
(15, 'SALE_OUT20260804123012158', 'SALE_OUT', 1, NULL, 2031, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2031 sang DELIVERING', 1, 1, '2026-08-04 12:30:12', '2026-08-04 12:30:12'),
(16, 'SALE_OUT20260804125424314', 'SALE_OUT', 1, NULL, 2032, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2032 sang DELIVERING', 1, 1, '2026-08-04 12:54:24', '2026-08-04 12:54:24'),
(17, 'SALE_OUT20260804150247833', 'SALE_OUT', 1, NULL, 2035, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2035 sang DELIVERING', 1, 1, '2026-08-04 15:02:47', '2026-08-04 15:02:47'),
(18, 'PN2026080415094711', 'IMPORT', NULL, 1, NULL, 'COMPLETED', '2026-08-04', 'nhập lô kính', 1, 1, '2026-08-04 15:09:47', '2026-08-04 15:09:47'),
(19, 'SALE_OUT20260804154857908', 'SALE_OUT', 1, NULL, 2036, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2036 sang DELIVERING', 1, 1, '2026-08-04 15:48:57', '2026-08-04 15:48:57'),
(20, 'SALE_OUT20260804155912916', 'SALE_OUT', 1, NULL, 2037, 'COMPLETED', NULL, 'Xuất bán khi cập nhật đơn #2037 sang DELIVERING', 1, 1, '2026-08-04 15:59:12', '2026-08-04 15:59:12'),
(21, 'PN2026080419581213', 'IMPORT', NULL, 1, NULL, 'COMPLETED', NULL, NULL, 1, 1, '2026-08-04 19:58:12', '2026-08-04 19:58:12');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transaction_items`
--

DROP TABLE IF EXISTS `stock_transaction_items`;
CREATE TABLE IF NOT EXISTS `stock_transaction_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `stock_transaction_id` bigint NOT NULL,
  `variant_id` bigint NOT NULL,
  `ordered_quantity` int NOT NULL,
  `actual_quantity` int DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `discrepancy_quantity` int GENERATED ALWAYS AS ((ifnull(`actual_quantity`,0) - `ordered_quantity`)) STORED,
  `note` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stock_items_transaction` (`stock_transaction_id`),
  KEY `fk_stock_items_variant` (`variant_id`)
) ;

--
-- Dumping data for table `stock_transaction_items`
--

INSERT INTO `stock_transaction_items` (`id`, `stock_transaction_id`, `variant_id`, `ordered_quantity`, `actual_quantity`, `unit_cost`, `note`) VALUES
(1, 1, 1032, 2, 1, 5790000.00, 'Nhập hàng hoàn: RTN20260611140758361'),
(2, 2, 1040, 1, 1, 5290000.00, 'Nhập hàng hoàn: RTN20260611141251190'),
(3, 3, 1040, 1, 1, 5290000.00, 'Xuất bán khi cập nhật đơn #2002 sang CONFIRMED'),
(4, 4, 1040, 1, 1, 5290000.00, 'Hoàn tồn do admin hủy đơn #2002'),
(5, 5, 1040, 1, 1, 5290000.00, 'Xuất bán khi cập nhật đơn #2003 sang CONFIRMED'),
(6, 5, 1039, 1, 1, 4890000.00, 'Xuất bán khi cập nhật đơn #2003 sang CONFIRMED'),
(7, 5, 1038, 1, 1, 5490000.00, 'Xuất bán khi cập nhật đơn #2003 sang CONFIRMED'),
(8, 6, 1040, 1, 1, 5290000.00, 'Hoàn tồn do admin hủy đơn #2003'),
(9, 6, 1039, 1, 1, 4890000.00, 'Hoàn tồn do admin hủy đơn #2003'),
(10, 6, 1038, 1, 1, 5490000.00, 'Hoàn tồn do admin hủy đơn #2003'),
(11, 7, 2000, 1, 1, 5000000.00, 'test'),
(12, 8, 1030, 1, 1, 500000.00, 'Nhập hàng hoàn: RTN20260616113154624'),
(13, 9, 1040, 1, 1, 5290000.00, 'Xuất bán khi cập nhật đơn #2006 sang DELIVERING'),
(14, 9, 1039, 1, 1, 4890000.00, 'Xuất bán khi cập nhật đơn #2006 sang DELIVERING'),
(15, 10, 1040, 1, 1, 5290000.00, 'Nhập hàng hoàn: RTN20260616113805570'),
(16, 11, 1040, 1, 1, 5290000.00, 'Xuất bán khi cập nhật đơn #2007 sang DELIVERING'),
(17, 11, 1023, 1, 1, 5250000.00, 'Xuất bán khi cập nhật đơn #2007 sang DELIVERING'),
(18, 11, 1038, 1, 1, 5490000.00, 'Xuất bán khi cập nhật đơn #2007 sang DELIVERING'),
(19, 12, 1040, 1, 1, 5290000.00, 'Hoàn tồn do admin hủy đơn #2007'),
(20, 12, 1023, 1, 1, 5250000.00, 'Hoàn tồn do admin hủy đơn #2007'),
(21, 12, 1038, 1, 1, 5490000.00, 'Hoàn tồn do admin hủy đơn #2007'),
(22, 13, 2000, 20, 20, 3000000.00, ''),
(23, 15, 1040, 1, 1, NULL, 'Ray-Ban RB4147 Boyfriend Black'),
(24, 16, 1040, 10, 10, NULL, 'Ray-Ban RB4147 Boyfriend Black'),
(25, 17, 1040, 1, 1, NULL, 'Ray-Ban RB4147 Boyfriend Black'),
(26, 18, 1040, 10, 10, 3597000.00, 'nhập lô kính'),
(27, 19, 1040, 1, 1, NULL, 'Ray-Ban RB4147 Boyfriend Black'),
(28, 20, 1040, 10, 10, NULL, 'Ray-Ban RB4147 Boyfriend Black'),
(29, 21, 1040, 10, 10, 3570000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
CREATE TABLE IF NOT EXISTS `stores` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `store_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ST0001...',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `manager_user_id` bigint DEFAULT NULL,
  `warehouse_id` bigint NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_detail` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `min_stock_level` int NOT NULL DEFAULT '5',
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_code` (`store_code`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `warehouse_id` (`warehouse_id`),
  KEY `fk_stores_manager` (`manager_user_id`)
) ;

-- --------------------------------------------------------

--
-- Table structure for table `try_on_snapshots`
--

DROP TABLE IF EXISTS `try_on_snapshots`;
CREATE TABLE IF NOT EXISTS `try_on_snapshots` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `product_id` bigint DEFAULT NULL,
  `variant_id` bigint DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_sku` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tryon_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'camera',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `try_on_snapshots_user_email_created_at_index` (`user_email`,`created_at`),
  KEY `try_on_snapshots_model_sku_created_at_index` (`model_sku`,`created_at`),
  KEY `try_on_snapshots_user_id_index` (`user_id`),
  KEY `try_on_snapshots_product_id_index` (`product_id`),
  KEY `try_on_snapshots_variant_id_index` (`variant_id`),
  KEY `idx_tryon_snapshots_user_id` (`user_id`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `try_on_snapshots`
--

INSERT INTO `try_on_snapshots` (`id`, `user_id`, `product_id`, `variant_id`, `user_name`, `user_email`, `product_name`, `model_sku`, `price`, `image_path`, `tryon_mode`, `created_at`, `updated_at`) VALUES
(1, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/8b72bde7-0032-4016-8d8e-e222a85430e6.jpg', 'camera', '2026-08-04 04:35:43', '2026-08-04 04:54:24'),
(2, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/fe88ae4f-3ee3-46d8-9d41-945add04bd15.jpg', 'camera', '2026-08-04 04:35:48', '2026-08-04 04:54:24'),
(3, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/fc175c43-05cf-44ec-9702-fc28c5894f76.jpg', 'camera', '2026-08-04 04:35:49', '2026-08-04 04:54:24'),
(4, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/7c67041b-539c-45b7-9c70-84ea4bec37d5.jpg', 'camera', '2026-08-04 04:35:49', '2026-08-04 04:54:24'),
(5, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/b69971d8-1444-4f3d-af8b-7a52170246ea.jpg', 'camera', '2026-08-04 04:35:50', '2026-08-04 04:54:24'),
(6, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/4c5afa2c-e28c-43e4-8ff9-b28f670f5a48.jpg', 'camera', '2026-08-04 04:35:50', '2026-08-04 04:54:24'),
(7, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/7e5dd547-72cc-4909-adba-675cfb0b2969.jpg', 'camera', '2026-08-04 04:35:51', '2026-08-04 04:54:24'),
(8, 2021, 1032, 1032, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB3136 Caravan Gold', 'rayban_caravan_or_vert_classique', 5790000.00, 'upload/tryons/2026/08/4e83ceec-aa94-401b-9332-a9137aa1c7d6.jpg', 'camera', '2026-08-04 04:56:04', '2026-08-04 04:56:04'),
(9, 2021, 1040, 1040, 'Minh Vu', 'vuthanhnhatminh@gmail.com', 'Ray-Ban RB4147 Boyfriend Black', 'rayban_boyfriend_noir_vert_classique', 5290000.00, 'upload/tryons/2026/08/9112b2fe-1920-41f0-96eb-13a4b6142033.jpg', 'camera', '2026-08-04 05:46:25', '2026-08-04 05:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL nếu chỉ đăng nhập Google và chưa đặt mật khẩu',
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'OTHER',
  `date_of_birth` date DEFAULT NULL,
  `provider` enum('LOCAL','GOOGLE','LOCAL_GOOGLE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOCAL',
  `google_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `status` enum('ACTIVE','LOCKED') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `failed_login_count` int NOT NULL DEFAULT '0',
  `last_failed_login_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`),
  KEY `phone` (`phone`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_provider` (`provider`)
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `avatar_url`, `gender`, `date_of_birth`, `provider`, `google_id`, `email_verified_at`, `status`, `failed_login_count`, `last_failed_login_at`, `locked_until`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin@gmail.com', '$2y$12$QIYYDCPSetolkE/qOz5t0e.nN0cVYjl49PB5/dcrN34R6Z7X/4ERa', 'Quản trị viên', '0900000000', NULL, 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-07-01 08:11:06'),
(1006, 'havutuan9@gmail.com', '$2y$12$Sr9VVNhuXB2mNZr1pXt3veCQxytro6aRgGe27GKaT8V7hqwou4aYq', 'Di Tiểu Bảo', '0909155511', 'anh-the-gay-dieu-dung-cua-nu-sinh-so-huu-nhan-sac-cuc-pham-song-mui-di-vao-long-nguoi-dspl-2.jpg', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-30 08:58:20'),
(1007, 'hilckpc524.old7@gmail.com', '$2y$10$sF.yA6lAhvCE1vhwffwijuzA3JMoVhgnxkk.FdqXR4HVHlHdnXHzK', 'Lê Châu Khả Hi', '0336216654', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1008, 'khoacn03@gmail.com', '$2y$10$Cm.2KiZ85WRGUTBk8vhMaOIQt46A53HKuzPfZh2jS.fdZzAr33dTi', 'Admin', '0336216111', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1009, 'tranvana@gmail.com', '$2y$10$ts748iCUjwA5HpQBMLuROuAXa70addsKmfkMh9rYIw/PjhxLLwH8i', 'Trần Văn A', '0909135969', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1010, 'haolong.old10@gmail.com', '$2y$10$gcBHpzElBGDkOv5EEzJFhuoireNk2HsaloJQLy2KHvzGqx6MIyYku', 'Mai Hảo Long', '0909135985', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1011, 'kietliet.old11@gmail.com', '$2y$10$ENy4z0Infjac7VjlKYp2A.gqCBwc8N01tKGLT9A3buGdVoyd7sXnK', 'Đặng tuấn Kiệt', '0909006764', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1012, 'anner@gmail.com', '$2y$10$ZPAY2O7ntfQ5/Huv3dUAIuY4qHuPD/DpRxiw11TurgYr3hCrWfnv2', 'Nguyễn Văn An', '0336678987', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1013, 'trungnopro@gmail.com', '$2y$10$ptSvfsaT78h4LdZQNCrKdemlC.AqyZ/q5cudTk9/FKcbe6TtJDJAC', 'Nguyễn Thành Trung', '0336216555', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1015, 'toan@gmail.com', '$2y$10$rwdD7UlOPC2XUc4d3nJ/nO0THzotlhmrKekcbBynHxTqpOmFlN79a', 'Toàn', '0336256555', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1016, 'tranvana.old16@gmail.com', '$2y$10$Cm.2KiZ85WRGUTBk8vhMaOIQt46A53HKuzPfZh2jS.fdZzAr33dTi', 'Tran Van A', '0909999999', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1017, 'tranvanc@gmail.com', '$2y$10$O87eXzRKPlNuuB0sYJzdf.0VyT0vEzLHkh1spblXM9uqBugzanRWC', 'Trần Văn C', '0336789123', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1018, 'taikhoan@gmail.com', '$2y$10$zzKX04pBOPu25Ne.5r0LxuBNI77nQLR05PEidmU71CNOpjEVkVW3.', 'Nguyễn Bảo Ngọc', '0336123456', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1019, 'user2@gmail.com', '$2y$10$mAt.bC1Bg7LcgE/zpoChCed8Zr6hcQSddLCgnlklwxIoAJ0PlvVE6', 'Trần Bảo Anh', '0336789456', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1020, 'taikhoan2@gmail.com', '$2y$10$bSGip5zJvVu9HdfPItYyWuITqmaTp.rrJ1/0MyFgVgZmMwJ32e1ka', 'Trần Anh Quốc', '0336789113', 'avatar_it.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1021, 'khachhang@gmail.com', '$2y$10$XRbmKPKAIhF7OzDEVKisqOPJB6MJO4PCokaQsqMioKmbTMU.UoGqm', 'Nguyễn Văn Long', '0336123654', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1022, 'nguyenvanan@gmail.com', '$2y$10$nDLPUD8WoQOEUSgCC2C3/.n3Ko7HM3IBo8E3j.2XWLKq3RlXdfWqm', 'Nguyen Văn AN', '0336987123', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1023, 'k2003@gmail.com', '$2y$10$kBU7Nb8spRHaM215KjPiHuXM46G7IWTk6gdiq5kSxNDCoX.YSCdf6', 'Nguyễn Khoa Nè', '0336216113', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1024, 'taikhoan3@gmail.com', '$2y$10$q4cG7rrG22Wry8RTaQh9NusYZ22.eG5eI/TCqakUvJu/RVeT4qR0a', 'Bảo Hân Helia', '0336999111', 'avatarR60.jpg', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1025, 'nguyenvane@gmail.com', '$2y$10$MFXkasRQF.76XppkDKv8Z.ETCU8FcR3otlXrSl199vCkh24LBaDwC', 'Nguyễn Văn E', '0909123456', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1026, 'phamass203@gmail.com', '$2y$10$tJfMYe9pXz1LE6d6vGBvHuQ3sDPMnnVgiHYvlBmwYwGh/dxtfGcua', 'CFL mobile', '0336123611', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1027, 'userno@gmail.com', '$2y$10$0FtIb7nbhwAfs3ZtQy2oE.KdCFQU.vvY2N3ONHt19a0ZeItn8Ocdm', 'Nguyễn Tuấn', '0336999888', 'avatar_it.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-10 01:56:38', 'ACTIVE', 0, NULL, NULL, NULL, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(2000, 'admin2@gmail.com', '$2y$12$ELEt4LkBW6SHh7Y0alzeYe.9gxR6NxgNEWNr2mY8Yf3LXM8b/CLEG', 'minh s', '0916961956', 'user-default.png', 'OTHER', NULL, 'LOCAL', NULL, '2026-06-14 21:22:45', 'LOCKED', 0, NULL, NULL, NULL, '2026-06-14 21:22:45', '2026-07-05 14:05:54'),
(2014, '123@gmail.com', '$2y$12$3IDdP2xBOpuCEwh5xa9psedh/zxa9UZrT6QBYcw5.wz17vjanfNa.', 'kimricha', '0990000123', NULL, 'MALE', '2026-07-22', 'LOCAL', NULL, '2026-06-14 21:22:45', 'ACTIVE', 0, NULL, NULL, NULL, '2026-07-05 13:58:17', '2026-07-10 20:50:43'),
(2015, 'vuhaidang012345@gmail.com', '$2y$12$x0shUSyEfctY5jV7OD6it.vKmFNOAqNUVDJ1KVeOFTloa1SoZtudS', 'nhatminh', '0916961956', NULL, 'OTHER', NULL, 'LOCAL', NULL, '2026-07-10 03:06:46', 'ACTIVE', 0, NULL, NULL, NULL, '2026-07-10 03:06:18', '2026-07-10 03:06:46'),
(2017, '10@gmail.com', '$2y$12$vsXWVj9DP6s/lmKy7H84MeoGJ2DrMyvfA3ZT9wcqP3fSb6ZcN7GpO', 'Minh Vu Thanh Nhat', '0916961956', NULL, 'OTHER', NULL, 'LOCAL', NULL, NULL, 'ACTIVE', 0, NULL, NULL, NULL, '2026-08-03 10:45:40', '2026-08-03 10:45:40'),
(2020, 'vutuan2906@gmail.com', '$2y$12$nU3veBNwe0.6G311WSUGnu4FW/iyJTto5le4iTydqxrKIRn.sRo.m', 'Vu Tuan', '0916961956', NULL, 'OTHER', NULL, 'LOCAL', NULL, NULL, 'ACTIVE', 0, NULL, NULL, NULL, '2026-08-04 09:26:13', '2026-08-04 09:26:13'),
(2021, 'vuthanhnhatminh@gmail.com', '$2y$12$v3FUuCph0AQbK.VeSSMkweDvWNyjiphesThY3Bj7yOXfuGLkLWbkK', 'Minh Vu', '0916961956', NULL, 'OTHER', NULL, 'LOCAL', NULL, '2026-08-04 09:29:07', 'ACTIVE', 0, NULL, NULL, NULL, '2026-08-04 09:28:49', '2026-08-04 09:29:07'),
(2022, 'narutonatsu123@gmail.com', '$2y$12$M7/yvwIbAev1tiOJlW9HBOBkeCV8dwlKb9Xrs6o4Ri96Tlfx2mV4.', 'Natsu Naruto', '0916961956', NULL, 'OTHER', NULL, 'LOCAL', NULL, '2026-08-04 15:52:26', 'ACTIVE', 0, NULL, NULL, NULL, '2026-08-04 15:52:08', '2026-08-04 15:52:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `recipient_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `district_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ward_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_detail` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_addresses_user` (`user_id`),
  KEY `idx_user_addresses_user_default_created` (`user_id`,`is_default`,`created_at`)
) ;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `recipient_name`, `phone`, `province_code`, `province_name`, `district_code`, `district_name`, `ward_code`, `ward_name`, `address_detail`, `is_default`, `created_at`, `updated_at`) VALUES
(1006, 1006, 'Di Tiểu Bảo', '0909155511', '00', 'Cần Thơ', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Ninh Kiều, Cần Thơ', 1, '2026-06-10 01:56:38', '2026-06-14 17:33:04'),
(1007, 1007, 'Lê Châu Khả Hi', '0336216654', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Kiên Giang', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1008, 1008, 'Admin', '0336216111', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cần Thơ nè', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1009, 1009, 'Trần Văn A', '0909135969', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cái Răng, Cần Thơ', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1010, 1010, 'Mai Hảo Long', '0909135985', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Ninh Kiều, Cần Thơ', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1011, 1011, 'Đặng tuấn Kiệt', '0909006764', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cần Thơ', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1012, 1012, 'Nguyễn Văn An', '0336678987', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cần Thơ', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1013, 1013, 'Nguyễn Thành Trung', '0336216555', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Can tho', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1015, 1015, 'Toàn', '0336256555', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Đại Học Cửu Long', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1016, 1016, 'Tran Van A', '0909999999', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, HCM', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1017, 1017, 'Trần Văn C', '0336789123', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Hà Đông, Hà Nôi', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1018, 1018, 'Nguyễn Bảo Ngọc', '0336123456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Hà Đông Hà NỘI', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1019, 1019, 'Trần Bảo Anh', '0336789456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Hà Đông, Hà Nội', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1020, 1020, 'Trần Anh Quốc', '0336789113', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cầu Giay, Ha Noi', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1021, 1021, 'Nguyễn Văn Long', '0336123654', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cầu Giay, Hà Nội', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1022, 1022, 'Nguyen Văn AN', '0336987123', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1 HCM', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1023, 1023, 'Nguyễn Khoa Nè', '0336216113', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'HN', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1024, 1024, 'Bảo Hân Helia', '0336999111', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, HCM', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1025, 1025, 'Nguyễn Văn E', '0909123456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Hà Đông, Hà Nội', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1026, 1026, 'CFL mobile', '0336123611', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quấn 1, HCM', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1027, 1027, 'Nguyễn Tuấn', '0336999888', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Hà Đông, Hà Nội', 1, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1028, 1015, 'Toàn', '0336256555', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Vĩnh Long 123', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1029, 1016, 'Tran Van A', '0909999999', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cầu giấy Hà Nội', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1030, 1017, 'Trần Văn C', '0336789123', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, HCM', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1031, 1018, 'Nguyễn Bảo Ngọc', '0336123456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quân 1 HCM', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1032, 1019, 'Trần Bảo Anh', '0336789456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quan 1, HCM', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1033, 1020, 'Trần Anh Quốc', '0336789113', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, HCM', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1034, 1021, 'Nguyễn Văn Long', '0336123654', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 11, HCM', 0, '2026-06-10 01:56:38', '2026-06-10 01:56:38'),
(1035, 1022, 'Nguyen Văn AN', '0336987123', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cau Giay, Hà Nội', 0, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1036, 1006, 'Di Tiểu Bảo', '0909155511', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, HCM', 0, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1037, 1024, 'Bảo Hân Helia', '0336999111', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Cầu giấy, Hà Nội', 0, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1038, 1025, 'Nguyễn Văn E', '0909123456', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 5, TP. HCM', 0, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(1039, 1027, 'Nguyễn Tuấn', '0336999888', '00', 'Chưa xác định', '000', 'Chưa xác định', '00000', 'Chưa xác định', 'Quận 1, Hồ Chí Minh', 0, '2026-06-10 01:56:39', '2026-06-10 01:56:39'),
(3000, 2000, 'minh s', '0916961956', '00', 'Hồ Chí Minh', '000', 'Chưa xác định', '00000', 'Chưa xác định', '184', 1, '2026-06-14 21:22:45', '2026-06-14 21:22:45'),
(3002, 2020, 'Vu Tuan', '0916961956', '', 'Hồ Chí Minh', '', '', '', '', '283', 1, '2026-08-04 09:26:13', '2026-08-04 09:26:13'),
(3003, 2021, 'Minh Vu', '0916961956', '', 'Quảng Ngãi', '', '', '', '', '283', 1, '2026-08-04 09:28:49', '2026-08-04 09:28:49'),
(3004, 2022, 'Natsu Naruto', '0916961956', '', 'Hồ Chí Minh', '', '', '', '', '283', 1, '2026-08-04 15:52:08', '2026-08-04 15:52:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` bigint NOT NULL,
  `role_id` bigint NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `fk_user_roles_role` (`role_id`),
  KEY `idx_user_roles_role_user` (`role_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1),
(1008, 1),
(1006, 2),
(1007, 2),
(1009, 2),
(1010, 2),
(1011, 2),
(1012, 2),
(1013, 2),
(1015, 2),
(1016, 2),
(1017, 2),
(1018, 2),
(1019, 2),
(1020, 2),
(1021, 2),
(1022, 2),
(1023, 2),
(1024, 2),
(1025, 2),
(1026, 2),
(1027, 2),
(2000, 2),
(2015, 2),
(2017, 2),
(2020, 2),
(2021, 2),
(2022, 2),
(2014, 3);

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `warehouse_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WH0001...',
  `name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('NORMAL','RETURN','WARRANTY','STORE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `capacity` int NOT NULL,
  `province_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ward_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_detail` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_stock_level` int NOT NULL DEFAULT '10',
  `status` enum('ACTIVE','INACTIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouse_code` (`warehouse_code`),
  UNIQUE KEY `name` (`name`)
) ;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `warehouse_code`, `name`, `type`, `capacity`, `province_code`, `province_name`, `district_code`, `district_name`, `ward_code`, `ward_name`, `address_detail`, `min_stock_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'WH0001', 'Kho trung tâm TP.HCM', 'NORMAL', 10000, '79', 'TP. Hồ Chí Minh', '760', 'Quận 1', '26734', 'Phường Bến Nghé', '123 Nguyễn Huệ', 10, 'ACTIVE', '2026-06-10 01:56:38', '2026-06-10 01:56:38');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `banners`
--
ALTER TABLE `banners`
  ADD CONSTRAINT `fk_banners_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventories`
--
ALTER TABLE `inventories`
  ADD CONSTRAINT `fk_inventories_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inventories_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_order_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_material` FOREIGN KEY (`frame_material_id`) REFERENCES `frame_materials` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_shape` FOREIGN KEY (`frame_shape_id`) REFERENCES `frame_shapes` (`id`),
  ADD CONSTRAINT `fk_products_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_images_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_reviews_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_variants_color` FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`),
  ADD CONSTRAINT `fk_variants_lens_size` FOREIGN KEY (`lens_size_id`) REFERENCES `lens_sizes` (`id`),
  ADD CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `fk_promotions_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `return_damage_assessments`
--
ALTER TABLE `return_damage_assessments`
  ADD CONSTRAINT `fk_return_damage_assessor` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_return_damage_request` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `fk_return_requests_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `fk_return_requests_reason` FOREIGN KEY (`reason_id`) REFERENCES `return_reasons` (`id`),
  ADD CONSTRAINT `fk_return_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_return_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `return_request_images`
--
ALTER TABLE `return_request_images`
  ADD CONSTRAINT `fk_return_images_request` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_request_items`
--
ALTER TABLE `return_request_items`
  ADD CONSTRAINT `fk_return_items_exchange_variant` FOREIGN KEY (`exchange_variant_id`) REFERENCES `product_variants` (`id`),
  ADD CONSTRAINT `fk_return_items_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`),
  ADD CONSTRAINT `fk_return_items_request` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `fk_stock_confirmed_by` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stock_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stock_related_order` FOREIGN KEY (`related_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stock_source_warehouse` FOREIGN KEY (`source_warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `fk_stock_target_warehouse` FOREIGN KEY (`target_warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `stock_transaction_items`
--
ALTER TABLE `stock_transaction_items`
  ADD CONSTRAINT `fk_stock_items_transaction` FOREIGN KEY (`stock_transaction_id`) REFERENCES `stock_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stock_items_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`);

--
-- Constraints for table `stores`
--
ALTER TABLE `stores`
  ADD CONSTRAINT `fk_stores_manager` FOREIGN KEY (`manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stores_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
