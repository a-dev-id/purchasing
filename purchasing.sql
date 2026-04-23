-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 23, 2026 at 07:52 AM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `purchasing`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('purchasing-cache-livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3:timer', 'i:1776928745;', 1776928745),
('purchasing-cache-livewire-rate-limiter:a03482d47b45acecfdd4da52d94a0986080d88b3', 'i:1;', 1776928745),
('purchasing-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0:timer', 'i:1776052554;', 1776052554),
('purchasing-cache-da4b9237bacccdf19c0760cab7aec4a8359010b0', 'i:1;', 1776052554),
('purchasing-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4:timer', 'i:1776926628;', 1776926628),
('purchasing-cache-ac3478d69a3c81fa62e60f5c3696165a4e5e6ac4', 'i:2;', 1776926628),
('purchasing-cache-77de68daecd823babbb58edb1c8e14d7106e83bb:timer', 'i:1776909753;', 1776909753),
('purchasing-cache-77de68daecd823babbb58edb1c8e14d7106e83bb', 'i:1;', 1776909753),
('purchasing-cache-1b6453892473a467d07372d45eb05abc2031647a:timer', 'i:1776925810;', 1776925810),
('purchasing-cache-1b6453892473a467d07372d45eb05abc2031647a', 'i:1;', 1776925810);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `default_unit` varchar(50) DEFAULT NULL,
  `default_specification` text,
  `last_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'IDR',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `sku`, `category`, `brand`, `default_unit`, `default_specification`, `last_price`, `currency`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Belt Kerang Musquito net', NULL, NULL, NULL, 'pcs', '<p></p>', NULL, 'IDR', 1, '2026-04-21 04:08:04', '2026-04-21 04:08:04'),
(2, 'Railing Tangga', NULL, NULL, NULL, 'set', '<p>P 180X2 dengan 4 tiang T 120X4</p>', NULL, 'IDR', 1, '2026-04-23 02:01:43', '2026-04-23 02:01:43'),
(3, 'Bayclin', NULL, NULL, NULL, 'galon', '<p></p>', NULL, 'IDR', 1, '2026-04-23 06:24:51', '2026-04-23 06:24:51'),
(4, 'Sapu hijau', NULL, NULL, NULL, 'pcs', '<p>Nagata</p>', NULL, 'IDR', 1, '2026-04-23 06:29:18', '2026-04-23 06:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `item_photos`
--

DROP TABLE IF EXISTS `item_photos`;
CREATE TABLE IF NOT EXISTS `item_photos` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` bigint UNSIGNED NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_photos_item_id_index` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_photos`
--

