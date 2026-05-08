-- ============================================================================
-- UP: Rename legacy "youtube quiz" tables/columns → video checkpoint naming
-- ============================================================================
-- Run in the database that OWNS these tables (e.g. USE db_lms; source this file).
--
-- Idempotent / resumable:
--   • If user_youtube_quiz_passes is already renamed (1146 on old name), the
--     table-rename block is SKIPPED and only lib_assessments is updated if needed.
--   • lib_assessments is skipped if legacy_checkpoint_id already exists.
--
-- If a step fails on FK names, run migration_rename_quiz_to_video_checkpoint_DISCOVER.sql
-- Backup first.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Decide whether full table migration still applies (both legacy names present)
-- ---------------------------------------------------------------------------
SET @run_tables := (
  SELECT CASE
    WHEN (
      SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
    ) > 0
     AND (
      SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_module_youtube_quizzes'
    ) > 0
    THEN 1 ELSE 0 END
);

-- ---------------------------------------------------------------------------
-- 0) Optional: drop FK from lib_assessments → course_module_youtube_quizzes
-- ---------------------------------------------------------------------------
SET @fk_lib := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'lib_assessments'
    AND REFERENCED_TABLE_NAME = 'course_module_youtube_quizzes'
  LIMIT 1
);
SET @dropsql := IF(
  @fk_lib IS NOT NULL AND CHAR_LENGTH(TRIM(@fk_lib)) > 0,
  CONCAT('ALTER TABLE `lib_assessments` DROP FOREIGN KEY `', REPLACE(@fk_lib, '`', ''), '`'),
  'SELECT 1 AS skip_drop_lib_fk'
);
PREPARE stmt_drop_lib_fk FROM @dropsql;
EXECUTE stmt_drop_lib_fk;
DEALLOCATE PREPARE stmt_drop_lib_fk;

-- ---------------------------------------------------------------------------
-- 1–8) Table renames + passes FKs (only when legacy tables still exist)
-- ---------------------------------------------------------------------------
SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_youtube_quiz_passes` DROP FOREIGN KEY `user_youtube_quiz_passes_ibfk_1`',
  'SELECT 1 AS skip_table_migration_start');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_youtube_quiz_passes` DROP FOREIGN KEY `user_youtube_quiz_passes_ibfk_2`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_youtube_quiz_passes` DROP INDEX `user_quiz`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_youtube_quiz_passes` DROP INDEX `quiz_id`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'RENAME TABLE `course_module_youtube_quizzes` TO `course_module_video_checkpoints`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'RENAME TABLE `user_youtube_quiz_passes` TO `user_video_checkpoint_passes`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_video_checkpoint_passes`
    CHANGE COLUMN `quiz_id` `checkpoint_id`
    INT(11) NOT NULL
    COMMENT ''FK to course_module_video_checkpoints.id''',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_video_checkpoint_passes`
    ADD UNIQUE KEY `user_checkpoint` (`user_id`, `checkpoint_id`)',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_video_checkpoint_passes`
    ADD KEY `idx_checkpoint_id` (`checkpoint_id`)',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_video_checkpoint_passes`
    ADD CONSTRAINT `user_video_checkpoint_passes_ibfk_user`
    FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`)',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `user_video_checkpoint_passes`
    ADD CONSTRAINT `user_video_checkpoint_passes_ibfk_checkpoint`
    FOREIGN KEY (`checkpoint_id`) REFERENCES `course_module_video_checkpoints` (`id`)',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `course_module_video_checkpoints`
    DROP FOREIGN KEY `course_module_youtube_quizzes_ibfk_1`',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(@run_tables > 0,
  'ALTER TABLE `course_module_video_checkpoints`
    ADD CONSTRAINT `course_module_video_checkpoints_ibfk_module`
    FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)',
  'SELECT 1 AS skip');
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

-- ---------------------------------------------------------------------------
-- 9) lib_assessments: legacy_youtube_quiz_id → legacy_checkpoint_id (idempotent)
-- ---------------------------------------------------------------------------
SET @lib_has_youtube := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND COLUMN_NAME = 'legacy_youtube_quiz_id'
);
SET @ix_uq_yt := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND INDEX_NAME = 'uq_lib_assessments_legacy_youtube'
);

SET @dropsql := IF(
  @lib_has_youtube > 0 AND @ix_uq_yt > 0,
  'ALTER TABLE `lib_assessments` DROP INDEX `uq_lib_assessments_legacy_youtube`',
  'SELECT 1 AS skip_drop_uq_legacy_youtube'
);
PREPARE p FROM @dropsql; EXECUTE p; DEALLOCATE PREPARE p;

SET @chsql := IF(
  @lib_has_youtube > 0,
  'ALTER TABLE `lib_assessments`
    CHANGE COLUMN `legacy_youtube_quiz_id` `legacy_checkpoint_id`
    INT(11) NULL DEFAULT NULL
    COMMENT ''Legacy course_module_video_checkpoints.id after unified migration''',
  'SELECT 1 AS skip_lib_column_already_checkpoint'
);
PREPARE p FROM @chsql; EXECUTE p; DEALLOCATE PREPARE p;

SET @lib_has_checkpoint_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND COLUMN_NAME = 'legacy_checkpoint_id'
);
SET @ix_uq_cp := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND INDEX_NAME = 'uq_lib_assessments_legacy_checkpoint'
);

SET @addsql := IF(
  @lib_has_checkpoint_col > 0 AND @ix_uq_cp = 0,
  'ALTER TABLE `lib_assessments`
    ADD UNIQUE KEY `uq_lib_assessments_legacy_checkpoint` (`legacy_checkpoint_id`)',
  'SELECT 1 AS skip_add_uq_checkpoint_or_already_present'
);
PREPARE p FROM @addsql; EXECUTE p; DEALLOCATE PREPARE p;

SET FOREIGN_KEY_CHECKS = 1;
