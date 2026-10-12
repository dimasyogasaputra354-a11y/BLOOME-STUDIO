-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 12, 2026 at 01:53 PM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bloome_studio`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_daily_report_chart` (IN `p_date` DATE)  BEGIN
  DECLARE v_min BIGINT UNSIGNED DEFAULT 0;
  DECLARE v_max BIGINT UNSIGNED DEFAULT 0;

  DROP TEMPORARY TABLE IF EXISTS `tmp_daily_series`;

  -- 14 hari: p_date-13 ... p_date (tanpa CTE, kompatibel versi lama)
  CREATE TEMPORARY TABLE `tmp_daily_series` AS
  SELECT
    DATE_SUB(p_date, INTERVAL n.i DAY) AS d,
    COALESCE((
      SELECT SUM(t.`amount`)
      FROM `transactions` t
      WHERE t.`status` = 'paid'
        AND t.`trx_datetime` >= DATE_SUB(p_date, INTERVAL n.i DAY)
        AND t.`trx_datetime` <  DATE_ADD(DATE_SUB(p_date, INTERVAL n.i DAY), INTERVAL 1 DAY)
    ), 0) AS revenue
  FROM (
    SELECT 0 AS i UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
    UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11
    UNION ALL SELECT 12 UNION ALL SELECT 13
  ) n;

  SELECT MIN(revenue), MAX(revenue) INTO v_min, v_max FROM `tmp_daily_series`;

  SELECT
    s.d                                   AS `tanggal`,
    YEAR(s.d)                             AS `tahun`,
    DATE_FORMAT(s.d, '%d-%m-%Y')          AS `label_tanggal`,
    s.revenue                             AS `pendapatan`,
    (s.revenue = v_min)                   AS `is_terendah`,
    (s.revenue = v_max)                   AS `is_tertinggi`,
    v_min                                 AS `pendapatan_terendah`,
    v_max                                 AS `pendapatan_tertinggi`
  FROM `tmp_daily_series` s
  ORDER BY s.d;

  DROP TEMPORARY TABLE IF EXISTS `tmp_daily_series`;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_daily_report_detail` (IN `p_date` DATE, IN `p_search` VARCHAR(100))  BEGIN
  SELECT
    TIME_FORMAT(v.`trx_datetime`, '%H:%i')                          AS `Jam`,
    v.`member_name`                                                 AS `Nama Member`,
    v.`item_name`                                                   AS `Paket/Layanan`,
    CASE v.`trx_type` WHEN 'sale' THEN 'Beli' ELSE 'Pakai Paket' END AS `Tipe`,
    v.`sessions_remaining`                                          AS `Sisa Sesi`,
    v.`member_phone`                                                AS `No HP`,
    v.`coach_name`                                                  AS `Coach`,
    v.`complaint_injury`                                            AS `Keluhan/Injury`,
    v.`unit_price`                                                  AS `Harga`,
    v.`amount`                                                      AS `Pendapatan`
  FROM `v_transaction_detail` v
  WHERE v.`status` = 'paid'
    AND v.`trx_datetime` >= p_date
    AND v.`trx_datetime` <  DATE_ADD(p_date, INTERVAL 1 DAY)
    AND (
      p_search IS NULL OR TRIM(p_search) = ''
      OR v.`member_name` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_code` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_phone` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`coach_name`  LIKE CONCAT('%', TRIM(p_search), '%')
    )
  ORDER BY v.`trx_datetime`, v.`id`;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_daily_report_summary` (IN `p_date` DATE)  BEGIN
  SELECT
    p_date                                                         AS `tanggal`,
    YEAR(p_date)                                                   AS `tahun`,
    COALESCE(SUM(t.`amount`), 0)                                   AS `pendapatan_hari_ini`,
    COUNT(t.`id`)                                                  AS `total_transaksi_checkin`,
    COALESCE(SUM(CASE WHEN t.`trx_type` = 'sale' THEN t.`sessions_total` ELSE 0 END), 0)
                                                                   AS `total_sesi_termasuk_paket`,
    ROUND(COALESCE(SUM(t.`amount`) / NULLIF(SUM(t.`amount` > 0), 0), 0))
                                                                   AS `rata_rata_transaksi`
  FROM `transactions` t
  WHERE t.`status` = 'paid'
    AND t.`trx_datetime` >= p_date
    AND t.`trx_datetime` <  DATE_ADD(p_date, INTERVAL 1 DAY);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_daily_report_total` (IN `p_date` DATE, IN `p_search` VARCHAR(100))  BEGIN
  SELECT
    p_date                         AS `tanggal`,
    COUNT(v.`id`)                  AS `jumlah_baris`,
    COALESCE(SUM(v.`amount`), 0)   AS `total_pendapatan`
  FROM `v_transaction_detail` v
  WHERE v.`status` = 'paid'
    AND v.`trx_datetime` >= p_date
    AND v.`trx_datetime` <  DATE_ADD(p_date, INTERVAL 1 DAY)
    AND (
      p_search IS NULL OR TRIM(p_search) = ''
      OR v.`member_name` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_code` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_phone` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`coach_name`  LIKE CONCAT('%', TRIM(p_search), '%')
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_monthly_chart` (IN `p_year` SMALLINT, IN `p_sort` VARCHAR(12))  BEGIN
  DECLARE v_min BIGINT UNSIGNED DEFAULT 0;
  DECLARE v_max BIGINT UNSIGNED DEFAULT 0;

  DROP TEMPORARY TABLE IF EXISTS `tmp_monthly_series`;

  -- 12 bulan pada tahun terpilih (tanpa CTE, kompatibel versi lama)
  CREATE TEMPORARY TABLE `tmp_monthly_series` AS
  SELECT
    n.m AS m,
    COALESCE((
      SELECT SUM(t.`amount`)
      FROM `transactions` t
      WHERE t.`status` = 'paid'
        AND t.`trx_datetime` >= DATE_ADD(MAKEDATE(p_year, 1), INTERVAL (n.m - 1) MONTH)
        AND t.`trx_datetime` <  DATE_ADD(MAKEDATE(p_year, 1), INTERVAL n.m MONTH)
    ), 0) AS revenue
  FROM (
    SELECT 1 AS m UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
    UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
    UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
  ) n;

  SELECT MIN(revenue), MAX(revenue) INTO v_min, v_max FROM `tmp_monthly_series`;

  SELECT
    p_year    AS `tahun`,
    s.m       AS `bulan`,
    ELT(s.m, 'Januari','Februari','Maret','April','Mei','Juni',
             'Juli','Agustus','September','Oktober','November','Desember') AS `nama_bulan`,
    s.revenue AS `pendapatan`,
    (s.revenue = v_min) AS `is_terendah`,
    (s.revenue = v_max) AS `is_tertinggi`,
    v_min     AS `pendapatan_terendah`,
    v_max     AS `pendapatan_tertinggi`
  FROM `tmp_monthly_series` s
  ORDER BY
    CASE WHEN p_sort = 'revenue_asc'  THEN s.revenue END ASC,
    CASE WHEN p_sort = 'revenue_desc' THEN s.revenue END DESC,
    s.m ASC;

  DROP TEMPORARY TABLE IF EXISTS `tmp_monthly_series`;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_monthly_package_revenue` (IN `p_year` SMALLINT, IN `p_month` TINYINT)  BEGIN
  DECLARE v_start DATE;
  DECLARE v_end   DATE;

  SET v_start = DATE_ADD(MAKEDATE(p_year, 1), INTERVAL (p_month - 1) MONTH);
  SET v_end   = DATE_ADD(v_start, INTERVAL 1 MONTH);

  SELECT x.`Kategori`, x.`Paket/Layanan`, x.`Jumlah Transaksi`, x.`Harga`, x.`Pendapatan`
  FROM (
    SELECT 0 AS srt,
           t.`item_type`   AS `Kategori`,
           t.`item_name`   AS `Paket/Layanan`,
           SUM(t.`qty`)    AS `Jumlah Transaksi`,
           t.`unit_price`  AS `Harga`,
           SUM(t.`amount`) AS `Pendapatan`
    FROM `transactions` t
    WHERE t.`status` = 'paid'
      AND t.`trx_type` = 'sale'
      AND t.`trx_datetime` >= v_start
      AND t.`trx_datetime` <  v_end
    GROUP BY t.`item_type`, t.`item_name`, t.`unit_price`
    UNION ALL
    SELECT 1,
           'TOTAL',
           'Total Pendapatan Bulan Ini',
           COALESCE(SUM(t.`qty`), 0),
           NULL,
           COALESCE(SUM(t.`amount`), 0)
    FROM `transactions` t
    WHERE t.`status` = 'paid'
      AND t.`trx_type` = 'sale'
      AND t.`trx_datetime` >= v_start
      AND t.`trx_datetime` <  v_end
  ) x
  ORDER BY x.srt, x.`Kategori`, x.`Pendapatan` DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_monthly_report_detail` (IN `p_year` SMALLINT, IN `p_month` TINYINT, IN `p_search` VARCHAR(100))  BEGIN
  DECLARE v_start DATE;
  DECLARE v_end   DATE;

  SET v_start = DATE_ADD(MAKEDATE(p_year, 1), INTERVAL (p_month - 1) MONTH);
  SET v_end   = DATE_ADD(v_start, INTERVAL 1 MONTH);

  SELECT
    DATE_FORMAT(v.`trx_datetime`, '%d-%m-%Y')                       AS `Tanggal`,
    TIME_FORMAT(v.`trx_datetime`, '%H:%i')                          AS `Jam`,
    v.`member_name`                                                 AS `Nama Member`,
    v.`item_name`                                                   AS `Paket/Layanan`,
    CASE v.`trx_type` WHEN 'sale' THEN 'Beli' ELSE 'Pakai Paket' END AS `Tipe`,
    v.`sessions_remaining`                                          AS `Sisa Sesi`,
    v.`member_phone`                                                AS `No HP`,
    v.`coach_name`                                                  AS `Coach`,
    v.`complaint_injury`                                            AS `Keluhan/Injury`,
    v.`unit_price`                                                  AS `Harga`,
    v.`amount`                                                      AS `Pendapatan`
  FROM `v_transaction_detail` v
  WHERE v.`status` = 'paid'
    AND v.`trx_datetime` >= v_start
    AND v.`trx_datetime` <  v_end
    AND (
      p_search IS NULL OR TRIM(p_search) = ''
      OR v.`member_name` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_code` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_phone` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`coach_name`  LIKE CONCAT('%', TRIM(p_search), '%')
    )
  ORDER BY v.`trx_datetime`, v.`id`;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_monthly_report_total` (IN `p_year` SMALLINT, IN `p_month` TINYINT, IN `p_search` VARCHAR(100))  BEGIN
  DECLARE v_start DATE;
  DECLARE v_end   DATE;

  SET v_start = DATE_ADD(MAKEDATE(p_year, 1), INTERVAL (p_month - 1) MONTH);
  SET v_end   = DATE_ADD(v_start, INTERVAL 1 MONTH);

  SELECT
    p_year                         AS `tahun`,
    p_month                        AS `bulan`,
    COUNT(v.`id`)                  AS `jumlah_baris`,
    COALESCE(SUM(v.`amount`), 0)   AS `total_pendapatan`
  FROM `v_transaction_detail` v
  WHERE v.`status` = 'paid'
    AND v.`trx_datetime` >= v_start
    AND v.`trx_datetime` <  v_end
    AND (
      p_search IS NULL OR TRIM(p_search) = ''
      OR v.`member_name` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_code` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`member_phone` LIKE CONCAT('%', TRIM(p_search), '%')
      OR v.`coach_name`  LIKE CONCAT('%', TRIM(p_search), '%')
    );
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_monthly_summary` (IN `p_year` SMALLINT, IN `p_month` TINYINT)  BEGIN
  DECLARE v_start DATE;
  DECLARE v_end   DATE;
  DECLARE v_best_date DATE DEFAULT NULL;
  DECLARE v_best_rev  INT UNSIGNED DEFAULT NULL;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_best_date = NULL;

  SET v_start = DATE_ADD(MAKEDATE(p_year, 1), INTERVAL (p_month - 1) MONTH);
  SET v_end   = DATE_ADD(v_start, INTERVAL 1 MONTH);

  -- Hari terbaik = tanggal dengan pendapatan tertinggi (seri: tanggal paling awal)
  SELECT DATE(t.`trx_datetime`), SUM(t.`amount`)
    INTO v_best_date, v_best_rev
  FROM `transactions` t
  WHERE t.`status` = 'paid'
    AND t.`trx_datetime` >= v_start
    AND t.`trx_datetime` <  v_end
  GROUP BY DATE(t.`trx_datetime`)
  ORDER BY SUM(t.`amount`) DESC, DATE(t.`trx_datetime`) ASC
  LIMIT 1;

  SELECT
    p_year                                                         AS `tahun`,
    p_month                                                        AS `bulan`,
    ELT(p_month, 'Januari','Februari','Maret','April','Mei','Juni',
                 'Juli','Agustus','September','Oktober','November','Desember')
                                                                   AS `nama_bulan`,
    COALESCE(SUM(t.`amount`), 0)                                   AS `total_pendapatan_bulan_ini`,
    COUNT(t.`id`)                                                  AS `total_transaksi_bulan_ini`,
    COALESCE(SUM(CASE WHEN t.`trx_type` = 'sale' THEN t.`sessions_total` ELSE 0 END), 0)
                                                                   AS `total_sesi_bulan_ini`,
    v_best_date                                                    AS `hari_terbaik_tanggal`,
    COALESCE(v_best_rev, 0)                                        AS `hari_terbaik_pendapatan`
  FROM `transactions` t
  WHERE t.`status` = 'paid'
    AND t.`trx_datetime` >= v_start
    AND t.`trx_datetime` <  v_end;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_search_coaches` (IN `p_q` VARCHAR(100))  BEGIN
  SELECT
    c.`id`,
    c.`name`,
    c.`is_active`,
    COUNT(s.`id`) AS `jumlah_slot_jadwal`
  FROM `coaches` c
  LEFT JOIN `class_schedules` s ON s.`coach_id` = c.`id` AND s.`is_active` = 1
  WHERE p_q IS NULL OR TRIM(p_q) = '' OR c.`name` LIKE CONCAT('%', TRIM(p_q), '%')
  GROUP BY c.`id`, c.`name`, c.`is_active`
  ORDER BY c.`name`
  LIMIT 50;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_search_members` (IN `p_q` VARCHAR(100))  BEGIN
  SELECT
    m.`id`,
    m.`member_code`,
    m.`full_name`,
    m.`phone`,
    COUNT(t.`id`)          AS `total_kunjungan`,
    MAX(t.`trx_datetime`)  AS `kunjungan_terakhir`
  FROM `members` m
  LEFT JOIN `transactions` t ON t.`member_id` = m.`id` AND t.`status` = 'paid'
  WHERE m.`is_active` = 1
    AND (
      p_q IS NULL OR TRIM(p_q) = ''
      OR m.`full_name`   LIKE CONCAT('%', TRIM(p_q), '%')
      OR m.`member_code` LIKE CONCAT('%', TRIM(p_q), '%')
      OR m.`phone`       LIKE CONCAT('%', TRIM(p_q), '%')
    )
  GROUP BY m.`id`, m.`member_code`, m.`full_name`, m.`phone`
  ORDER BY m.`full_name`
  LIMIT 50;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `beauty_categories`
--

CREATE TABLE `beauty_categories` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` enum('lash','brow_lips','nail') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beauty_categories`
--

INSERT INTO `beauty_categories` (`id`, `code`, `name`, `group_name`, `sort_order`) VALUES
(1, 'EYELASH', 'Eyelash Extension', 'lash', 1),
(2, 'LASHLIFT', 'LashLift', 'lash', 2),
(3, 'LASH_ADDITIONAL', 'Lash Additional', 'lash', 3),
(4, 'BROW_LIPS', 'Brow & Lips', 'brow_lips', 4),
(5, 'NAIL_HAND', 'Nail Treatment - Hand', 'nail', 5),
(6, 'NAIL_ART', 'Nail Art', 'nail', 6),
(7, 'NAIL_FOOT', 'Nail Treatment - Foot', 'nail', 7),
(8, 'NAIL_ACCESSORIES', 'Accessories', 'nail', 8),
(9, 'NAIL_REMOVAL', 'Removal', 'nail', 9),
(10, 'NAIL_EXTENSION', 'Extension Nails', 'nail', 10);

-- --------------------------------------------------------

--
-- Table structure for table `beauty_services`
--

CREATE TABLE `beauty_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_type` enum('fixed','range','percent','free') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `price` int(10) UNSIGNED DEFAULT NULL COMMENT 'harga / harga minimum (Rp)',
  `price_max` int(10) UNSIGNED DEFAULT NULL COMMENT 'harga maksimum untuk price_type = range',
  `percent_value` tinyint(3) UNSIGNED DEFAULT NULL COMMENT 'persen untuk price_type = percent',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `beauty_services`
--

INSERT INTO `beauty_services` (`id`, `category_id`, `name`, `description`, `price_type`, `price`, `price_max`, `percent_value`, `notes`, `sort_order`, `is_active`, `is_featured`) VALUES
(1, 1, 'Classic Lash', NULL, 'fixed', 150000, NULL, NULL, NULL, 1, 1, 0),
(2, 1, 'Double Lash', NULL, 'fixed', 200000, NULL, NULL, NULL, 2, 1, 0),
(3, 1, 'Volume Lash', NULL, 'fixed', 250000, NULL, NULL, NULL, 3, 1, 0),
(4, 1, 'Russian Lash', NULL, 'fixed', 300000, NULL, NULL, NULL, 4, 1, 0),
(5, 1, 'Anime Lash', 'Include under lash', 'fixed', 350000, NULL, NULL, NULL, 5, 1, 0),
(6, 1, 'Meduza Lash', 'Include under lash', 'fixed', 500000, NULL, NULL, NULL, 6, 1, 0),
(7, 2, 'Lashlift & Tint', 'Natural lash, include Black Tint & keratin made in Korea', 'fixed', 185000, NULL, NULL, NULL, 1, 1, 1),
(8, 3, 'Retouch', NULL, 'percent', NULL, NULL, 50, NULL, 1, 1, 0),
(9, 3, 'Removal From Bloome/Other Salon', NULL, 'fixed', 50000, NULL, NULL, NULL, 2, 1, 0),
(10, 3, 'Guarantee 3 Days Treatment', NULL, 'free', 0, NULL, NULL, NULL, 3, 1, 0),
(11, 4, 'Brow Shading', 'Include retouch', 'fixed', 1499000, NULL, NULL, NULL, 1, 1, 0),
(12, 4, 'Brow Removal', '2x visit', 'fixed', 999000, NULL, NULL, NULL, 2, 1, 0),
(13, 4, 'Lip Blush', 'Include retouch', 'fixed', 1499000, NULL, NULL, NULL, 3, 1, 0),
(14, 5, 'Manicure', 'Wash salt, trimming, buffing, shaping, cuticle care, vitamin, lotion', 'fixed', 80000, NULL, NULL, NULL, 1, 1, 1),
(15, 5, 'Basic Gel / Basic Gel Halal', 'Gel polish only for 10 fingers', 'fixed', 150000, NULL, NULL, NULL, 2, 1, 0),
(16, 5, 'Dry/Express Manicure + Basic Gel', 'Trimming, buffing, shaping, cuticle care, vitamin', 'fixed', 200000, NULL, NULL, NULL, 3, 1, 0),
(17, 5, 'Dry/Express Manicure + Cat Eye Nails', 'Trimming, buffing, shaping, cuticle care, gel cat eye, vitamin', 'fixed', 230000, NULL, NULL, NULL, 4, 1, 0),
(18, 5, 'Dry/Express Manicure + Glitter Nails', 'Trimming, buffing, shaping, cuticle care, gel glitter, vitamin', 'fixed', 230000, NULL, NULL, NULL, 5, 1, 0),
(19, 6, 'Easy', 'Glitter, Foil, Strengthen Gel, Dots, Cat Eyes, Sticker', 'fixed', 5000, NULL, NULL, NULL, 1, 1, 0),
(20, 6, 'Medium', 'Ombre, Chrome, Korean Glass, French, Blush, Swirl, Simple Marble', 'fixed', 10000, NULL, NULL, NULL, 2, 1, 0),
(21, 6, 'Advance', 'Advance Marble, Lines, Gold Flakes, Sea Shell, Dry Flower', 'fixed', 20000, NULL, NULL, NULL, 3, 1, 0),
(22, 6, 'Hard', '3D Flower, 3D Acc, Character, Pattern', 'range', 25000, 50000, NULL, NULL, 4, 1, 0),
(23, 7, 'SPA Pedicure', 'Foot spa using bath salt, scrub, cleaning, trimming, buffing, shaping, cuticle care, shining, vitamin, lotion', 'fixed', 100000, NULL, NULL, NULL, 1, 1, 1),
(24, 7, 'Basic Gel / Basic Gel Halal', 'Gel polish only for 10 fingers', 'fixed', 150000, NULL, NULL, NULL, 2, 1, 0),
(25, 7, 'Dry/Express Manicure + Basic Gel', 'Trimming, buffing, shaping, cuticle care, gel polish, vitamin', 'fixed', 210000, NULL, NULL, NULL, 3, 1, 0),
(26, 7, 'Callus Treatment', 'Soaking corns and calluses in warm, soapy water softens them', 'fixed', 120000, NULL, NULL, NULL, 4, 1, 0),
(27, 7, 'Full Foot Treatment', 'Spa pedicure + Basic Gel + Callus Treatment', 'fixed', 330000, NULL, NULL, NULL, 5, 1, 0),
(28, 8, 'Small Swarovski', NULL, 'fixed', 10000, NULL, NULL, NULL, 1, 1, 0),
(29, 8, 'Pearl', NULL, 'fixed', 5000, NULL, NULL, NULL, 2, 1, 0),
(30, 8, 'Rhinestones', NULL, 'fixed', 5000, NULL, NULL, NULL, 3, 1, 0),
(31, 8, 'Charm', NULL, 'fixed', 25000, NULL, NULL, NULL, 4, 1, 0),
(32, 9, 'Remove Gel Color', NULL, 'fixed', 40000, NULL, NULL, NULL, 1, 1, 0),
(33, 9, 'Remove EXT From Bloome', NULL, 'fixed', 70000, NULL, NULL, NULL, 2, 1, 0),
(34, 9, 'Remove EXT From Another', NULL, 'fixed', 100000, NULL, NULL, NULL, 3, 1, 0),
(35, 10, 'Softgel Tips For 10 Fingers', NULL, 'fixed', 100000, NULL, NULL, NULL, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `class_schedules`
--

CREATE TABLE `class_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `discipline` enum('pilates','yoga') COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_type` enum('reformer','mat','yoga') COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Monday ... 7=Sunday',
  `start_time` time NOT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT '60',
  `coach_id` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'NULL = coach belum ditentukan',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_schedules`
