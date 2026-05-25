-- Optional LMS profile fields on aauth_users (safe to re-run).
-- Run if you want bio, contact number, and avatar path stored locally.

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `aauth_users` ADD COLUMN `contact_number` VARCHAR(30) NULL DEFAULT NULL AFTER `email`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'aauth_users' AND COLUMN_NAME = 'contact_number'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `aauth_users` ADD COLUMN `bio` VARCHAR(500) NULL DEFAULT NULL AFTER `contact_number`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'aauth_users' AND COLUMN_NAME = 'bio'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `aauth_users` ADD COLUMN `avatar_path` VARCHAR(255) NULL DEFAULT NULL AFTER `bio`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'aauth_users' AND COLUMN_NAME = 'avatar_path'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
