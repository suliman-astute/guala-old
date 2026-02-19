-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: guala_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `active_app_user`
--

DROP TABLE IF EXISTS `active_app_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_app_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `active_app_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `active_app_user_user_id_foreign` (`user_id`),
  KEY `active_app_user_active_app_id_foreign` (`active_app_id`),
  CONSTRAINT `active_app_user_active_app_id_foreign` FOREIGN KEY (`active_app_id`) REFERENCES `active_apps` (`id`) ON DELETE CASCADE,
  CONSTRAINT `active_app_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `active_apps`
--

DROP TABLE IF EXISTS `active_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_apps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned DEFAULT NULL,
  `azienda` int(5) unsigned NOT NULL,
  `icon` char(255) DEFAULT NULL,
  `name_it` varchar(255) NOT NULL,
  `name_en` varchar(255) DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `active_apps_site_id_foreign` (`site_id`),
  CONSTRAINT `active_apps_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=1030 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `assemblaggio_view`
--

DROP TABLE IF EXISTS `assemblaggio_view`;
/*!50001 DROP VIEW IF EXISTS `assemblaggio_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `assemblaggio_view` AS SELECT
 1 AS `id`,
  1 AS `mesOrderNo`,
  1 AS `mesStatus`,
  1 AS `itemNo`,
  1 AS `itemDescription`,
  1 AS `machineSatmp`,
  1 AS `machinePress`,
  1 AS `machinePressDesc`,
  1 AS `guaCustomerNO`,
  1 AS `guaCustomName`,
  1 AS `guaCustomerOrder`,
  1 AS `quantity`,
  1 AS `relSequence`,
  1 AS `quantita_prodotta`,
  1 AS `family`,
  1 AS `commento`,
  1 AS `nome_completo_macchina` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `aziende`
--

DROP TABLE IF EXISTS `aziende`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aziende` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bisio_progetti_stain`
--

DROP TABLE IF EXISTS `bisio_progetti_stain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bisio_progetti_stain` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `DescrMacchinaEstesa` varchar(255) NOT NULL,
  `StatoOperazione` varchar(255) NOT NULL,
  `nrordinesap` varchar(255) NOT NULL,
  `codarticolo` varchar(255) NOT NULL,
  `DescrizioneArticolo` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bom_explosion`
--

DROP TABLE IF EXISTS `bom_explosion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_explosion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `xLevel` int(11) NOT NULL,
  `productionBOMNo` varchar(255) NOT NULL,
  `BOMReplSystem` varchar(255) NOT NULL,
  `BOMInvPostGr` varchar(255) NOT NULL,
  `No` varchar(255) NOT NULL,
  `ReplSystem` varchar(255) NOT NULL,
  `InvPostGr` varchar(255) NOT NULL,
  `UoM` varchar(255) NOT NULL,
  `QtyPer` double NOT NULL,
  `PercScarti` double NOT NULL,
  `PathString` varchar(255) NOT NULL,
  `PathLength` int(11) NOT NULL,
  `StartingDate` date DEFAULT NULL,
  `Company` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `codici_oggetti`
--

DROP TABLE IF EXISTS `codici_oggetti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codici_oggetti` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codici` varchar(255) NOT NULL,
  `oggetto` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commento_lavori_guala_fp`
--

DROP TABLE IF EXISTS `commento_lavori_guala_fp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commento_lavori_guala_fp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_riga` varchar(255) NOT NULL,
  `testo` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=753 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commento_lavori_guala_fp_t2`
--

DROP TABLE IF EXISTS `commento_lavori_guala_fp_t2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commento_lavori_guala_fp_t2` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_riga` int(11) NOT NULL,
  `testo` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dictionary_table`
--

DROP TABLE IF EXISTS `dictionary_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dictionary_table` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `IT` varchar(255) DEFAULT NULL,
  `EN` varchar(255) DEFAULT NULL,
  `column_name` varchar(255) DEFAULT NULL,
  `table_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enpoint_piovan`
--

DROP TABLE IF EXISTS `enpoint_piovan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enpoint_piovan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `endpoint` text DEFAULT NULL,
  `chiamata_soap` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `azienda` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ext_infos`
--

DROP TABLE IF EXISTS `ext_infos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ext_infos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `val1` char(255) DEFAULT NULL,
  `val2` char(255) DEFAULT NULL,
  `val3` char(255) DEFAULT NULL,
  `stampe` char(255) DEFAULT NULL,
  `seq` char(255) DEFAULT NULL,
  `code` char(255) DEFAULT NULL,
  `n_order` char(255) DEFAULT NULL,
  `n_order_erp` char(255) DEFAULT NULL,
  `qta_ric` int(11) DEFAULT NULL,
  `qta_prod` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ext_infos_stampe_index` (`stampe`),
  KEY `ext_infos_seq_index` (`seq`),
  KEY `ext_infos_code_index` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gestione_turni`
