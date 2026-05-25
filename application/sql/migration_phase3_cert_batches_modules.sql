-- Phase 3: certificate signatories, course batches, enrollment batch_id
-- Safe to re-run (IF NOT EXISTS / conditional ALTER).
--
-- Dev (Windows PowerShell): do not chain `php -l a.php && php -l b.php` (unsupported).
-- Use separate commands or ';' instead, e.g.:
--   php -l application/models/course_model.php; php -l application/controllers/Manage_courses.php

CREATE TABLE IF NOT EXISTS `certificate_signatories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `title` varchar(120) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_batches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- enrollments.batch_id (optional)
SET @db = DATABASE();
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `enrollments` ADD COLUMN `batch_id` int(10) unsigned DEFAULT NULL AFTER `course_id`, ADD KEY `idx_enroll_batch` (`batch_id`)',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'enrollments' AND COLUMN_NAME = 'batch_id'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

