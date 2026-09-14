-- Course overview fields: pass threshold, sequential modules, capacity, enrollment deadline.
-- Safe to re-run: skips ADD when each column already exists.
--
-- Example (mysql CLI):
--   mysql -u injkbarrac -p db_lms < application/sql/migration_course_overview_fields.sql

SET @db = DATABASE();

-- pass_threshold_pct
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'pass_threshold_pct'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `courses` ADD COLUMN `pass_threshold_pct` DECIMAL(5,2) NULL DEFAULT NULL COMMENT ''Override global pass %; NULL=default'' AFTER `signatory_title`',
  'SELECT ''courses.pass_threshold_pct already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- enforce_sequential_modules
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'enforce_sequential_modules'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `courses` ADD COLUMN `enforce_sequential_modules` TINYINT(1) NOT NULL DEFAULT 1 AFTER `pass_threshold_pct`',
  'SELECT ''courses.enforce_sequential_modules already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- max_capacity
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'max_capacity'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `courses` ADD COLUMN `max_capacity` INT(11) NULL DEFAULT NULL AFTER `enforce_sequential_modules`',
  'SELECT ''courses.max_capacity already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- enrollment_deadline
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'enrollment_deadline'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `courses` ADD COLUMN `enrollment_deadline` DATETIME NULL DEFAULT NULL AFTER `max_capacity`',
  'SELECT ''courses.enrollment_deadline already exists'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