--

INSERT INTO `class_schedules` (`id`, `discipline`, `class_type`, `day_of_week`, `start_time`, `duration_minutes`, `coach_id`, `is_active`) VALUES
(1, 'pilates', 'reformer', 1, '07:00:00', 420, 1, 1),
(2, 'pilates', 'reformer', 1, '08:00:00', 60, 1, 0),
(3, 'pilates', 'reformer', 1, '09:00:00', 60, 1, 0),
(4, 'pilates', 'reformer', 1, '10:00:00', 60, 1, 0),
(5, 'pilates', 'reformer', 1, '11:00:00', 60, 1, 0),
(6, 'pilates', 'reformer', 1, '12:00:00', 60, 1, 0),
(7, 'pilates', 'reformer', 1, '13:00:00', 60, 1, 0),
(8, 'pilates', 'mat', 1, '15:00:00', 60, 2, 1),
(9, 'pilates', 'reformer', 1, '16:00:00', 240, 2, 1),
(10, 'pilates', 'reformer', 1, '17:00:00', 60, 2, 0),
(11, 'pilates', 'reformer', 1, '18:00:00', 60, 2, 0),
(12, 'pilates', 'reformer', 1, '19:00:00', 60, 2, 0),
(13, 'pilates', 'reformer', 2, '07:00:00', 480, 3, 1),
(14, 'pilates', 'reformer', 2, '08:00:00', 60, 3, 0),
(15, 'pilates', 'reformer', 2, '09:00:00', 60, 3, 0),
(16, 'pilates', 'reformer', 2, '10:00:00', 60, 3, 0),
(17, 'pilates', 'reformer', 2, '11:00:00', 60, 3, 0),
(18, 'pilates', 'reformer', 2, '12:00:00', 60, 3, 0),
(19, 'pilates', 'reformer', 2, '13:00:00', 60, 3, 0),
(20, 'pilates', 'reformer', 2, '14:00:00', 60, 3, 0),
(21, 'pilates', 'reformer', 2, '16:00:00', 180, 3, 1),
(22, 'pilates', 'reformer', 2, '17:00:00', 60, 3, 0),
(23, 'pilates', 'reformer', 2, '18:00:00', 60, 3, 0),
(24, 'pilates', 'reformer', 2, '20:00:00', 60, 3, 1),
(25, 'pilates', 'mat', 3, '07:00:00', 60, 4, 1),
(26, 'pilates', 'reformer', 3, '08:00:00', 240, 4, 1),
(27, 'pilates', 'reformer', 3, '09:00:00', 60, 4, 0),
(28, 'pilates', 'reformer', 3, '10:00:00', 60, 4, 0),
(29, 'pilates', 'reformer', 3, '11:00:00', 60, 4, 0),
(30, 'pilates', 'reformer', 3, '13:00:00', 420, NULL, 1),
(31, 'pilates', 'reformer', 3, '14:00:00', 60, NULL, 0),
(32, 'pilates', 'reformer', 3, '15:00:00', 60, NULL, 0),
(33, 'pilates', 'reformer', 3, '16:00:00', 60, NULL, 0),
(34, 'pilates', 'reformer', 3, '17:00:00', 60, NULL, 0),
(35, 'pilates', 'reformer', 3, '18:00:00', 60, NULL, 0),
(36, 'pilates', 'reformer', 3, '19:00:00', 60, NULL, 0),
(37, 'pilates', 'reformer', 4, '07:00:00', 360, 5, 1),
(38, 'pilates', 'reformer', 4, '08:00:00', 60, 5, 0),
(39, 'pilates', 'reformer', 4, '09:00:00', 60, 5, 0),
(40, 'pilates', 'reformer', 4, '10:00:00', 60, 5, 0),
(41, 'pilates', 'reformer', 4, '11:00:00', 60, 5, 0),
(42, 'pilates', 'reformer', 4, '12:00:00', 60, 5, 0),
(43, 'pilates', 'reformer', 4, '14:00:00', 360, 2, 1),
(44, 'pilates', 'reformer', 4, '15:00:00', 60, 2, 0),
(45, 'pilates', 'reformer', 4, '16:00:00', 60, 2, 0),
(46, 'pilates', 'reformer', 4, '17:00:00', 60, 2, 0),
(47, 'pilates', 'reformer', 4, '18:00:00', 60, 2, 0),
(48, 'pilates', 'reformer', 4, '19:00:00', 60, 2, 0),
(49, 'pilates', 'reformer', 5, '07:00:00', 540, 3, 1),
(50, 'pilates', 'reformer', 5, '08:00:00', 60, 3, 0),
(51, 'pilates', 'reformer', 5, '09:00:00', 60, 3, 0),
(52, 'pilates', 'reformer', 5, '10:00:00', 60, 3, 0),
(53, 'pilates', 'reformer', 5, '11:00:00', 60, 3, 0),
(54, 'pilates', 'reformer', 5, '12:00:00', 60, 3, 0),
(55, 'pilates', 'reformer', 5, '13:00:00', 60, 3, 0),
(56, 'pilates', 'reformer', 5, '14:00:00', 60, 3, 0),
(57, 'pilates', 'reformer', 5, '15:00:00', 60, 3, 0),
(58, 'pilates', 'reformer', 5, '17:00:00', 180, 3, 1),
(59, 'pilates', 'reformer', 5, '18:00:00', 60, 3, 0),
(60, 'pilates', 'reformer', 5, '19:00:00', 60, 3, 0),
(61, 'pilates', 'mat', 6, '07:00:00', 60, 6, 1),
(62, 'pilates', 'reformer', 6, '08:00:00', 180, 6, 1),
(63, 'pilates', 'reformer', 6, '09:00:00', 60, 6, 0),
(64, 'pilates', 'reformer', 6, '10:00:00', 60, 6, 0),
(65, 'pilates', 'reformer', 6, '12:00:00', 180, 1, 1),
(66, 'pilates', 'reformer', 6, '13:00:00', 60, 1, 0),
(67, 'pilates', 'reformer', 6, '14:00:00', 60, 1, 0),
(68, 'pilates', 'reformer', 6, '16:00:00', 240, 6, 1),
(69, 'pilates', 'reformer', 6, '17:00:00', 60, 6, 0),
(70, 'pilates', 'reformer', 6, '18:00:00', 60, 6, 0),
(71, 'pilates', 'reformer', 6, '19:00:00', 60, 6, 0),
(72, 'pilates', 'reformer', 7, '07:00:00', 360, 7, 1),
(73, 'pilates', 'reformer', 7, '08:00:00', 60, 7, 0),
(74, 'pilates', 'reformer', 7, '09:00:00', 60, 7, 0),
(75, 'pilates', 'reformer', 7, '10:00:00', 60, 7, 0),
(76, 'pilates', 'reformer', 7, '11:00:00', 60, 7, 0),
(77, 'pilates', 'reformer', 7, '12:00:00', 60, 7, 0),
(78, 'pilates', 'reformer', 7, '14:00:00', 360, 8, 1),
(79, 'pilates', 'reformer', 7, '15:00:00', 60, 8, 0),
(80, 'pilates', 'reformer', 7, '16:00:00', 60, 8, 0),
(81, 'pilates', 'reformer', 7, '17:00:00', 60, 8, 0),
(82, 'pilates', 'reformer', 7, '18:00:00', 60, 8, 0),
(83, 'pilates', 'reformer', 7, '19:00:00', 60, 8, 0);

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

