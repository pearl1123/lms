

-- MariaDB 10.3.3+ (recommended for Workbench)
-- (No semicolon inside COMMENT — avoids rare client mis-parsing.)
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `context` ENUM('course','module','video') NOT NULL DEFAULT 'module' AFTER `type`;
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `trigger_type` ENUM('manual','percent','seconds') NOT NULL DEFAULT 'manual' AFTER `context`;
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `trigger_value` DECIMAL(5,2) NULL DEFAULT NULL AFTER `trigger_type`;
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `is_required` TINYINT(1) NOT NULL DEFAULT 1 AFTER `trigger_value`;
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `sort_order` INT NOT NULL DEFAULT 0 AFTER `is_required`;
ALTER TABLE `lib_assessments` ADD COLUMN IF NOT EXISTS `legacy_youtube_quiz_id` INT(11) NULL DEFAULT NULL COMMENT 'Legacy YouTube quiz id after migration' AFTER `sort_order`;

