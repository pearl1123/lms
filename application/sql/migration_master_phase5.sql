-- Phase 5 master migration — notification email log, module prerequisites, pass thresholds, activity log extensions
-- Run manually on dev/staging. See rollback_master_phase5.sql to reverse.

SET @db = DATABASE();

-- ---------------------------------------------------------------------------
-- notification_email_log
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email_to` varchar(255) NOT NULL,
  `template_key` varchar(32) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `status` enum('sent','failed','skipped') NOT NULL DEFAULT 'skipped',
  `error_message` text DEFAULT NULL,
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nel_user` (`user_id`),
  KEY `idx_nel_notification` (`notification_id`),
  KEY `idx_nel_status` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- course_module_prerequisites (custom rules; sequential enforced via module_order)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_module_prerequisites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `prerequisite_module_id` int(11) NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cmp_module_prereq` (`module_id`,`prerequisite_module_id`),
  KEY `fk_cmp_module` (`module_id`),
  KEY `fk_cmp_prereq` (`prerequisite_module_id`),
  CONSTRAINT `fk_cmp_module` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmp_prereq` FOREIGN KEY (`prerequisite_module_id`) REFERENCES `course_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- courses: F2F schedule, sequential flag, pass threshold, capacity, enrollment deadline
-- ---------------------------------------------------------------------------
SET @t = 'courses';

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `courses` ADD COLUMN `pass_threshold_pct` decimal(5,2) DEFAULT NULL COMMENT ''Override global pass %; NULL=default'' AFTER `signatory_title`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'pass_threshold_pct';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `courses` ADD COLUMN `enforce_sequential_modules` tinyint(1) NOT NULL DEFAULT 1 AFTER `pass_threshold_pct`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'enforce_sequential_modules';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `courses` ADD COLUMN `max_capacity` int(11) DEFAULT NULL AFTER `enforce_sequential_modules`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'max_capacity';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `courses` ADD COLUMN `enrollment_deadline` datetime DEFAULT NULL AFTER `max_capacity`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'enrollment_deadline';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- lib_assessments: per-assessment pass threshold
-- ---------------------------------------------------------------------------
SET @t = 'lib_assessments';

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `lib_assessments` ADD COLUMN `pass_threshold_pct` decimal(5,2) DEFAULT NULL COMMENT ''NULL=course/global default'' AFTER `randomize_questions`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'pass_threshold_pct';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- lib_assessment_questions: essay_response_mode (if Phase 4 not applied)
-- ---------------------------------------------------------------------------
SET @t = 'lib_assessment_questions';

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `lib_assessment_questions` ADD COLUMN `essay_response_mode` varchar(20) NOT NULL DEFAULT ''text'' COMMENT ''text|pdf|text_or_pdf'' AFTER `min_words`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'essay_response_mode';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- activity_logs: extended audit fields
-- ---------------------------------------------------------------------------
SET @t = 'activity_logs';

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `activity_logs` ADD COLUMN `module` varchar(64) DEFAULT NULL AFTER `action`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'module';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `activity_logs` ADD COLUMN `reference_id` int(11) DEFAULT NULL AFTER `module`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'reference_id';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `activity_logs` ADD COLUMN `details` text DEFAULT NULL AFTER `reference_id`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'details';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- HRMIS demographic cache (reports fallback)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_hrmis_demographic_cache` (
  `employee_id` varchar(50) NOT NULL,
  `department` varchar(120) DEFAULT NULL,
  `position` varchar(120) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `snapshot_json` text DEFAULT NULL,
  `cached_at` datetime NOT NULL,
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