CREATE TABLE `coaches` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coaches`
--

INSERT INTO `coaches` (`id`, `name`, `is_active`) VALUES
(1, 'Coach Zul', 1),
(2, 'Coach Dinda', 1),
(3, 'Coach Diza', 1),
(4, 'Coach Dea', 1),
(5, 'Coach Riefaldi', 1),
(6, 'Coach Jane', 1),
(7, 'Coach Putri', 1),
(8, 'Coach Han', 1);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ktp_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` enum('admin','finance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `module`, `description`) VALUES
(1, 'finance.neraca', 'finance', 'Kelola Neraca'),
(2, 'finance.laba_rugi', 'finance', 'Kelola Laba Rugi'),
(3, 'finance.arus_kas', 'finance', 'Kelola Arus Kas'),
(4, 'finance.estatement_bank', 'finance', 'Kelola E-Statement Bank'),
(5, 'admin.users', 'admin', 'Kelola user & hak akses'),
(6, 'admin.promos', 'admin', 'Kelola promo'),
(7, 'admin.pricing_yoga', 'admin', 'Kelola pricelist Yoga'),
(8, 'admin.pricing_pilates', 'admin', 'Kelola pricelist Pilates'),
(9, 'admin.pricing_beauty', 'admin', 'Kelola pricelist Beauty Treatment'),
(10, 'admin.schedule', 'admin', 'Kelola jadwal kelas Pilates & Yoga'),
(11, 'admin.coaches', 'admin', 'Kelola data coach');

-- --------------------------------------------------------

--
-- Table structure for table `pilates_pricing`
--

CREATE TABLE `pilates_pricing` (
  `id` int(10) UNSIGNED NOT NULL,
  `kind` enum('session','membership') COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_type` enum('group','private','duo','triple') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_count` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `bonus_sessions` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `participants` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `validity_days` smallint(5) UNSIGNED DEFAULT NULL,
  `validity_label` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `no_reschedule` tinyint(1) NOT NULL DEFAULT '0',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pilates_pricing`
--

INSERT INTO `pilates_pricing` (`id`, `kind`, `class_type`, `name`, `session_count`, `bonus_sessions`, `participants`, `validity_days`, `validity_label`, `price`, `no_reschedule`, `notes`, `sort_order`, `is_active`) VALUES
(1, 'session', 'group', 'First Visit', 1, 0, 1, NULL, NULL, 99000, 1, '1 KTP + 1 No. HP (No Reschedule)', 1, 1),
(2, 'session', 'group', 'Single Visit', 1, 0, 1, NULL, NULL, 111000, 0, '1 Person Group', 2, 1),
(3, 'session', 'private', 'Private', 1, 0, 1, NULL, NULL, 469000, 0, '1 Person Private', 3, 1),
(4, 'session', 'duo', 'Duo Private', 1, 0, 2, NULL, NULL, 619000, 0, '2 Person Private', 4, 1),
(5, 'session', 'triple', 'Triple Private', 1, 0, 3, NULL, NULL, 719000, 0, '3 Person Private', 5, 1),
(6, 'session', 'group', 'Class Group', 1, 0, 7, NULL, NULL, 649000, 1, '7 Person (No Reschedule)', 6, 1),
(7, 'membership', 'group', '3 Session', 3, 0, 1, 21, '3 weeks', 289000, 0, NULL, 1, 1),
(8, 'membership', 'group', '5 Session', 5, 0, 1, 30, '1 month', 475000, 0, NULL, 2, 1),
(9, 'membership', 'group', '10 + 1 Session', 10, 1, 1, 45, '45 days', 999000, 0, NULL, 3, 1),
(10, 'membership', 'group', '20 + 2 Session', 20, 2, 1, 90, '3 months', 1969000, 0, NULL, 4, 1),
(11, 'membership', 'private', '1 Session', 1, 0, 1, 7, '1 week', 469000, 0, NULL, 1, 1),
(12, 'membership', 'private', '3 Session', 3, 0, 1, 21, '3 weeks', 1377000, 0, NULL, 2, 1),
(13, 'membership', 'private', '5 Session', 5, 0, 1, 30, '1 month', 2245000, 0, NULL, 3, 1),
(14, 'membership', 'private', '10 Session', 10, 0, 1, 45, '45 days', 4390000, 0, NULL, 4, 1),
(15, 'membership', 'duo', '3 Session', 3, 0, 2, 21, '3 weeks', 1767000, 0, 'Valid for 2 persons', 1, 1),
(16, 'membership', 'duo', '5 Session', 5, 0, 2, 30, '1 month', 2795000, 0, 'Valid for 2 persons', 2, 1),
(17, 'membership', 'duo', '10 Session', 10, 0, 2, 45, '45 days', 5200000, 0, 'Valid for 2 persons', 3, 1),
(18, 'membership', 'triple', '3 Session', 3, 0, 3, 21, '3 weeks', 2067000, 0, 'Valid for 3 persons', 1, 1),
(19, 'membership', 'triple', '5 Session', 5, 0, 3, 30, '1 month', 3295000, 0, 'Valid for 3 persons', 2, 1),
(20, 'membership', 'triple', '10 Session', 10, 0, 3, 45, '45 days', 6200000, 0, 'Valid for 3 persons', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `promos`
--

CREATE TABLE `promos` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discipline` enum('pilates','yoga','beauty','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tagline` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `price_unit` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'contoh: 2 session, group',
  `session_count` smallint(5) UNSIGNED DEFAULT NULL,
  `participants_min` tinyint(3) UNSIGNED DEFAULT NULL,
  `participants_max` tinyint(3) UNSIGNED DEFAULT NULL,
  `available_days` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `terms_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promos`
--

INSERT INTO `promos` (`id`, `code`, `discipline`, `title`, `tagline`, `price`, `price_unit`, `session_count`, `participants_min`, `participants_max`, `available_days`, `valid_from`, `valid_until`, `terms_note`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'PILATES-1010', 'pilates', '10.10 Pilates Deal', 'Your 10.10 reset', 190000, '2 session', 2, NULL, NULL, NULL, '2026-10-10', '2026-10-31', 'DM for T&C and details', '10.10-pilates.jpeg', 1, '2026-10-10 17:37:54', '2026-10-12 09:20:17'),
(2, 'YOGA-AFTER-OFFICE', 'yoga', 'Yoga After Office Hours', NULL, 700000, 'group', NULL, 8, 11, 'Monday-Friday', '2026-10-01', '2026-10-31', 'Valid throughout October 2026. DM for T&C and details', 'promo-uploads/12ddc1cd66cd1fd36962ea880c0bcdfd.jpeg', 1, '2026-10-10 17:37:54', '2026-10-12 09:40:21');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Akses penuh: kelola semua data dan modul keuangan'),
(2, 'finance', 'Hanya kelola Neraca (Inject), Laba Rugi, Arus Kas, dan E-Statement Bank');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `permission_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(2, 1),
(2, 2),
(2, 3),
(2, 4);

-- --------------------------------------------------------

--
-- Table structure for table `studio_bookings`
--

CREATE TABLE `studio_bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `public_token` char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` enum('pilates','yoga','beauty') COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_id` int(10) UNSIGNED NOT NULL,
  `promo_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_count` smallint(5) UNSIGNED NOT NULL,
  `bonus_sessions` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `participants` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `validity_days` smallint(5) UNSIGNED DEFAULT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `health_notes` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_other` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statement_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('qris','transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('pending','paid','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `booking_status` enum('pending_payment','confirmed','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_payment',
  `receipt_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `studio_bookings`
--

INSERT INTO `studio_bookings` (`id`, `public_token`, `service`, `package_id`, `promo_code`, `package_name`, `package_kind`, `session_count`, `bonus_sessions`, `participants`, `validity_days`, `price`, `full_name`, `phone`, `health_notes`, `source`, `source_other`, `statement_file`, `payment_method`, `payment_status`, `booking_status`, `receipt_file`, `expires_at`, `created_at`, `paid_at`, `verified_by`, `verified_at`) VALUES
(2, '6c51f89be502b157b5d7cb34946e08e7c3786c7a', 'pilates', 7, NULL, '3 Session', 'membership · group', 3, 0, 1, 21, 289000, 'Uji pilih bebas', '081234567890', NULL, 'Instagram', NULL, NULL, 'transfer', 'rejected', 'cancelled', NULL, '2026-10-12 11:46:04', '2026-10-11 11:46:04', NULL, 1, '2026-10-11 12:05:09'),
(3, '2797c7a8829a57b5324d554c1045e167511434c1', 'pilates', 7, NULL, '3 Session', 'membership · group', 3, 0, 1, 21, 289000, 'Preview multi sesi', '081234567890', NULL, 'Instagram', NULL, NULL, 'transfer', 'paid', 'confirmed', 'receipt-229c1d40607fd5670e3e97aae6234206c2e905ba.pdf', '2026-10-12 11:48:45', '2026-10-11 11:48:45', '2026-10-11 11:55:29', 1, '2026-10-11 11:55:29'),
(4, '506d13fe93aebac226beb26da9c78213c4c76bb5', 'pilates', 7, NULL, '3 Session', 'membership · group', 3, 0, 1, 21, 289000, 'b', '+6285945100296', NULL, 'TikTok', NULL, NULL, 'transfer', 'pending', 'pending_payment', NULL, '2026-10-12 21:01:46', '2026-10-11 21:01:46', NULL, NULL, NULL),
(5, 'a91793170ae2731dbce3b9da7f6d1ffc091a4cf5', 'pilates', 7, NULL, '3 Session', 'membership · group', 3, 0, 1, 21, 289000, 'Bagas Meriko', '+6285945100296', NULL, 'Lainnya', 'brosur', NULL, 'transfer', 'paid', 'cancelled', 'receipt-ca8f141d58358dff5b8ce38c7d11d1eca10e93da.pdf', '2026-10-13 03:29:50', '2026-10-12 03:29:50', '2026-10-12 03:32:48', 1, '2026-10-12 03:32:48'),
(6, 'f5d98ebd1533f378eba2bb8fc015c7f3d45eb6dd', 'pilates', 1, 'PILATES-1010', '10.10 Pilates Deal', 'session · group', 2, 0, 1, NULL, 190000, 'b', '089684634430', NULL, 'TikTok', NULL, NULL, 'transfer', 'rejected', 'cancelled', 'receipt-e652f7aea6050e10ef9d853744dbecd67851a774.pdf', '2026-10-13 16:34:28', '2026-10-12 16:34:28', NULL, 1, '2026-10-12 18:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `studio_booking_sessions`
--

CREATE TABLE `studio_booking_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `duration_minutes` smallint(5) UNSIGNED NOT NULL DEFAULT '60',
  `coach_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attendees` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `is_exclusive` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `studio_booking_sessions`
--

INSERT INTO `studio_booking_sessions` (`id`, `booking_id`, `schedule_id`, `session_date`, `start_time`, `duration_minutes`, `coach_name`, `class_type`, `attendees`, `is_exclusive`) VALUES
(2, 2, 1, '2026-10-12', '07:00:00', 420, 'Coach Zul', 'reformer', 1, 0),
(3, 2, 8, '2026-10-12', '15:00:00', 60, 'Coach Dinda', 'mat', 1, 0),
(4, 2, 9, '2026-10-12', '16:00:00', 240, 'Coach Dinda', 'reformer', 1, 0),
(5, 3, 1, '2026-10-12', '07:00:00', 420, 'Coach Zul', 'reformer', 1, 0),
(6, 3, 8, '2026-10-12', '15:00:00', 60, 'Coach Dinda', 'mat', 1, 0),
(7, 3, 9, '2026-10-12', '16:00:00', 240, 'Coach Dinda', 'reformer', 1, 0),
(8, 4, 13, '2026-10-13', '07:00:00', 480, 'Coach Diza', 'reformer', 1, 0),
(9, 4, 21, '2026-10-13', '16:00:00', 180, 'Coach Diza', 'reformer', 1, 0),
(10, 4, 24, '2026-10-13', '20:00:00', 60, 'Coach Diza', 'reformer', 1, 0),
(11, 5, 37, '2026-10-22', '07:00:00', 360, 'Coach Riefaldi', 'reformer', 1, 0),
(12, 5, 43, '2026-10-22', '14:00:00', 360, 'Coach Dinda', 'reformer', 1, 0),
(13, 5, 37, '2026-11-12', '07:00:00', 360, 'Coach Riefaldi', 'reformer', 1, 0),
(14, 6, 13, '2026-10-13', '07:00:00', 480, 'Coach Diza', 'reformer', 1, 0),
(15, 6, 58, '2026-10-30', '17:00:00', 180, 'Coach Diza', 'reformer', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `studio_gallery`
--

CREATE TABLE `studio_gallery` (
  `id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `studio_gallery`
--

INSERT INTO `studio_gallery` (`id`, `image_path`, `alt_text`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'gallery-uploads/bca93aac9a7293f8d94813cc099dbd42.jpeg', 'Sesi Pilates reformer di BloomÃ¨ Studio', 1, 1, '2026-10-12 10:33:27', '2026-10-12 10:44:59'),
(2, 'gallery-studio-02.jpg', 'Ruang kelas yoga BloomÃ¨ Studio', 2, 1, '2026-10-12 10:33:27', '2026-10-12 10:33:27'),
(3, 'gallery-studio-03.jpg', 'Area lounge BloomÃ¨ Studio', 3, 1, '2026-10-12 10:33:27', '2026-10-12 10:33:27'),
(4, 'gallery-studio-04.jpg', 'Area perawatan beauty BloomÃ¨ Studio', 4, 1, '2026-10-12 10:33:27', '2026-10-12 10:33:27'),
(5, 'gallery-uploads/f3633079068b3e4da4f1380e950043ea.jpg', 'Sesi Pilates reformer di BloomÃ¨ Studio', 5, 1, '2026-10-12 10:45:26', '2026-10-12 10:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `studio_reviews`
--

CREATE TABLE `studio_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_text` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT '5',
  `source` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Google Review',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `studio_reviews`
--

INSERT INTO `studio_reviews` (`id`, `name`, `review_text`, `rating`, `source`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Aulia Askha', 'studionya comfortable, wangi dan nyaman untuk work out & treatment. Kak Dinda, best coach! super duper ramah banget. highly recommended', 5, 'Google Review', 1, 1, '2026-10-12 10:33:27', '2026-10-12 10:51:53'),
(2, 'Cut Alia Masturina', 'Warga cinere must try, lokasi nya ada di ruko blkg cibel. Oke dan affordable, ada meni pedi nya juga', 5, 'Google Review', 2, 1, '2026-10-12 10:33:27', '2026-10-12 10:33:27'),
(3, 'Rae Taylor', 'Studio baru di cinere yang estetik banget, lengkap ada yoga ada pilates, best banget â¤ï¸', 5, 'Google Review', 3, 1, '2026-10-12 10:33:27', '2026-10-12 10:33:27');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `trx_no` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trx_datetime` datetime NOT NULL COMMENT 'Waktu check-in / transaksi (WIB)',
  `member_id` int(10) UNSIGNED NOT NULL,
  `trx_type` enum('sale','redeem') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sale',
  `status` enum('paid','void') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `item_type` enum('yoga','pilates','beauty','promo','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `yoga_pricing_id` int(10) UNSIGNED DEFAULT NULL,
  `pilates_pricing_id` int(10) UNSIGNED DEFAULT NULL,
  `beauty_service_id` int(10) UNSIGNED DEFAULT NULL,
  `promo_id` int(10) UNSIGNED DEFAULT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Snapshot nama paket/layanan',
  `qty` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `unit_price` int(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Snapshot harga paket/sesi (Rp)',
  `amount` int(10) UNSIGNED AS (IF(`trx_type` = 'sale', `unit_price` * `qty`, 0)) VIRTUAL,
  `sessions_total` smallint(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Jumlah sesi pada paket yang dibeli (termasuk bonus); 0 untuk redeem',
  `sessions_remaining` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Sisa sesi paket setelah check-in ini',
  `coach_id` smallint(5) UNSIGNED DEFAULT NULL,
  `complaint_injury` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('cash','transfer','qris','debit','credit','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `password_salt` char(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA2(CONCAT(salt, password), 256)',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `role_id`, `password_salt`, `password_hash`, `must_change_password`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin@bloomestudio.id', 'Admin', 1, '1c6be7fa78fcfb1b', 'c130ed2b6778f8046518ea9b0c96613b8ff6acdde9577cd8c52fff1f0951a412', 0, 1, '2026-10-12 18:51:05', '2026-10-10 17:37:54', '2026-10-12 11:51:05'),
(2, 'diza', 'Diza', 1, '404b0cc9e9fa61e9', 'c2c9b4cd0fe247ecbb95cf5499fee41b80e434ee9c196f1bf0a322a2b037a13d', 1, 1, NULL, '2026-10-10 17:37:54', '2026-10-11 12:52:21'),
(3, 'sarah', 'Sarah', 1, '7b4488693d992398', 'e48ede6514b0d0e0f2863062db3bfe13ee06c34419df1ed2fc446d7d2f87c66b', 1, 1, NULL, '2026-10-10 17:37:54', '2026-10-11 12:52:21'),
(4, 'finance', 'Finance', 2, '6f53e93b8156f775', '8be25259348d628f9e6ada0834e5680a3b9a8363001b6e12dbcdf06d616d5d33', 1, 1, NULL, '2026-10-10 17:37:54', '2026-10-11 12:52:21');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_transaction_detail`
-- (See below for the actual view)
--
CREATE TABLE `v_transaction_detail` (
`id` bigint(20) unsigned
,`trx_no` varchar(30)
,`trx_datetime` datetime
,`trx_date` date
,`status` enum('paid','void')
,`trx_type` enum('sale','redeem')
,`item_type` enum('yoga','pilates','beauty','promo','other')
,`item_name` varchar(150)
,`qty` smallint(5) unsigned
,`unit_price` int(10) unsigned
,`amount` int(10) unsigned
,`sessions_total` smallint(5) unsigned
,`sessions_remaining` smallint(5) unsigned
,`complaint_injury` varchar(255)
,`payment_method` enum('cash','transfer','qris','debit','credit','other')
,`member_id` int(10) unsigned
,`member_code` varchar(20)
,`member_name` varchar(100)
,`member_phone` varchar(20)
,`coach_id` smallint(5) unsigned
,`coach_name` varchar(60)
);

-- --------------------------------------------------------

--
-- Table structure for table `v_user_permissions`
--

CREATE TABLE `v_user_permissions` (
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_name` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permission_code` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `yoga_pricing`
--

CREATE TABLE `yoga_pricing` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_count` smallint(5) UNSIGNED NOT NULL,
  `bonus_sessions` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `validity_days` smallint(5) UNSIGNED DEFAULT NULL,
  `validity_label` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int(10) UNSIGNED NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `yoga_pricing`
--

INSERT INTO `yoga_pricing` (`id`, `name`, `session_count`, `bonus_sessions`, `validity_days`, `validity_label`, `price`, `notes`, `sort_order`, `is_active`) VALUES
(1, 'First Visit', 1, 0, NULL, 'Valid for 1 ID', 89000, NULL, 1, 1),
(2, 'Single Visit', 1, 0, 7, '1 week', 99000, NULL, 2, 1),
(3, '3 Session', 3, 0, 21, '3 weeks', 260000, NULL, 3, 1),
(4, '5 Session', 5, 0, 30, '1 month', 415000, NULL, 4, 1),
(5, '10 + 1 Session', 10, 1, 45, '45 days', 800000, NULL, 5, 1);

-- --------------------------------------------------------

--
-- Structure for view `v_transaction_detail`
--
DROP TABLE IF EXISTS `v_transaction_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_transaction_detail`  AS  select `t`.`id` AS `id`,`t`.`trx_no` AS `trx_no`,`t`.`trx_datetime` AS `trx_datetime`,cast(`t`.`trx_datetime` as date) AS `trx_date`,`t`.`status` AS `status`,`t`.`trx_type` AS `trx_type`,`t`.`item_type` AS `item_type`,`t`.`item_name` AS `item_name`,`t`.`qty` AS `qty`,`t`.`unit_price` AS `unit_price`,`t`.`amount` AS `amount`,`t`.`sessions_total` AS `sessions_total`,`t`.`sessions_remaining` AS `sessions_remaining`,`t`.`complaint_injury` AS `complaint_injury`,`t`.`payment_method` AS `payment_method`,`t`.`member_id` AS `member_id`,`m`.`member_code` AS `member_code`,`m`.`full_name` AS `member_name`,`m`.`phone` AS `member_phone`,`t`.`coach_id` AS `coach_id`,`c`.`name` AS `coach_name` from ((`transactions` `t` join `members` `m` on((`m`.`id` = `t`.`member_id`))) left join `coaches` `c` on((`c`.`id` = `t`.`coach_id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `beauty_categories`
--
ALTER TABLE `beauty_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_beauty_cat_code` (`code`);

--
-- Indexes for table `beauty_services`
--
ALTER TABLE `beauty_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_beauty_services_cat` (`category_id`);

--
-- Indexes for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schedule_slot` (`discipline`,`day_of_week`,`start_time`),
  ADD KEY `idx_schedule_coach` (`coach_id`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coaches_name` (`name`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_members_code` (`member_code`),
  ADD KEY `idx_members_name` (`full_name`),
  ADD KEY `idx_members_phone` (`phone`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_permissions_code` (`code`);

--
-- Indexes for table `pilates_pricing`
--
ALTER TABLE `pilates_pricing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pilates_pricing` (`kind`,`class_type`,`name`);

--
-- Indexes for table `promos`
--
ALTER TABLE `promos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_promos_code` (`code`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_rp_perm` (`permission_id`);

--
-- Indexes for table `studio_bookings`
--
ALTER TABLE `studio_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_studio_booking_token` (`public_token`),
  ADD KEY `idx_studio_booking_pending` (`booking_status`,`expires_at`),
  ADD KEY `idx_studio_booking_payment` (`payment_status`,`created_at`);

--
-- Indexes for table `studio_booking_sessions`
--
ALTER TABLE `studio_booking_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_booking_schedule_date` (`booking_id`,`schedule_id`,`session_date`),
  ADD KEY `idx_booked_schedule_date` (`schedule_id`,`session_date`,`start_time`);

--
-- Indexes for table `studio_gallery`
--
ALTER TABLE `studio_gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `studio_reviews`
--
ALTER TABLE `studio_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_transactions_no` (`trx_no`),
  ADD KEY `idx_trx_datetime` (`trx_datetime`),
  ADD KEY `idx_trx_member` (`member_id`),
  ADD KEY `idx_trx_coach` (`coach_id`),
  ADD KEY `idx_trx_item` (`item_type`),
  ADD KEY `fk_trx_yoga` (`yoga_pricing_id`),
  ADD KEY `fk_trx_pilates` (`pilates_pricing_id`),
  ADD KEY `fk_trx_beauty` (`beauty_service_id`),
  ADD KEY `fk_trx_promo` (`promo_id`),
  ADD KEY `fk_trx_user` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role_id`);

--
-- Indexes for table `yoga_pricing`
--
ALTER TABLE `yoga_pricing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_yoga_pricing_name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `beauty_categories`
--
ALTER TABLE `beauty_categories`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `beauty_services`
--
ALTER TABLE `beauty_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `class_schedules`
--
ALTER TABLE `class_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pilates_pricing`
--
ALTER TABLE `pilates_pricing`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `promos`
--
ALTER TABLE `promos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `studio_bookings`
--
ALTER TABLE `studio_bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `studio_booking_sessions`
--
ALTER TABLE `studio_booking_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `studio_gallery`
--
ALTER TABLE `studio_gallery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `studio_reviews`
--
ALTER TABLE `studio_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `yoga_pricing`
--
ALTER TABLE `yoga_pricing`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `beauty_services`
--
ALTER TABLE `beauty_services`
  ADD CONSTRAINT `fk_beauty_services_cat` FOREIGN KEY (`category_id`) REFERENCES `beauty_categories` (`id`);

--
-- Constraints for table `class_schedules`
--
ALTER TABLE `class_schedules`
  ADD CONSTRAINT `fk_schedule_coach` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `studio_booking_sessions`
--
ALTER TABLE `studio_booking_sessions`
  ADD CONSTRAINT `fk_booking_session_booking` FOREIGN KEY (`booking_id`) REFERENCES `studio_bookings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_session_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_trx_beauty` FOREIGN KEY (`beauty_service_id`) REFERENCES `beauty_services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_coach` FOREIGN KEY (`coach_id`) REFERENCES `coaches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_trx_pilates` FOREIGN KEY (`pilates_pricing_id`) REFERENCES `pilates_pricing` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_promo` FOREIGN KEY (`promo_id`) REFERENCES `promos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_yoga` FOREIGN KEY (`yoga_pricing_id`) REFERENCES `yoga_pricing` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
