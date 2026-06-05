-- MySQL dump 10.13  Distrib 8.0.40, for Win64 (x86_64)
--
-- Host: 192.168.3.161    Database: db_lms
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aauth_group_to_group`
--

DROP TABLE IF EXISTS `aauth_group_to_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_group_to_group` (
  `group_id` int(11) unsigned NOT NULL,
  `subgroup_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`subgroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_groups`
--

DROP TABLE IF EXISTS `aauth_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `definition` text DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `encoded_by` varchar(100) DEFAULT NULL,
  `date_encoded` datetime DEFAULT current_timestamp(),
  `modified_by` varchar(100) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_login_attempts`
--

DROP TABLE IF EXISTS `aauth_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(39) DEFAULT '0',
  `timestamp` datetime DEFAULT NULL,
  `login_attempts` tinyint(2) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2626 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perm_module_main`
--

DROP TABLE IF EXISTS `aauth_perm_module_main`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perm_module_main` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perm_module_sub`
--

DROP TABLE IF EXISTS `aauth_perm_module_sub`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perm_module_sub` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_main_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `view` varchar(100) DEFAULT NULL,
  `add` varchar(100) DEFAULT NULL,
  `edit` varchar(100) DEFAULT NULL,
  `delete` varchar(100) DEFAULT NULL,
  `extra_permissions` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perm_to_group`
--

