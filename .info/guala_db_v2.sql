-- Guala App V2 Database Schema
-- Status: Draft / Standardized
-- Date: 2026-01-26

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

-- --------------------------------------------------------

-- --------------------------------------------------------

--
-- Table structure for table `machines` (formerly `machine_center`)
-- SYNC TABLE - Do not modify manually in production
--

DROP TABLE IF EXISTS `machines`;
CREATE TABLE `machines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) DEFAULT NULL, -- was no
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL, -- was GUAMachineCenterType
  `company` varchar(255) DEFAULT NULL, -- was Company
  `position` varchar(255) DEFAULT NULL, -- was GUAPosition
  `schedule_code` varchar(255) DEFAULT NULL, -- was GUA_schedule
  `piovan_id` varchar(255) DEFAULT NULL, -- was id_piovan (Synced?)
  PRIMARY KEY (`id`),
  UNIQUE KEY `machines_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `machine_metadata` (formerly `tabella_appoggio_macchine`)
-- Local overrides and additional data
--

DROP TABLE IF EXISTS `machine_metadata`;
CREATE TABLE `machine_metadata` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `machine_code` varchar(255) NOT NULL, -- FK to machines.code
  `company_id` bigint(20) unsigned DEFAULT NULL, -- was azienda
  `mes_id` varchar(255) DEFAULT NULL, -- was id_mes
  `piovan_id` varchar(255) DEFAULT NULL, -- was id_piovan (Local override)
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `machine_metadata_machine_code_index` (`machine_code`),
  CONSTRAINT `machine_metadata_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shifts` (formerly `turni`)
--

DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL, -- was nome_turno
  `start_minutes` int(11) NOT NULL, -- was inizio
  `end_minutes` int(11) NOT NULL, -- was fine
  `company_id` bigint(20) unsigned NOT NULL, -- was azienda
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shifts_company_id_foreign` (`company_id`),
  CONSTRAINT `shifts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_assignments` (formerly `gestione_turni`)
--

DROP TABLE IF EXISTS `shift_assignments`;
CREATE TABLE `shift_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_leader_id` bigint(20) unsigned NOT NULL, -- was id_capoturno
  `shift_id` bigint(20) unsigned NOT NULL, -- was id_turno
  `shift_date` date NOT NULL, -- was data_turno
  `operator_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operator_ids`)), -- was id_operatori
  `machine_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`machine_ids`)), -- was id_macchinari_associati
  `note` text DEFAULT NULL, -- was nota
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shift_assignments_shift_leader_id_foreign` (`shift_leader_id`),
  KEY `shift_assignments_shift_id_foreign` (`shift_id`),
  CONSTRAINT `shift_assignments_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shift_assignments_shift_leader_id_foreign` FOREIGN KEY (`shift_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `press_shift_assignments` (formerly `gestione_turni_presse`)
--

DROP TABLE IF EXISTS `press_shift_assignments`;
CREATE TABLE `press_shift_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shift_leader_id` bigint(20) unsigned NOT NULL,
  `shift_id` bigint(20) unsigned NOT NULL,
  `shift_date` date NOT NULL,
  `operator_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`operator_ids`)),
  `machine_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`machine_ids`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `press_shift_assignments_leader_foreign` (`shift_leader_id`),
  KEY `press_shift_assignments_shift_foreign` (`shift_id`),
  CONSTRAINT `press_shift_assignments_leader_foreign` FOREIGN KEY (`shift_leader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `press_shift_assignments_shift_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Standard Laravel Tables
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