--

DROP TABLE IF EXISTS `gestione_turni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestione_turni` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_capoturno` bigint(20) unsigned NOT NULL,
  `id_turno` bigint(20) unsigned NOT NULL,
  `id_operatori` text DEFAULT NULL,
  `id_macchinari_associati` text DEFAULT NULL,
  `data_turno` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nota` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gestione_turni_presse`
--

DROP TABLE IF EXISTS `gestione_turni_presse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gestione_turni_presse` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_capoturno` bigint(20) unsigned NOT NULL,
  `id_turno` bigint(20) unsigned NOT NULL,
  `id_operatori` text NOT NULL,
  `id_macchinari_associati` text NOT NULL,
  `data_turno` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `macchine`
--

DROP TABLE IF EXISTS `macchine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `macchine` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `machine_center`
--

DROP TABLE IF EXISTS `machine_center`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `machine_center` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `GUAPosition` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `no` varchar(255) DEFAULT NULL,
  `id_piovan` varchar(255) DEFAULT NULL,
  `azienda` enum('','Guala Dispensing','Bisio','Messico','Romania') NOT NULL DEFAULT '',
  `GUAMachineCenterType` varchar(255) DEFAULT NULL,
  `Company` varchar(255) DEFAULT NULL,
  `GUA_schedule` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=681 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `machine_center_tmp`
--

DROP TABLE IF EXISTS `machine_center_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `machine_center_tmp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `GUAPosition` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `no` varchar(255) DEFAULT NULL,
  `id_piovan` varchar(255) DEFAULT NULL,
  `azienda` enum('','Guala Dispensing','Bisio','Messico','Romania') NOT NULL DEFAULT '',
  `GUAMachineCenterType` varchar(255) DEFAULT NULL,
  `Company` varchar(255) DEFAULT NULL,
  `GUA_schedule` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_macchine_operatori`
--

