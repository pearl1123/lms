-- Enhancement Phase 6 — points, leaderboard, category hierarchy
-- Run manually on dev/staging. See rollback_enhancement_phase6.sql.

SET @db = DATABASE();

-- ---------------------------------------------------------------------------
-- user_points
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_points` (
  `user_id` int(11) NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_user_points_total` (`total_points` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- points_rules
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `points_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_key` varchar(64) NOT NULL,
  `label` varchar(120) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_points_rules_key` (`rule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `points_rules` (`rule_key`, `label`, `points`, `is_active`, `date_encoded`)
SELECT 'course_completion', 'Course completion', 100, 1, NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `points_rules` WHERE `rule_key` = 'course_completion');

INSERT INTO `points_rules` (`rule_key`, `label`, `points`, `is_active`, `date_encoded`)
SELECT 'assessment_pass', 'Assessment pass', 50, 1, NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `points_rules` WHERE `rule_key` = 'assessment_pass');

INSERT INTO `points_rules` (`rule_key`, `label`, `points`, `is_active`, `date_encoded`)
SELECT 'certificate_issued', 'Certificate issuance', 25, 1, NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `points_rules` WHERE `rule_key` = 'certificate_issued');

-- ---------------------------------------------------------------------------
-- points_transactions
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `points_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rule_key` varchar(64) NOT NULL DEFAULT '',
  `points` int(11) NOT NULL DEFAULT 0,
  `reference_type` varchar(32) NOT NULL DEFAULT '',
  `reference_id` int(11) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_points_tx` (`user_id`,`rule_key`,`reference_type`,`reference_id`),
  KEY `idx_points_tx_user_created` (`user_id`,`created_at`),
  KEY `idx_points_tx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- course_categories.parent_id (nested categories)
-- ---------------------------------------------------------------------------
SET @t = 'course_categories';

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `course_categories` ADD COLUMN `parent_id` int(11) DEFAULT NULL COMMENT ''Parent category; NULL=root'' AFTER `name`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'parent_id';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `course_categories` ADD KEY `idx_cc_parent` (`parent_id`)',
  'SELECT 1') INTO @sql FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND INDEX_NAME = 'idx_cc_parent';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