INSERT INTO `item_photos` (`id`, `item_id`, `file_path`, `file_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'purchase-request-items/01KPQ3JKHXAJRHS994HFS3Z819.jpeg', '01KPQ3JKHXAJRHS994HFS3Z819.jpeg', '2026-04-21 04:08:04', '2026-04-21 04:08:04'),
(2, 2, 'purchase-request-items/01KPW14P4GRWTM25X4B0C31RAW.png', '01KPW14P4GRWTM25X4B0C31RAW.png', '2026-04-23 02:01:43', '2026-04-23 02:01:43'),
(3, 3, 'purchase-request-items/01KPWG6G192VZV0VC1MDW1SF9P.jpg', '01KPWG6G192VZV0VC1MDW1SF9P.jpg', '2026-04-23 06:24:51', '2026-04-23 06:24:51'),
(4, 4, 'purchase-request-items/01KPWGEMPJZNYK7S2CDTYY0YEN.jpg', '01KPWGEMPJZNYK7S2CDTYY0YEN.jpg', '2026-04-23 06:29:18', '2026-04-23 06:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(9, '0001_01_01_000000_create_users_table', 1),
(10, '0001_01_01_000001_create_cache_table', 1),
(11, '0001_01_01_000002_create_jobs_table', 1),
(12, '2026_03_24_065851_create_purchase_requests_table', 1),
(13, '2026_03_24_065859_create_purchase_request_items_table', 1),
(14, '2026_03_24_065906_create_purchase_request_item_photos_table', 1),
(15, '2026_03_24_070408_create_vendors_table', 1),
(16, '2026_03_24_070416_create_purchase_request_vendor_offers_table', 1),
(17, '2026_03_24_070735_create_purchase_request_logs_table', 1),
(18, '2026_03_24_074656_add_requester_name_to_purchase_requests_table', 2),
(19, '2026_03_24_080637_add_role_department_is_active_to_users_table', 3),
(20, '2026_03_26_000001_update_purchase_requests_status_enum', 4),
(21, '2026_03_26_121600_update_purchase_request_status_enum', 5),
(22, '2026_03_26_143549_add_new_statuses', 6),
(23, '2026_03_26_151500_add_gm_revision_statuses_to_purchase_requests', 7),
(24, '2026_03_26_152953_revision_to_purchasing_from_g_m', 8),
(25, '2026_03_27_120000_add_cancelled_status_to_purchase_requests', 9),
(26, '2026_03_28_000001_add_inactivity_tracking_to_purchase_requests_table', 10),
(27, '2026_03_28_141029_add_username_to_user', 11),
(28, '2026_04_01_155020_add_date_needed_to_purchase_requests_table', 12),
(29, '2026_04_01_162644_create_purchase_request_item_vendor_offers_table', 13),
(30, '2026_04_01_170032_add_vendor_comparison_mode_to_purchase_requests_table', 14),
(31, '2026_04_16_143641_item_photos', 15),
(32, '2026_04_18_123319_update_purhcase_requests_status_enum', 16),
(33, '2026_04_18_140628_update_purchase_request_status_enum_for_fc_flow', 17),
(34, '2026_04_18_144655_update_status', 18),
(35, '2026_04_18_144853_update_status2', 19),
(36, '2026_04_20_093458_add_received_at_to_purchase_requests', 20),
(37, '2026_04_21_154703_add_category_to_purchase_request_item_vendor_offers_table', 21),
(38, '2026_04_21_155118_add_category_to_vendor_offer_tables', 22);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_by` bigint UNSIGNED DEFAULT NULL,
  `requester_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('urgent','normal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `date_needed` date DEFAULT NULL,
  `vendor_comparison_mode` enum('pr','item') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'item',
  `status` enum('draft','submitted','revision_from_purchasing','submitted_to_accounting','on_hold_by_accounting','revision_from_accounting','revision_to_purchasing_from_accounting','revision_to_requester_from_accounting','submitted_to_gm','on_hold_by_gm','revision_from_gm','revision_to_purchasing_from_gm','revision_to_accounting_from_gm','revision_to_requester_from_gm','gm_approved','pending','on_progress','waiting_payment','paid_to_vendor','on_shipping','received_by_requester','pending_by_fc','on_progress_by_fc','waiting_payment_by_fc','paid_to_vendor_by_fc','on_shipping_by_fc','received_by_requester_by_fc','item_arrived_by_fc','on_hold_by_fc','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `request_notes` longtext COLLATE utf8mb4_unicode_ci,
  `current_status_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `last_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_requests_request_number_unique` (`request_number`),
  KEY `purchase_requests_requested_by_foreign` (`requested_by`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `request_number`, `requested_by`, `requester_name`, `department_name`, `title`, `priority`, `date_needed`, `vendor_comparison_mode`, `status`, `request_notes`, `current_status_at`, `last_activity_at`, `last_reminder_sent_at`, `submitted_at`, `approved_at`, `cancelled_at`, `received_at`, `created_at`, `updated_at`) VALUES
(2, 'PR-20260421-0002', NULL, 'Wayan HK', 'Housekeeping', 'Belt Kerang Musquito net', 'normal', '2026-10-30', 'item', 'received_by_requester', '<p>Untuk stok</p>', '2026-04-22 08:36:01', '2026-04-22 08:36:01', NULL, '2026-04-21 04:43:39', '2026-04-22 07:15:12', NULL, '2026-04-22 16:00:00', '2026-04-21 04:37:12', '2026-04-22 08:36:01'),
(3, 'PR-20260423-0003', NULL, 'Made Engineering', 'Engineering', 'Pembuatan Reling Tangga P 180X2 dg 4tiang T 120X4', 'normal', '2026-05-31', 'item', 'submitted', '<p>new realing at main stairs resto</p>', '2026-04-23 02:04:39', '2026-04-23 02:04:39', NULL, '2026-04-23 02:04:39', NULL, NULL, NULL, '2026-04-23 02:01:43', '2026-04-23 02:04:39'),
(4, 'PR-20260423-0004', NULL, 'Komang HK', 'Housekeeping & Garden', 'Pembelian Chemical', 'normal', '2026-05-01', 'item', 'received_by_requester', '<p>Untuk pembersihan rutin</p>', '2026-04-23 07:21:19', '2026-04-23 07:21:19', NULL, '2026-04-23 06:25:53', '2026-04-23 07:09:46', NULL, '2026-04-23 07:20:00', '2026-04-23 06:24:51', '2026-04-23 07:21:19'),
(5, NULL, NULL, 'Komang HK', 'Housekeeping & Garden', 'Pembelian Sapu', 'normal', '2026-04-30', 'item', 'draft', '<p>Untuk menyapu</p>', '2026-04-23 06:29:18', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 06:29:18', '2026-04-23 06:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_items`
--

DROP TABLE IF EXISTS `purchase_request_items`;
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `item_id` bigint UNSIGNED DEFAULT NULL,
  `item_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specification` text COLLATE utf8mb4_unicode_ci,
  `qty` decimal(12,2) NOT NULL DEFAULT '1.00',
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `needed_by` date DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_request_items_purchase_request_id_foreign` (`purchase_request_id`),
  KEY `pri_item_id_foreign` (`item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_request_items`
--

INSERT INTO `purchase_request_items` (`id`, `purchase_request_id`, `item_id`, `item_name`, `specification`, `qty`, `unit`, `needed_by`, `purpose`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Belt Kerang Musquito net', '<p></p>', 10.00, 'pcs', NULL, NULL, 0, '2026-04-21 04:08:04', '2026-04-21 04:08:04'),
(2, 2, 1, 'Belt Kerang Musquito net', '<p></p>', 10.00, 'pcs', NULL, NULL, 0, '2026-04-21 04:37:12', '2026-04-21 04:37:12'),
(3, 3, 2, 'Railing Tangga', '<p>P 180X2 dengan 4 tiang T 120X4</p>', 1.00, 'set', NULL, NULL, 0, '2026-04-23 02:01:43', '2026-04-23 02:01:43'),
(4, 4, 3, 'Bayclin', '<p></p>', 1.00, 'galon', NULL, NULL, 0, '2026-04-23 06:24:51', '2026-04-23 06:24:51'),
(5, 5, 4, 'Sapu hijau', '<p>Nagata</p>', 1.00, 'pcs', NULL, NULL, 0, '2026-04-23 06:29:18', '2026-04-23 06:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_item_photos`
--

DROP TABLE IF EXISTS `purchase_request_item_photos`;
CREATE TABLE IF NOT EXISTS `purchase_request_item_photos` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_item_id` bigint UNSIGNED NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_request_item_photos_purchase_request_item_id_foreign` (`purchase_request_item_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_request_item_photos`
--

INSERT INTO `purchase_request_item_photos` (`id`, `purchase_request_item_id`, `file_path`, `file_name`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'purchase-request-items/01KPQ3JKHXAJRHS994HFS3Z819.jpeg', NULL, 0, '2026-04-21 04:08:04', '2026-04-21 04:08:04'),
(2, 3, 'purchase-request-items/01KPW14P4GRWTM25X4B0C31RAW.png', NULL, 0, '2026-04-23 02:01:43', '2026-04-23 02:01:43'),
(3, 4, 'purchase-request-items/01KPWG6G192VZV0VC1MDW1SF9P.jpg', NULL, 0, '2026-04-23 06:24:51', '2026-04-23 06:24:51'),
(4, 5, 'purchase-request-items/01KPWGEMPJZNYK7S2CDTYY0YEN.jpg', NULL, 0, '2026-04-23 06:29:18', '2026-04-23 06:29:18');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_item_vendor_offers`
--

DROP TABLE IF EXISTS `purchase_request_item_vendor_offers`;
CREATE TABLE IF NOT EXISTS `purchase_request_item_vendor_offers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_item_id` bigint UNSIGNED NOT NULL,
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_total` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `lead_time_days` int DEFAULT NULL,
  `offer_rank` tinyint UNSIGNED DEFAULT NULL,
  `is_selected_by_accounting` tinyint(1) NOT NULL DEFAULT '0',
  `offer_notes` text COLLATE utf8mb4_unicode_ci,
  `quotation_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `privo_item_fk` (`purchase_request_item_id`),
  KEY `privo_vendor_fk` (`vendor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_request_item_vendor_offers`
--

INSERT INTO `purchase_request_item_vendor_offers` (`id`, `purchase_request_item_id`, `vendor_id`, `vendor_name`, `category`, `contact_person`, `phone`, `email`, `offer_total`, `currency`, `lead_time_days`, `offer_rank`, `is_selected_by_accounting`, `offer_notes`, `quotation_file`, `created_at`, `updated_at`) VALUES
(6, 2, 3, 'PT Ubud Textile Supply', NULL, 'Ketut', '+62 361 555 0246', 'quotation@ubudtextiles.example', 834999.00, 'IDR', NULL, NULL, 0, NULL, 'purchase-request-quotations/01KPQGFTQ1X2ACXKGTY9JVNSCJ.pdf', '2026-04-21 08:27:46', '2026-04-21 08:27:46'),
(5, 2, 2, 'CV Nusantara Mosquito Net Works', NULL, 'Made', '+62 361 555 0314', 'offers@nmnw.example', 920000.00, 'IDR', NULL, NULL, 0, NULL, 'purchase-request-quotations/01KPQGFTPTFQNA6QMKYY4MWB6V.pdf', '2026-04-21 08:27:45', '2026-04-21 08:27:45'),
(4, 2, 1, 'CV Bali Netindo', NULL, 'Pak Komang', '+62 361 555 0188', 'sales@balinetindo.example', 750000.00, 'IDR', NULL, NULL, 1, NULL, 'purchase-request-quotations/01KPQGFTPF2QJ5D3WZYER4XXBQ.pdf', '2026-04-21 08:27:45', '2026-04-21 08:27:45'),
(12, 4, 6, 'Vendor C', NULL, 'asghdfagsdas', NULL, NULL, 90000.00, 'IDR', NULL, NULL, 0, NULL, 'purchase-request-quotations/01KPWH9N7VDH3Q36H56P04FWHG.pdf', '2026-04-23 06:52:23', '2026-04-23 06:52:23'),
(11, 4, 5, 'Vendor B', NULL, 'Komang C', NULL, NULL, 65000.00, 'IDR', NULL, NULL, 1, NULL, 'purchase-request-quotations/01KPWH9N7KEKMSX9PFQFPQ3A2F.pdf', '2026-04-23 06:52:23', '2026-04-23 06:52:23'),
(10, 4, 4, 'Vendor A', NULL, 'Made B', NULL, NULL, 70000.00, 'IDR', NULL, NULL, 0, NULL, 'purchase-request-quotations/01KPWH9N78Y4YS4PRCYS2D7J5G.pdf', '2026-04-23 06:52:23', '2026-04-23 06:52:23');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_logs`
--

DROP TABLE IF EXISTS `purchase_request_logs`;
CREATE TABLE IF NOT EXISTS `purchase_request_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `user_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_request_logs_user_id_foreign` (`user_id`),
  KEY `purchase_request_logs_purchase_request_id_acted_at_index` (`purchase_request_id`,`acted_at`),
  KEY `purchase_request_logs_action_index` (`action`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_request_logs`
--

INSERT INTO `purchase_request_logs` (`id`, `purchase_request_id`, `user_id`, `user_name`, `user_email`, `role_name`, `action`, `from_status`, `to_status`, `message`, `meta`, `acted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 4, NULL, NULL, NULL, 'cancelled', NULL, NULL, 'Purchase request cancelled.', NULL, NULL, '2026-04-21 04:10:55', '2026-04-21 04:10:55'),
(2, 2, 4, 'Housekeeping Department', NULL, 'requester', 'submitted', 'draft', 'submitted', 'Submitted by requester: Wayan HK to Purchasing', '{\"request_number\": \"PR-20260421-0002\", \"requester_name\": \"Wayan HK\", \"department_name\": \"Housekeeping\"}', '2026-04-21 04:43:39', '2026-04-21 04:43:39', '2026-04-21 04:43:39'),
(3, 2, 5, NULL, NULL, NULL, 'submitted_to_accounting', NULL, NULL, 'Purchase request submitted to Accounting.', NULL, NULL, '2026-04-21 07:55:50', '2026-04-21 07:55:50'),
(4, 2, 6, NULL, NULL, NULL, 'submitted_to_gm', NULL, NULL, 'Purchase request submitted to GM.', NULL, NULL, '2026-04-21 08:24:44', '2026-04-21 08:24:44'),
(5, 2, 8, NULL, NULL, NULL, 'revision_to_accounting_from_gm', NULL, NULL, 'vendor belum di pilih', NULL, NULL, '2026-04-21 08:26:10', '2026-04-21 08:26:10'),
(6, 2, 6, NULL, NULL, NULL, 'submitted_to_gm', NULL, NULL, 'Purchase request submitted to GM.', NULL, NULL, '2026-04-21 08:42:25', '2026-04-21 08:42:25'),
(7, 2, 8, NULL, NULL, NULL, 'gm_approved', NULL, NULL, 'Purchase request approved by GM and handed over to Financial Controller.', NULL, NULL, '2026-04-22 07:15:12', '2026-04-22 07:15:12'),
(8, 2, 10, NULL, NULL, NULL, 'on_progress', NULL, NULL, 'Purchase request marked as on progress by Financial Controller.', NULL, NULL, '2026-04-22 07:32:20', '2026-04-22 07:32:20'),
(9, 2, 10, NULL, NULL, NULL, 'paid_to_vendor', NULL, NULL, 'Purchase request marked as paid to vendor by Financial Controller.', NULL, NULL, '2026-04-22 07:32:55', '2026-04-22 07:32:55'),
(10, 2, 10, NULL, NULL, NULL, 'on_progress', NULL, NULL, 'Purchase request marked as on progress by Financial Controller.', NULL, NULL, '2026-04-22 07:34:14', '2026-04-22 07:34:14'),
(11, 2, 10, NULL, NULL, NULL, 'paid_to_vendor', NULL, NULL, 'Purchase request marked as paid to vendor by Financial Controller.', NULL, NULL, '2026-04-22 07:42:33', '2026-04-22 07:42:33'),
(12, 2, 5, NULL, NULL, NULL, 'on_shipping', NULL, NULL, 'The PR is Paid to vendor by Financial Controller and On Shipping.', NULL, NULL, '2026-04-22 08:35:03', '2026-04-22 08:35:03'),
(13, 2, 5, NULL, NULL, NULL, 'received_by_requester', NULL, NULL, 'The item was received by Made Anu on 2026-04-23. Barang sudah di ambil oleh Made Anu Receiver: Made Anu. Received date: 2026-04-23.', NULL, NULL, '2026-04-22 08:36:01', '2026-04-22 08:36:01'),
(14, 3, 3, NULL, NULL, NULL, 'submitted', NULL, NULL, 'Purchase request submitted to Purchasing.', NULL, NULL, '2026-04-23 02:04:39', '2026-04-23 02:04:39'),
(15, 4, 4, NULL, NULL, NULL, 'submitted', NULL, NULL, 'Purchase request submitted to Purchasing.', NULL, NULL, '2026-04-23 06:25:53', '2026-04-23 06:25:53'),
(16, 4, 5, NULL, NULL, NULL, 'submitted_to_accounting', NULL, NULL, 'Purchase request submitted to Accounting.', NULL, NULL, '2026-04-23 06:48:43', '2026-04-23 06:48:43'),
(17, 4, 6, NULL, NULL, NULL, 'on_hold_by_accounting', NULL, NULL, 'karena belum ada uang', NULL, NULL, '2026-04-23 07:01:34', '2026-04-23 07:01:34'),
(18, 4, 6, NULL, NULL, NULL, 'submitted_to_gm', NULL, NULL, 'Purchase request submitted to GM.', NULL, NULL, '2026-04-23 07:05:12', '2026-04-23 07:05:12'),
(19, 4, 8, NULL, NULL, NULL, 'gm_approved', NULL, NULL, 'Purchase request approved by GM and handed over to Financial Controller.', NULL, NULL, '2026-04-23 07:09:46', '2026-04-23 07:09:46'),
(20, 4, 10, NULL, NULL, NULL, 'waiting_payment', NULL, NULL, 'Purchase request marked as waiting payment by Financial Controller.', NULL, NULL, '2026-04-23 07:16:17', '2026-04-23 07:16:17'),
(21, 4, 10, NULL, NULL, NULL, 'paid_to_vendor', NULL, NULL, 'Purchase request marked as paid to vendor by Financial Controller.', NULL, NULL, '2026-04-23 07:17:45', '2026-04-23 07:17:45'),
(22, 4, 5, NULL, NULL, NULL, 'on_shipping', NULL, NULL, 'The PR is Paid to vendor by Financial Controller and On Shipping.', NULL, NULL, '2026-04-23 07:19:28', '2026-04-23 07:19:28'),
(23, 4, 5, NULL, NULL, NULL, 'received_by_requester', NULL, NULL, 'The item was received by Dwita on 2026-04-23. Note: barang diterima sesuai request', NULL, NULL, '2026-04-23 07:21:19', '2026-04-23 07:21:19');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_vendor_offers`
--

DROP TABLE IF EXISTS `purchase_request_vendor_offers`;
CREATE TABLE IF NOT EXISTS `purchase_request_vendor_offers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint UNSIGNED NOT NULL,
  `vendor_id` bigint UNSIGNED DEFAULT NULL,
  `vendor_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_total` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IDR',
  `lead_time_days` int DEFAULT NULL,
  `offer_notes` text COLLATE utf8mb4_unicode_ci,
  `quotation_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_rank` tinyint UNSIGNED DEFAULT NULL,
  `is_selected_by_accounting` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pr_vendor_offer_rank_unique` (`purchase_request_id`,`offer_rank`),
  KEY `purchase_request_vendor_offers_vendor_id_foreign` (`vendor_id`),
  KEY `purchase_request_vendor_offers_created_by_foreign` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('5axTTW5Dhzr31NVf7fM92V6ug9EnZBR0CxMthLAA', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiI2RFlWckU4bXBOVHNINWt1TVpSZkNWUkd3OVJzM24zSkM5bFk0MktaIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL3B1cmNoYXNpbmciLCJyb3V0ZSI6ImZpbGFtZW50LnB1cmNoYXNpbmcucGFnZXMuZGFzaGJvYXJkIn0sInVybCI6W10sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo1LCJwYXNzd29yZF9oYXNoX3dlYiI6IjA3YzRlZWUyZWUyNDMzNTRiYmIxZjlmYTM3ZDczOWFhMzYyYTQwMGYwZGQ1YWEyMjFkOWQ4NDYxMDMxMGM2OTQiLCJmaWxhbWVudCI6W10sInRhYmxlcyI6eyI0MWNmZWZmNGQ2ZjM3N2M0NGFjZWIzMWE1MWY5MmVmMl9jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InJlcXVlc3RfbnVtYmVyIiwibGFiZWwiOiJQUiIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJyZXF1ZXN0ZXJfbmFtZSIsImxhYmVsIjoiUmVxdWVzdGVyIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImRlcGFydG1lbnRfbmFtZSIsImxhYmVsIjoiRGVwYXJ0bWVudCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ0aXRsZSIsImxhYmVsIjoiUmVxdWVzdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJwcmlvcml0eSIsImxhYmVsIjoiUHJpb3JpdHkiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJTdGF0dXMiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3VycmVudF9kZXNrIiwibGFiZWwiOiJEZXNrIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6IndhaXRpbmdfZGF5cyIsImxhYmVsIjoiRGF5cyBXYWl0aW5nIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Imxhc3RfYWN0aXZpdHlfYXQiLCJsYWJlbCI6Ikxhc3QgQWN0aXZpdHkiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfV19fQ==', 1776929695),
('olhA0x8F9it8x9fuOEnN3GUBgyvgKxEFaxvshSVD', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJuc3k3WTlNQXBsVmtHaHNzV29KOXlwUWtFUWFjOW9lS1RseTR0bkU4IiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9wdXJjaGFzaW5nIiwicm91dGUiOiJmaWxhbWVudC5wdXJjaGFzaW5nLnBhZ2VzLmRhc2hib2FyZCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo0LCJwYXNzd29yZF9oYXNoX3dlYiI6IjA3YzRlZWUyZWUyNDMzNTRiYmIxZjlmYTM3ZDczOWFhMzYyYTQwMGYwZGQ1YWEyMjFkOWQ4NDYxMDMxMGM2OTQiLCJ0YWJsZXMiOnsiNDFjZmVmZjRkNmYzNzdjNDRhY2ViMzFhNTFmOTJlZjJfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJyZXF1ZXN0X251bWJlciIsImxhYmVsIjoiUFIiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicmVxdWVzdGVyX25hbWUiLCJsYWJlbCI6IlJlcXVlc3RlciIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJkZXBhcnRtZW50X25hbWUiLCJsYWJlbCI6IkRlcGFydG1lbnQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoidGl0bGUiLCJsYWJlbCI6IlJlcXVlc3QiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoicHJpb3JpdHkiLCJsYWJlbCI6IlByaW9yaXR5IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InN0YXR1cyIsImxhYmVsIjoiU3RhdHVzIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImN1cnJlbnRfZGVzayIsImxhYmVsIjoiRGVzayIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ3YWl0aW5nX2RheXMiLCJsYWJlbCI6IkRheXMgV2FpdGluZyIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJsYXN0X2FjdGl2aXR5X2F0IiwibGFiZWwiOiJMYXN0IEFjdGl2aXR5IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH1dfX0=', 1776929651);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_role_index` (`role`),
  KEY `users_department_name_index` (`department_name`),
  KEY `users_is_active_index` (`is_active`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `role`, `department_name`, `is_active`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', 'admin@admin.com', 'admin', NULL, 1, NULL, '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-03-23 23:26:06', '2026-03-23 23:26:06'),
(2, 'IT Department', 'it', 'it@nandiniapps.cloud', 'requester', 'IT', 1, '2026-03-24 00:09:41', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', '78gOINrikup9sIwpsNwo7Q1s1LeuyQAAm5Z2yfqzd6K9sACn3pRylIiOWPyi', '2026-03-24 00:09:41', '2026-03-24 00:09:41'),
(3, 'Engineering Department', 'eng', 'eng@nandiniapps.cloud', 'requester', 'Engineering', 1, '2026-03-24 00:09:41', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'qF2qxLzjPQ7oxjANpZVh3SGvzUF4hEHDZoFVx9wPuxQ5My4PTPl5yoO5QkrK', '2026-03-24 00:09:41', '2026-03-24 00:09:41'),
(4, 'Housekeeping & Garden', 'hk', 'hk@nandiniapps.cloud', 'requester', 'Housekeeping & Garden', 1, '2026-03-24 00:09:41', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'd2c1BpvRGDRBdMHhcLqgzpcCnjQvMEXeiymEzDryMifQdcScOUUKW7PM4LoS', '2026-03-24 00:09:41', '2026-03-24 00:09:41'),
(5, 'Purchasing', 'purchasing', 'purchasing@nandiniapps.cloud', 'purchasing', 'Purchasing', 1, '2026-03-24 00:09:41', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'ElPtNRM9SyGp4x8IFyZWdTqnu3mjTzks8DmatkApIUn9NEi3oE2tgjo73g2f', '2026-03-24 00:09:41', '2026-03-24 00:09:41'),
(6, 'Cost Control', 'cc', 'costcontrol@nandiniapps.cloud', 'accounting', 'Accounting', 1, '2026-03-24 00:09:42', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'tRnFbjfemzsyCedvDruXYo0q6LLgwyIGHlfpw21UROJjjgRAqceXkxMllVCM', '2026-03-24 00:09:42', '2026-03-24 00:09:42'),
(7, 'Bookkeeper', 'bookkeeper', 'bookkeeper@nandiniapps.cloud', 'accounting', 'Accounting', 1, '2026-03-24 00:09:42', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'GbaEqebIHj', '2026-03-24 00:09:42', '2026-03-24 00:09:42'),
(8, 'General Manager', 'gm', 'gm@nandiniapps.cloud', 'gm', NULL, 1, '2026-03-24 00:09:42', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'da3ctYBlIPv4UtWi7MN82Cobcs5YganJKHAoYBWOEFlfEP5pWCtBrZABFJ2Y', '2026-03-24 00:09:42', '2026-03-24 00:09:42'),
(9, 'Owner Representative', 'or', 'or@nandiniapps.cloud', 'owner', NULL, 1, '2026-03-24 00:09:43', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', 'jZLv7F2sH1', '2026-03-24 00:09:43', '2026-03-24 00:09:43'),
(10, 'Financial Controller', 'fc', 'fc@nandiniapps.cloud', 'financial_controller', 'Accounting', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(11, 'FB Product', 'fbp', 'fbp@nandiniapps.cloud', 'requester', 'FB Product', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(12, 'FB Service', 'fbs', 'fbs@nandiniapps.cloud', 'requester', 'FB Service', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(13, 'Spa', 'spa', 'spa@nandiniapps.cloud', 'requester', 'Spa', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(14, 'Sales & Marketing', 'sm', 'sm@nandiniapps.cloud', 'requester', 'Sales & Marketing', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(15, 'Front Office', 'fo', 'fo@nandiniapps.cloud', 'requester', 'Front Office', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38'),
(16, 'Human Resources & Security', 'hr', 'hr@nandiniapps.cloud', 'requester', 'Human Resources & Security', 1, '2026-04-16 03:43:38', '$2y$12$IZ.L6mTyGEkDef0.32yU9eBs1S4E09WlOzMIDhPMCEsX.4PhDEpMW', NULL, '2026-04-16 03:43:38', '2026-04-16 03:43:38');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text,
  `notes` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `category`, `contact_person`, `phone`, `email`, `address`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CV Bali Netindo', NULL, 'Pak Komang', '+62 361 555 0188', 'sales@balinetindo.example', NULL, NULL, 1, '2026-04-21 07:48:11', '2026-04-21 07:48:11'),
(2, 'CV Nusantara Mosquito Net Works', NULL, 'Made', '+62 361 555 0314', 'offers@nmnw.example', NULL, NULL, 1, '2026-04-21 07:48:11', '2026-04-21 07:48:11'),
(3, 'PT Ubud Textile Supply', NULL, 'Ketut', '+62 361 555 0246', 'quotation@ubudtextiles.example', NULL, NULL, 1, '2026-04-21 07:48:11', '2026-04-21 07:48:11'),
(4, 'Vendor A', NULL, 'Made B', NULL, NULL, NULL, NULL, 1, '2026-04-23 06:44:04', '2026-04-23 06:44:04'),
(5, 'Vendor B', NULL, 'Komang C', NULL, NULL, NULL, NULL, 1, '2026-04-23 06:44:04', '2026-04-23 06:44:04'),
(6, 'Vendor C', NULL, 'asghdfagsdas', NULL, NULL, NULL, NULL, 1, '2026-04-23 06:44:04', '2026-04-23 06:44:04');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
