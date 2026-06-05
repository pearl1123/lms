-- State integrity: optional resume payload on module_progress (idempotent)
SET @db = DATABASE();

SET @t = 'module_progress';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `module_progress` ADD COLUMN `resume_state` TEXT NULL COMMENT ''JSON resume payload'' AFTER `score`',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'resume_state');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
