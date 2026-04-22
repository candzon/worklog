-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for u9621710_worklog
CREATE DATABASE IF NOT EXISTS `u9621710_worklog` /*!40100 DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `u9621710_worklog`;

-- Dumping structure for table u9621710_worklog.bagian
CREATE TABLE IF NOT EXISTS `bagian` (
  `id_bagian` int NOT NULL AUTO_INCREMENT,
  `nama_bagian` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id_bagian`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table u9621710_worklog.bagian: ~17 rows (approximately)
REPLACE INTO `bagian` (`id_bagian`, `nama_bagian`, `created_at`) VALUES
	(1, 'Finance AR', '2026-01-29 00:00:00'),
	(3, 'Warehouse', '2024-03-18 00:00:00'),
	(4, 'GA ', '2024-03-18 00:00:00'),
	(5, 'Sales', '2024-03-18 00:00:00'),
	(10, 'Direktur Utama', '2024-04-29 11:14:13'),
	(11, 'IT', '2024-06-05 00:00:00'),
	(12, 'Apoteker', '2024-06-07 00:00:00'),
	(13, 'HR', '2024-09-17 00:00:00'),
	(14, 'HRGA', '2025-02-01 00:00:00'),
	(15, 'Tax', '2025-02-01 00:00:00'),
	(16, 'Finance, Accounting & Tax ', '2025-02-01 00:00:00'),
	(17, 'Accounting', '2025-02-01 00:00:00'),
	(18, 'Kurir', '2025-02-01 00:00:00'),
	(19, 'Business Development & Ops', '2025-02-01 00:00:00'),
	(20, 'Direktur', '2025-02-18 00:00:00'),
	(21, 'Business', '2026-01-29 00:00:00'),
	(22, 'Finance AP', '2026-01-29 00:00:00'),
	(24, 'TTK', '2026-04-18 10:21:25');

-- Dumping structure for table u9621710_worklog.employee
CREATE TABLE IF NOT EXISTS `employee` (
  `npp` varchar(20) NOT NULL,
  `nama_emp` varchar(100) NOT NULL,
  `jenis_kelamin` char(2) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT '0 = Laki-Laki, 1 = Perempuan',
  `telp` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `nama_bagian` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `alamat` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `password` varchar(100) NOT NULL,
  `role_id` int DEFAULT NULL,
  PRIMARY KEY (`npp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table u9621710_worklog.employee: ~5 rows (approximately)
REPLACE INTO `employee` (`npp`, `nama_emp`, `jenis_kelamin`, `telp`, `nama_bagian`, `alamat`, `password`, `role_id`) VALUES
	('20910012', 'Yudha Amandangi Syahputera', '0', '895635328933', '12', 'Jl. Cucur Timur VI Blok A9 No. 23, Kel. Pondok Karya, Kec Pondok Aren, Tangerang Selatan\r\n', '20910012', 2),
	('21970019', 'Nur Meinanda Handi Resmana', '1', '85692350910', '12', 'Jl. Anggrek Kp. Bulak RT. 004 RW. 003 Kel. Pondok Kacang Timur Kec. Pondok Aren Banten , Kota Tangerang Selatan\r\n', '21970019', 3),
	('23920040', 'Isnainul Fajri', '0', '081285957873', '12', 'Kp. Cipedak RT.006/009, Srengseng Sawah, Jagakarsa', '23920040', 3),
	('249800102', 'Pande Gede Raditya Wira Perdana', '0', '0895366567055', '12', 'Jl. Taman giri perumahan griya nugraha C14 No.284, kel. Benoa. Kec. Kuta selatan, Badung 80361', '249800102', 3),
	('24990080', 'Hamal Rizki', '0', '082360350789', '12', 'Jl. Rahmadsyah GG Kembar No. 425-B RT. 021/000 Kel. Kota Matsumi, Kec. Medan Area', '24990080', 3);

-- Dumping structure for table u9621710_worklog.master_cabang
CREATE TABLE IF NOT EXISTS `master_cabang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Dumping data for table u9621710_worklog.master_cabang: ~6 rows (approximately)
REPLACE INTO `master_cabang` (`id`, `nama_cabang`) VALUES
	(1, 'Puri'),
	(2, 'Surabaya'),
	(3, 'Cibinong'),
	(4, 'Medan'),
	(5, 'Bekasi'),
	(6, 'Bali');

-- Dumping structure for table u9621710_worklog.master_tugas
CREATE TABLE IF NOT EXISTS `master_tugas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb3_unicode_ci,
  `npp` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `bagian_id` int DEFAULT NULL,
  `periode` enum('bulanan','mingguan','harian') COLLATE utf8mb3_unicode_ci DEFAULT 'bulanan',
  `target_tgl` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `npp_manager` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bagian_id` (`bagian_id`),
  KEY `npp` (`npp`),
  CONSTRAINT `master_tugas_ibfk_1` FOREIGN KEY (`bagian_id`) REFERENCES `bagian` (`id_bagian`),
  CONSTRAINT `master_tugas_ibfk_2` FOREIGN KEY (`npp`) REFERENCES `employee` (`npp`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- Dumping data for table u9621710_worklog.master_tugas: ~1 rows (approximately)
REPLACE INTO `master_tugas` (`id`, `judul`, `deskripsi`, `npp`, `bagian_id`, `periode`, `target_tgl`, `created_at`, `npp_manager`) VALUES
	(37, 'asdsa', 'sadads', NULL, 12, 'bulanan', '2026-04-30', '2026-04-18 11:40:16', '20910012');

-- Dumping structure for table u9621710_worklog.master_tugas_detail
CREATE TABLE IF NOT EXISTS `master_tugas_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `master_tugas_id` int NOT NULL,
  `npp` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_master_tugas` (`master_tugas_id`),
  KEY `fk_employee_npp` (`npp`),
  CONSTRAINT `fk_employee_npp` FOREIGN KEY (`npp`) REFERENCES `employee` (`npp`) ON DELETE CASCADE,
  CONSTRAINT `fk_master_tugas` FOREIGN KEY (`master_tugas_id`) REFERENCES `master_tugas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table u9621710_worklog.master_tugas_detail: ~5 rows (approximately)
REPLACE INTO `master_tugas_detail` (`id`, `master_tugas_id`, `npp`, `created_at`) VALUES
	(1, 37, '20910012', '2026-04-18 04:40:16'),
	(2, 37, '21970019', '2026-04-18 04:40:16'),
	(3, 37, '23920040', '2026-04-18 04:40:16'),
	(4, 37, '249800102', '2026-04-18 04:40:16'),
	(5, 37, '24990080', '2026-04-18 04:40:16');

-- Dumping structure for table u9621710_worklog.pekerjaan
CREATE TABLE IF NOT EXISTS `pekerjaan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `created_by_npp` varchar(20) NOT NULL,
  `nama_emp` varchar(150) DEFAULT NULL,
  `assigned_to_npp` varchar(20) DEFAULT NULL,
  `bagian_penerima` varchar(50) DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'open',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `master_tugas_id` int DEFAULT NULL,
  `periode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_tugas_id` (`master_tugas_id`),
  CONSTRAINT `pekerjaan_ibfk_1` FOREIGN KEY (`master_tugas_id`) REFERENCES `master_tugas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table u9621710_worklog.pekerjaan: ~0 rows (approximately)

-- Dumping structure for table u9621710_worklog.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table u9621710_worklog.roles: ~3 rows (approximately)
REPLACE INTO `roles` (`id`, `name`, `label`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'admin', 'Administrator', NULL, '2026-03-13 13:18:12', NULL),
	(2, 'manager', 'Manager', NULL, '2026-03-13 13:18:12', NULL),
	(3, 'user', 'User', NULL, '2026-03-13 13:18:12', NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