DROP TABLE IF EXISTS `note_macchine_operatori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_macchine_operatori` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_macchina` bigint(20) unsigned NOT NULL,
  `id_operatore` bigint(20) unsigned NOT NULL,
  `data` date NOT NULL,
  `nota` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_macc_op_data` (`id_macchina`,`id_operatore`,`data`),
  KEY `note_macchine_operatori_id_operatore_foreign` (`id_operatore`),
  CONSTRAINT `note_macchine_operatori_id_macchina_foreign` FOREIGN KEY (`id_macchina`) REFERENCES `machine_center` (`id`) ON DELETE CASCADE,
  CONSTRAINT `note_macchine_operatori_id_operatore_foreign` FOREIGN KEY (`id_operatore`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orderfrommes`
--

DROP TABLE IF EXISTS `orderfrommes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orderfrommes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ordernane` varchar(255) NOT NULL,
  `messtatus` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17872 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ordine_note`
--

DROP TABLE IF EXISTS `ordine_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordine_note` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ordine` varchar(100) NOT NULL,
  `lotto` varchar(100) NOT NULL,
  `nota` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ordine_note_ordine_lotto_unique` (`ordine`,`lotto`),
  KEY `ordine_note_ordine_index` (`ordine`),
  KEY `ordine_note_lotto_index` (`lotto`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ordini_lavoro_lotti`
--

DROP TABLE IF EXISTS `ordini_lavoro_lotti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ordini_lavoro_lotti` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `Ordine` varchar(255) NOT NULL,
  `Lotto` varchar(255) NOT NULL,
  `ArticoloCodice` varchar(255) NOT NULL,
  `ArticoloDescrizione` varchar(255) NOT NULL,
  `ClienteCodice` varchar(255) NOT NULL,
  `ClienteDescrizione` varchar(255) NOT NULL,
  `QtaPrevOrdin` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52432 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `presse`
--

DROP TABLE IF EXISTS `presse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presse` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `id_mes` varchar(255) DEFAULT NULL,
  `id_piovan` varchar(255) DEFAULT NULL,
  `azienda` enum('','Guala Dispensing','Bisio','Messico','Romania') NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qta_guala_pro_rom`
--

DROP TABLE IF EXISTS `qta_guala_pro_rom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qta_guala_pro_rom` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codice_udc` varchar(255) NOT NULL,
  `sku` varchar(255) NOT NULL,
  `Quantita` double NOT NULL,
  `Stato_udc` varchar(255) NOT NULL,
  `productype` varchar(255) DEFAULT NULL,
  `UM` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3023 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `stampaggio_fp`
--

DROP TABLE IF EXISTS `stampaggio_fp`;
/*!50001 DROP VIEW IF EXISTS `stampaggio_fp`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `stampaggio_fp` AS SELECT
 1 AS `id`,
  1 AS `mesOrderNo`,
  1 AS `mesStatus`,
  1 AS `startingdatetime`,
  1 AS `itemNo`,
  1 AS `itemDescription`,
  1 AS `machinePress`,
  1 AS `machinePressDesc`,
  1 AS `guaCustomerNO`,
  1 AS `guaCustomName`,
  1 AS `guaCustomerOrder`,
  1 AS `quantity`,
  1 AS `relSequence`,
  1 AS `quantita_prodotta`,
  1 AS `family`,
  1 AS `commento`,
  1 AS `machinePressFull`,
  1 AS `machineSatmp`,
  1 AS `routingReferenceNo`,
  1 AS `prodOrderNo`,
  1 AS `routingNo`,
  1 AS `operationNo`,
  1 AS `centerType`,
  1 AS `routingStatus`,
  1 AS `GUAPosition`,
  1 AS `GUA_schedule` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `stampaggio_view`
--

DROP TABLE IF EXISTS `stampaggio_view`;
/*!50001 DROP VIEW IF EXISTS `stampaggio_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `stampaggio_view` AS SELECT
 1 AS `id`,
  1 AS `mesOrderNo`,
  1 AS `mesStatus`,
  1 AS `itemNo`,
  1 AS `itemDescription`,
  1 AS `machineSatmp`,
  1 AS `machinePress`,
  1 AS `machinePressDesc`,
  1 AS `guaCustomerNO`,
  1 AS `guaCustomName`,
  1 AS `guaCustomerOrder`,
  1 AS `quantity`,
  1 AS `relSequence`,
  1 AS `quantita_prodotta`,
  1 AS `family`,
  1 AS `commento`,
  1 AS `GUAPosition`,
  1 AS `machinePressFull` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `tabella_appoggio_macchine`
--

DROP TABLE IF EXISTS `tabella_appoggio_macchine`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tabella_appoggio_macchine` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `no` varchar(255) NOT NULL,
  `id_piovan` varchar(255) DEFAULT NULL,
  `azienda` int(5) unsigned NOT NULL,
  `ingressi_usati` int(11) DEFAULT NULL,
  `id_mes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=543 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_commenti_guala_fp`
--

DROP TABLE IF EXISTS `table_commenti_guala_fp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_commenti_guala_fp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `TableName` varchar(50) NOT NULL,
  `No` varchar(255) NOT NULL,
  `LineNo` varchar(255) NOT NULL,
  `Date` date NOT NULL,
  `Code` varchar(255) DEFAULT NULL,
  `Comment` varchar(255) NOT NULL,
  `Company` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_gestione_ad`
--

DROP TABLE IF EXISTS `table_gestione_ad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_gestione_ad` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dominio` varchar(255) DEFAULT NULL,
  `host` varchar(255) DEFAULT NULL,
  `base_dn` varchar(255) DEFAULT NULL,
  `porta` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dominio` (`dominio`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_gua_items_in_producion`
--

DROP TABLE IF EXISTS `table_gua_items_in_producion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_gua_items_in_producion` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entryNo` int(11) NOT NULL,
  `componentNo` varchar(255) NOT NULL,
  `parentitemNo` varchar(255) NOT NULL,
  `compDescription` text NOT NULL,
  `levelCode` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `unitOfMeasure` varchar(255) NOT NULL,
  `prodorderno` varchar(255) NOT NULL,
  `mesOrderNo` varchar(255) NOT NULL,
  `commento` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2072 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_gua_mes_prod_orders`
--

DROP TABLE IF EXISTS `table_gua_mes_prod_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_gua_mes_prod_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `startingdatetime` datetime DEFAULT NULL,
  `mesOrderNo` varchar(255) NOT NULL,
  `mesStatus` varchar(255) NOT NULL,
  `itemNo` varchar(255) NOT NULL,
  `itemDescription` text NOT NULL,
  `machineSatmp` varchar(255) NOT NULL,
  `machinePress` varchar(255) NOT NULL,
  `machinePressDesc` varchar(255) NOT NULL,
  `guaCustomerNo` varchar(255) NOT NULL,
  `guaCustomName` varchar(255) NOT NULL,
  `guaCustomerOrder` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `relSequence` int(11) NOT NULL,
  `quantita_prodotta` varchar(255) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `commento` varchar(255) DEFAULT NULL,
  `no` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_guaprodrouting`
--

DROP TABLE IF EXISTS `table_guaprodrouting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_guaprodrouting` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) DEFAULT NULL,
  `prodOrderNo` varchar(255) DEFAULT NULL,
  `routingReferenceNo` varchar(255) DEFAULT NULL,
  `routingNo` varchar(255) DEFAULT NULL,
  `operationNo` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `no` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `TotaleQtaProdottaBuoni` int(10) unsigned NOT NULL DEFAULT 0,
  `StatoOperazione` int(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=440 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_piovan_import`
--

DROP TABLE IF EXISTS `table_piovan_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `table_piovan_import` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_mes` varchar(255) DEFAULT NULL,
  `material` varchar(255) DEFAULT NULL,
  `lotto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `turni`
--

DROP TABLE IF EXISTS `turni`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turni` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome_turno` varchar(255) NOT NULL,
  `inizio` int(11) NOT NULL,
  `fine` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `azienda` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint(20) unsigned DEFAULT NULL,
  `lang` varchar(2) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `admin` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `cognome` varchar(255) DEFAULT NULL,
  `matricola` varchar(255) DEFAULT NULL,
  `valido` int(11) NOT NULL DEFAULT 1,
  `is_ad_user` int(5) NOT NULL,
  `tipo_dominio` varchar(255) DEFAULT '0',
  `destinazione_utenti` int(5) unsigned NOT NULL,
  `ruolo_personale` enum('','Operatore Assemblaggio','Capo turno Assemblaggio','Operatore Stampaggio','Capo turno Stampaggio') NOT NULL,
  `stato` enum('','attivo','inattivo','sospeso') NOT NULL DEFAULT '',
  `superadmin` varchar(255) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `users_site_id_foreign` (`site_id`),
  CONSTRAINT `users_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Final view structure for view `assemblaggio_view`
--

/*!50001 DROP VIEW IF EXISTS `assemblaggio_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`guala_usr`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `assemblaggio_view` AS select distinct `o`.`id` AS `id`,`o`.`mesOrderNo` AS `mesOrderNo`,`o`.`mesStatus` AS `mesStatus`,`o`.`itemNo` AS `itemNo`,`o`.`itemDescription` AS `itemDescription`,`o`.`machineSatmp` AS `machineSatmp`,`o`.`machinePress` AS `machinePress`,`o`.`machinePressDesc` AS `machinePressDesc`,`o`.`guaCustomerNo` AS `guaCustomerNO`,`o`.`guaCustomName` AS `guaCustomName`,`o`.`guaCustomerOrder` AS `guaCustomerOrder`,`o`.`quantity` AS `quantity`,`o`.`relSequence` AS `relSequence`,`o`.`quantita_prodotta` AS `quantita_prodotta`,`o`.`family` AS `family`,`o`.`commento` AS `commento`,concat(`o`.`machineSatmp`,' - ',`m`.`name`) AS `nome_completo_macchina` from (`table_gua_mes_prod_orders` `o` join `machine_center` `m` on(`o`.`machineSatmp` = `m`.`GUAPosition`)) where `o`.`mesOrderNo` like '%AS%' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `stampaggio_fp`
--

/*!50001 DROP VIEW IF EXISTS `stampaggio_fp`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`guala_usr`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `stampaggio_fp` AS select `mpo`.`id` AS `id`,`mpo`.`mesOrderNo` AS `mesOrderNo`,`mpo`.`mesStatus` AS `mesStatus`,`mpo`.`startingdatetime` AS `startingdatetime`,`mpo`.`itemNo` AS `itemNo`,`mpo`.`itemDescription` AS `itemDescription`,`mpo`.`machinePress` AS `machinePress`,`mpo`.`machinePressDesc` AS `machinePressDesc`,`mpo`.`guaCustomerNo` AS `guaCustomerNO`,`mpo`.`guaCustomName` AS `guaCustomName`,`mpo`.`guaCustomerOrder` AS `guaCustomerOrder`,`mpo`.`quantity` AS `quantity`,`mpo`.`relSequence` AS `relSequence`,`mpo`.`quantita_prodotta` AS `quantita_prodotta`,`mpo`.`family` AS `family`,`mpo`.`commento` AS `commento`,concat(`mpo`.`machinePress`,' ',`mpo`.`machinePressDesc`) AS `machinePressFull`,`gpr`.`no` AS `machineSatmp`,`gpr`.`routingReferenceNo` AS `routingReferenceNo`,`gpr`.`prodOrderNo` AS `prodOrderNo`,`gpr`.`routingNo` AS `routingNo`,`gpr`.`operationNo` AS `operationNo`,`gpr`.`type` AS `centerType`,`gpr`.`status` AS `routingStatus`,`mc`.`GUAPosition` AS `GUAPosition`,`mc`.`GUA_schedule` AS `GUA_schedule` from ((`table_gua_mes_prod_orders` `mpo` left join `table_guaprodrouting` `gpr` on(`gpr`.`prodOrderNo` = `mpo`.`no`)) left join `machine_center` `mc` on(`mc`.`no` = `gpr`.`no` and `mc`.`Company` = 'Guala Dispensing FP')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `stampaggio_view`
--

/*!50001 DROP VIEW IF EXISTS `stampaggio_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`guala_usr`@`%` SQL SECURITY DEFINER */
/*!50001 VIEW `stampaggio_view` AS select distinct `mpo`.`id` AS `id`,`mpo`.`mesOrderNo` AS `mesOrderNo`,`mpo`.`mesStatus` AS `mesStatus`,`mpo`.`itemNo` AS `itemNo`,`mpo`.`itemDescription` AS `itemDescription`,`mpo`.`machineSatmp` AS `machineSatmp`,`mpo`.`machinePress` AS `machinePress`,`mpo`.`machinePressDesc` AS `machinePressDesc`,`mpo`.`guaCustomerNo` AS `guaCustomerNO`,`mpo`.`guaCustomName` AS `guaCustomName`,`mpo`.`guaCustomerOrder` AS `guaCustomerOrder`,`mpo`.`quantity` AS `quantity`,`mpo`.`relSequence` AS `relSequence`,`mpo`.`quantita_prodotta` AS `quantita_prodotta`,`mpo`.`family` AS `family`,`mpo`.`commento` AS `commento`,`mc`.`GUAPosition` AS `GUAPosition`,concat(`mpo`.`machinePress`,' ',`mpo`.`machinePressDesc`) AS `machinePressFull` from (`table_gua_mes_prod_orders` `mpo` left join `machine_center` `mc` on(`mpo`.`machinePress` = `mc`.`no`)) where `mpo`.`mesOrderNo` like '%ST%' and `mc`.`Company` like 'My Company' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-26 18:57:02
