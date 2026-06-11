-- Rollback migration_master_phase5.sql (manual run)

SET @db = DATABASE();

DROP TABLE IF EXISTS `lms_hrmis_demographic_cache`;
DROP TABLE IF EXISTS `course_module_prerequisites`;
DROP TABLE IF EXISTS `notification_email_log`;

SET @t = 'activity_logs';
SELECT IF(COUNT(*) > 0, 'ALTER TABLE `activity_logs` DROP COLUMN `details`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'details';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0, 'ALTER TABLE `activity_logs` DROP COLUMN `reference_id`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'reference_id';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0, 'ALTER TABLE `activity_logs` DROP COLUMN `module`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'module';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @t = 'lib_assessment_questions';
SELECT IF(COUNT(*) > 0, 'ALTER TABLE `lib_assessment_questions` DROP COLUMN `essay_response_mode`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'essay_response_mode';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @t = 'lib_assessments';
SELECT IF(COUNT(*) > 0, 'ALTER TABLE `lib_assessments` DROP COLUMN `pass_threshold_pct`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'pass_threshold_pct';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @t = 'courses';
SELECT IF(COUNT(*) > 0, 'ALTER TABLE `courses` DROP COLUMN `enrollment_deadline`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'enrollment_deadline';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0, 'ALTER TABLE `courses` DROP COLUMN `max_capacity`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'max_capacity';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0, 'ALTER TABLE `courses` DROP COLUMN `enforce_sequential_modules`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'enforce_sequential_modules';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0, 'ALTER TABLE `courses` DROP COLUMN `pass_threshold_pct`', 'SELECT 1') INTO @sql FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'pass_threshold_pct';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
