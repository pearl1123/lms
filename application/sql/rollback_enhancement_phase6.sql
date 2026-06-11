-- Rollback Enhancement Phase 6 migration

SET @db = DATABASE();

DROP TABLE IF EXISTS `points_transactions`;
DROP TABLE IF EXISTS `points_rules`;
DROP TABLE IF EXISTS `user_points`;

SET @t = 'course_categories';

SELECT IF(COUNT(*) > 0,
  'ALTER TABLE `course_categories` DROP INDEX `idx_cc_parent`',
  'SELECT 1') INTO @sql FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND INDEX_NAME = 'idx_cc_parent';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT IF(COUNT(*) > 0,
  'ALTER TABLE `course_categories` DROP COLUMN `parent_id`',
  'SELECT 1') INTO @sql FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'parent_id';
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
