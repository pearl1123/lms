-- Assessment integrity: persistent attempt orders + content versioning
-- Safe to re-run (information_schema guards). Run after backup.

SET @db = DATABASE();

-- content_version on lib_assessments
SET @t = 'lib_assessments';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `lib_assessments` ADD COLUMN `content_version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT ''Incremented on Q/choice edits''',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'content_version');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill NULL-safe default
UPDATE `lib_assessments` SET `content_version` = 1 WHERE `content_version` IS NULL OR `content_version` < 1;

-- Persistent per-learner attempt shuffle + version snapshot
CREATE TABLE IF NOT EXISTS `assessment_attempt_orders` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assessment_id`       INT UNSIGNED NOT NULL,
  `user_id`             INT UNSIGNED NOT NULL,
  `enrollment_id`       INT UNSIGNED NULL DEFAULT NULL,
  `assessment_version`  INT UNSIGNED NOT NULL DEFAULT 1,
  `is_legacy_version`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 when assessment content_version advanced mid-attempt',
  `question_order_json` TEXT         NOT NULL,
  `choice_order_json`   TEXT         NULL,
  `status`              ENUM('active','submitted','retaken') NOT NULL DEFAULT 'active',
  `created_at`          DATETIME     NOT NULL,
  `updated_at`          DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aao_assessment_user_status` (`assessment_id`, `user_id`, `status`),
  KEY `idx_aao_user` (`user_id`),
  KEY `idx_aao_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