DROP TABLE IF EXISTS `aauth_perm_to_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perm_to_group` (
  `perm_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`perm_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perm_to_user`
--

DROP TABLE IF EXISTS `aauth_perm_to_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perm_to_user` (
  `perm_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`perm_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perms`
--

DROP TABLE IF EXISTS `aauth_perms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `definition` text DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `encoded_by` varchar(100) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `modified_by` varchar(100) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `module_sub_id` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_perms_new`
--

DROP TABLE IF EXISTS `aauth_perms_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_perms_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perms_code` varchar(10) NOT NULL,
  `perms_name` varchar(45) NOT NULL,
  `perms_category` int(11) NOT NULL COMMENT '1 - USER MANAGEMENT, 2 - SYSTEM LIBRARIES, 3 - PATIENT LIST, 4 - PATIENT CHART, 5 - CLINICAL NOTES, 6 - REPORTS',
  `perms_definition` text NOT NULL,
  `perms_module` varchar(45) NOT NULL,
  `archived` tinyint(4) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `modified_by` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_user_to_group`
--

DROP TABLE IF EXISTS `aauth_user_to_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_user_to_group` (
  `user_id` int(11) NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `fk_group` (`group_id`),
  CONSTRAINT `aauth_user_to_group_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_group` FOREIGN KEY (`group_id`) REFERENCES `aauth_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aauth_users`
--

DROP TABLE IF EXISTS `aauth_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aauth_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `bio` varchar(500) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `fullname` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','employee') DEFAULT 'employee',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `employment_type` varchar(100) DEFAULT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `banned` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `forgot_exp` text DEFAULT NULL,
  `remember_time` datetime DEFAULT NULL,
  `remember_exp` text DEFAULT NULL,
  `verification_code` text DEFAULT NULL,
  `totp_secret` varchar(16) DEFAULT NULL,
  `ip_address` text DEFAULT NULL,
  `phone_number` varchar(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_date_start` datetime DEFAULT NULL,
  `token_date_end` datetime DEFAULT NULL,
  `email_verify_token` varchar(255) DEFAULT NULL,
  `email_verify` tinyint(4) DEFAULT 0,
  `email_verify_req_date` datetime DEFAULT NULL,
  `email_verify_date` datetime DEFAULT NULL,
  `date_created` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `DELETED` tinyint(1) NOT NULL DEFAULT 0,
  `office` varchar(150) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `declare` int(1) NOT NULL DEFAULT 0,
  `doctor_id` int(11) NOT NULL DEFAULT 0,
  `patient_profile` int(11) DEFAULT 0,
  `ui` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_answers`
--

DROP TABLE IF EXISTS `assessment_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `essay_file_path` varchar(255) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `user_id` (`user_id`),
  KEY `checked_by` (`checked_by`),
  CONSTRAINT `assessment_answers_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `lib_assessment_questions` (`id`),
  CONSTRAINT `assessment_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `assessment_answers_ibfk_3` FOREIGN KEY (`checked_by`) REFERENCES `aauth_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_attempt_orders`
--

DROP TABLE IF EXISTS `assessment_attempt_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_attempt_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `enrollment_id` int(10) unsigned DEFAULT NULL,
  `assessment_version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_legacy_version` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 when assessment content_version advanced mid-attempt',
  `question_order_json` text NOT NULL,
  `choice_order_json` text DEFAULT NULL,
  `status` enum('active','submitted','retaken') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aao_assessment_user_status` (`assessment_id`,`user_id`,`status`),
  KEY `idx_aao_user` (`user_id`),
  KEY `idx_aao_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `certificate_signatories`
--

DROP TABLE IF EXISTS `certificate_signatories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificate_signatories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `title` varchar(120) DEFAULT NULL,
  `signature_image_path` varchar(255) DEFAULT NULL,
  `order_no` int(10) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cs_course` (`course_id`),
  KEY `idx_cs_order` (`course_id`,`order_no`),
  KEY `idx_cs_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_batches`
--

DROP TABLE IF EXISTS `course_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `batch_name` varchar(120) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','active','closed') NOT NULL DEFAULT 'planned',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cb_course` (`course_id`),
  KEY `idx_cb_status` (`status`),
  KEY `idx_cb_archived` (`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_categories`
--

DROP TABLE IF EXISTS `course_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_categories_map`
--

DROP TABLE IF EXISTS `course_categories_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_categories_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_category` (`course_id`,`category_id`),
  KEY `idx_ccm_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_departments`
--

DROP TABLE IF EXISTS `course_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `department_id` int(10) unsigned NOT NULL COMMENT 'HRMIS tbldepartment.id',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_department` (`course_id`,`department_id`),
  KEY `idx_cd_department` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_instructors`
--

DROP TABLE IF EXISTS `course_instructors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_instructors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_instructor` (`course_id`,`user_id`),
  KEY `idx_ci_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_invitations`
--

DROP TABLE IF EXISTS `course_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `invited_by` int(10) unsigned NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `status` enum('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cinv_course` (`course_id`),
  KEY `idx_cinv_user` (`user_id`),
  KEY `idx_cinv_token` (`token`),
  KEY `idx_cinv_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_module_video_checkpoints`
--

DROP TABLE IF EXISTS `course_module_video_checkpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_module_video_checkpoints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `choices` longtext NOT NULL COMMENT 'JSON array of choice strings',
  `correct_choice_index` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `trigger_percent` decimal(5,2) DEFAULT NULL COMMENT '0-100; used when trigger_seconds is 0',
  `trigger_seconds` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'If > 0, fires at this timestamp (seconds)',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `course_module_video_checkpoints_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`),
  CONSTRAINT `course_module_youtube_quizzes_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_modules`
--

DROP TABLE IF EXISTS `course_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content_type` enum('pdf','slides','video','audio','zoom_recording') NOT NULL,
  `content_path` varchar(255) DEFAULT NULL,
  `weight_percentage` decimal(5,2) DEFAULT 0.00,
  `module_order` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `course_modules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `course_professions`
--

DROP TABLE IF EXISTS `course_professions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_professions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `profession_id` int(10) unsigned NOT NULL COMMENT 'Key from HRMIS tblemployee.Position (see course_phase2_hrmis_position_key)',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_profession` (`course_id`,`profession_id`),
  KEY `idx_cp_profession` (`profession_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `modality_id` int(11) DEFAULT NULL,
  `access_type_id` int(11) DEFAULT NULL,
  `access_type` varchar(32) NOT NULL DEFAULT 'approval_required' COMMENT 'open|approval_required|invitation_only|hidden',
  `publish_status` varchar(20) NOT NULL DEFAULT 'published' COMMENT 'draft|published|unpublished',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `expiry_days` int(11) DEFAULT NULL,
  `certificate_prefix` varchar(12) DEFAULT NULL,
  `signatory_name` varchar(120) DEFAULT NULL,
  `signatory_title` varchar(120) DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `fk_course_category` (`category_id`),
  KEY `fk_course_modality` (`modality_id`),
  KEY `fk_course_access_type` (`access_type_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `fk_course_access_type` FOREIGN KEY (`access_type_id`) REFERENCES `lib_course_access_type` (`access_type_id`),
  CONSTRAINT `fk_course_category` FOREIGN KEY (`category_id`) REFERENCES `course_categories` (`id`),
  CONSTRAINT `fk_course_modality` FOREIGN KEY (`modality_id`) REFERENCES `lib_course_modality` (`modality_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `enrolled_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`course_id`),
  KEY `course_id` (`course_id`),
  KEY `idx_enroll_batch` (`batch_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `learning_notes`
--

DROP TABLE IF EXISTS `learning_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `learning_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `course_id` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned DEFAULT NULL,
  `note_title` varchar(255) NOT NULL DEFAULT '',
  `note_content` text NOT NULL,
  `content_type` enum('video','pdf','slides','audio','general') NOT NULL DEFAULT 'general',
  `timestamp_seconds` int(10) unsigned DEFAULT NULL,
  `pdf_page` int(10) unsigned DEFAULT NULL,
  `slide_number` int(10) unsigned DEFAULT NULL,
  `tags_json` text DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `is_favorite` tinyint(1) NOT NULL DEFAULT 0,
  `color_label` varchar(20) DEFAULT NULL COMMENT 'blue|yellow|green',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ln_user` (`user_id`,`archived`),
  KEY `idx_ln_course` (`course_id`,`user_id`),
  KEY `idx_ln_module` (`module_id`,`user_id`),
  KEY `idx_ln_favorite` (`user_id`,`is_favorite`,`archived`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_assessment_choices`
--

DROP TABLE IF EXISTS `lib_assessment_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_assessment_choices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `choice_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `choice_order` int(11) NOT NULL DEFAULT 1,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `fk_choice_question` FOREIGN KEY (`question_id`) REFERENCES `lib_assessment_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_assessment_questions`
--

DROP TABLE IF EXISTS `lib_assessment_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_assessment_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','essay','likert','fill_blank') NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `min_words` int(11) DEFAULT NULL,
  `essay_response_mode` varchar(20) NOT NULL DEFAULT 'text' COMMENT 'text|pdf|text_or_pdf',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  CONSTRAINT `lib_assessment_questions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `lib_assessments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_assessments`
--

DROP TABLE IF EXISTS `lib_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `type` enum('pre','post','checkpoint') NOT NULL,
  `context` enum('course','module','video') NOT NULL DEFAULT 'module',
  `trigger_type` enum('manual','percent','seconds') NOT NULL DEFAULT 'manual',
  `trigger_value` decimal(5,2) DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Shuffle Q/choices per attempt',
  `legacy_checkpoint_id` int(11) DEFAULT NULL COMMENT 'Legacy course_module_video_checkpoints.id after migration',
  `legacy_youtube_quiz_id` int(11) DEFAULT NULL COMMENT 'Set during migration from course_module_youtube_quizzes; optional',
  `title` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `content_version` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Incremented on Q/choice edits',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lib_assessments_legacy_youtube` (`legacy_youtube_quiz_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `lib_assessments_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_certificate_logs`
--

DROP TABLE IF EXISTS `lib_certificate_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_certificate_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_id` int(11) NOT NULL,
  `action` enum('issued','revoked','printed') NOT NULL,
  `action_by` int(11) DEFAULT NULL,
  `action_at` datetime NOT NULL,
  `remarks` text DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `certificate_id` (`certificate_id`),
  CONSTRAINT `lib_certificate_logs_ibfk_1` FOREIGN KEY (`certificate_id`) REFERENCES `lib_certificates` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_certificate_templates`
--

DROP TABLE IF EXISTS `lib_certificate_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_certificate_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(150) NOT NULL,
  `template_file` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_certificate_types`
--

DROP TABLE IF EXISTS `lib_certificate_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_certificate_types` (
  `certificate_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`certificate_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_certificates`
--

DROP TABLE IF EXISTS `lib_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(100) NOT NULL,
  `issued_at` datetime NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_code` (`certificate_code`),
  UNIQUE KEY `uq_lib_certificates_code` (`certificate_code`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `lib_certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `lib_certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_course_access_type`
--

DROP TABLE IF EXISTS `lib_course_access_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_course_access_type` (
  `access_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `access_type_desc` varchar(100) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`access_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_course_modality`
--

DROP TABLE IF EXISTS `lib_course_modality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_course_modality` (
  `modality_id` int(11) NOT NULL AUTO_INCREMENT,
  `modality_desc` varchar(100) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`modality_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_notification`
--

DROP TABLE IF EXISTS `lib_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_notification` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type_id` int(11) DEFAULT NULL,
  `notification_title` varchar(150) DEFAULT NULL,
  `notification_message` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`notification_id`),
  KEY `fk_notification_type` (`notification_type_id`),
  CONSTRAINT `fk_notification_type` FOREIGN KEY (`notification_type_id`) REFERENCES `lib_notification_type` (`notification_type_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_notification_channel`
--

DROP TABLE IF EXISTS `lib_notification_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_notification_channel` (
  `channel_id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_name` varchar(50) NOT NULL,
  `channel_desc` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`channel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_notification_channel_link`
--

DROP TABLE IF EXISTS `lib_notification_channel_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_notification_channel_link` (
  `notification_channel_link_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `date_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_channel_link_id`),
  KEY `fk_channel_link_notification` (`notification_id`),
  KEY `fk_channel_link_channel` (`channel_id`),
  CONSTRAINT `fk_channel_link_channel` FOREIGN KEY (`channel_id`) REFERENCES `lib_notification_channel` (`channel_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_channel_link_notification` FOREIGN KEY (`notification_id`) REFERENCES `lib_notification` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_notification_type`
--

DROP TABLE IF EXISTS `lib_notification_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_notification_type` (
  `notification_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_type_desc` varchar(100) DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`notification_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lib_user_notification`
--

DROP TABLE IF EXISTS `lib_user_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lib_user_notification` (
  `user_notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `date_read` datetime DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_notification_id`),
  KEY `fk_user_notification_notification` (`notification_id`),
  KEY `fk_user_notification_user` (`user_id`),
  CONSTRAINT `fk_user_notification_notification` FOREIGN KEY (`notification_id`) REFERENCES `lib_notification` (`notification_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_notification_user` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lms_settings`
--

DROP TABLE IF EXISTS `lms_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lms_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` mediumtext DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_settings_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_progress`
--

DROP TABLE IF EXISTS `module_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `score` decimal(5,2) DEFAULT NULL,
  `resume_state` text DEFAULT NULL COMMENT 'JSON resume payload',
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `module_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `module_progress_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `registration_attempts`
--

DROP TABLE IF EXISTS `registration_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `registration_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `registration_attempts_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime NOT NULL,
  `grade` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_youtube_quiz_passes`
--

DROP TABLE IF EXISTS `user_youtube_quiz_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_youtube_quiz_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `passed_at` datetime NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-02 11:24:01
