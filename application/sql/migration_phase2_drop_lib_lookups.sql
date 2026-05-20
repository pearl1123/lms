-- Optional cleanup if an older Phase 2 migration created duplicate HR lookup tables in db_lms.
-- Safe to re-run. Does NOT touch HRMIS (dbhrmis_staging).

DROP TABLE IF EXISTS `lib_professions`;
DROP TABLE IF EXISTS `lib_departments`;

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) > 0,
    'ALTER TABLE `aauth_users` DROP COLUMN `profession_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'aauth_users' AND COLUMN_NAME = 'profession_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) > 0,
    'ALTER TABLE `aauth_users` DROP COLUMN `department_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'aauth_users' AND COLUMN_NAME = 'department_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
