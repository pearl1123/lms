-- Course training duration (hours) for certificates / Overview.
-- Safe to re-run: skips ADD when column already exists.
--
-- Example (mysql CLI):
--   mysql -u root db_lms < application/sql/migration_course_training_hours.sql

SET @col_exists := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'courses'
    AND COLUMN_NAME = 'training_hours'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `courses` ADD COLUMN `training_hours` DECIMAL(6,1) NULL DEFAULT NULL COMMENT ''Official training duration in hours (shown on certificates)'' AFTER `venue`',
  'SELECT ''courses.training_hours already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
