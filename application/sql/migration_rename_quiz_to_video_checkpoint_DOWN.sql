-- ============================================================================
-- DOWN: Roll back video checkpoint renames → legacy youtube quiz names
-- ============================================================================
-- Idempotent: each phase runs only if the current schema matches that phase.
-- Safe if lib was never migrated (no uq_lib_assessments_legacy_checkpoint).
-- Safe if passes table is already user_youtube_quiz_passes (skips video rollback).
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1) lib_assessments: only if legacy_checkpoint_id column still exists
-- ---------------------------------------------------------------------------
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

SET @dropsql := IF(
  @lib_has_checkpoint_col > 0 AND @ix_uq_cp > 0,
  'ALTER TABLE `lib_assessments` DROP INDEX `uq_lib_assessments_legacy_checkpoint`',
  'SELECT 1 AS skip_drop_uq_legacy_checkpoint'
);
PREPARE p FROM @dropsql; EXECUTE p; DEALLOCATE PREPARE p;

SET @chsql := IF(
  @lib_has_checkpoint_col > 0,
  'ALTER TABLE `lib_assessments`
    CHANGE COLUMN `legacy_checkpoint_id` `legacy_youtube_quiz_id`
    INT(11) NULL DEFAULT NULL
    COMMENT ''Set during migration from course_module_youtube_quizzes; optional''',
  'SELECT 1 AS skip_lib_revert_column'
);
PREPARE p FROM @chsql; EXECUTE p; DEALLOCATE PREPARE p;

SET @lib_has_youtube_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND COLUMN_NAME = 'legacy_youtube_quiz_id'
);
SET @ix_uq_yt := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lib_assessments'
    AND INDEX_NAME = 'uq_lib_assessments_legacy_youtube'
);

SET @addsql := IF(
  @lib_has_youtube_col > 0 AND @ix_uq_yt = 0,
  'ALTER TABLE `lib_assessments`
    ADD UNIQUE KEY `uq_lib_assessments_legacy_youtube` (`legacy_youtube_quiz_id`)',
  'SELECT 1 AS skip_add_uq_legacy_youtube_or_already_present'
);
PREPARE p FROM @addsql; EXECUTE p; DEALLOCATE PREPARE p;

-- ---------------------------------------------------------------------------
-- 2–7) Passes + module FK + renames (only if new table names exist)
-- ---------------------------------------------------------------------------
SET @have_vid_passes := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_video_checkpoint_passes'
);
SET @have_vid_def := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_module_video_checkpoints'
);

SET @fk_pass_user := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_video_checkpoint_passes'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'user_video_checkpoint_passes_ibfk_user'
);
SET @fk_pass_cp := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_video_checkpoint_passes'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'user_video_checkpoint_passes_ibfk_checkpoint'
);

SET @s := IF(
  @have_vid_passes > 0 AND @fk_pass_user > 0,
  'ALTER TABLE `user_video_checkpoint_passes` DROP FOREIGN KEY `user_video_checkpoint_passes_ibfk_user`',
  'SELECT 1 AS skip_drop_passes_fk_user'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(
  @have_vid_passes > 0 AND @fk_pass_cp > 0,
  'ALTER TABLE `user_video_checkpoint_passes` DROP FOREIGN KEY `user_video_checkpoint_passes_ibfk_checkpoint`',
  'SELECT 1 AS skip_drop_passes_fk_checkpoint'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @ix_user_checkpoint := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_video_checkpoint_passes'
    AND INDEX_NAME = 'user_checkpoint'
);
SET @ix_idx_checkpoint := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_video_checkpoint_passes'
    AND INDEX_NAME = 'idx_checkpoint_id'
);

SET @s := IF(
  @have_vid_passes > 0 AND @ix_user_checkpoint > 0,
  'ALTER TABLE `user_video_checkpoint_passes` DROP INDEX `user_checkpoint`',
  'SELECT 1 AS skip'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(
  @have_vid_passes > 0 AND @ix_idx_checkpoint > 0,
  'ALTER TABLE `user_video_checkpoint_passes` DROP INDEX `idx_checkpoint_id`',
  'SELECT 1 AS skip'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @fk_mod := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'course_module_video_checkpoints'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    AND CONSTRAINT_NAME = 'course_module_video_checkpoints_ibfk_module'
);

SET @s := IF(
  @have_vid_def > 0 AND @fk_mod > 0,
  'ALTER TABLE `course_module_video_checkpoints` DROP FOREIGN KEY `course_module_video_checkpoints_ibfk_module`',
  'SELECT 1 AS skip_drop_module_fk'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(
  @have_vid_def > 0,
  'ALTER TABLE `course_module_video_checkpoints`
    ADD CONSTRAINT `course_module_youtube_quizzes_ibfk_1`
    FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)',
  'SELECT 1 AS skip_add_old_module_fk_name'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(
  @have_vid_passes > 0,
  'RENAME TABLE `user_video_checkpoint_passes` TO `user_youtube_quiz_passes`',
  'SELECT 1 AS skip_rename_passes'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @s := IF(
  @have_vid_def > 0,
  'RENAME TABLE `course_module_video_checkpoints` TO `course_module_youtube_quizzes`',
  'SELECT 1 AS skip_rename_definition'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @have_legacy_passes := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
);

SET @s := IF(
  @have_legacy_passes > 0,
  'ALTER TABLE `user_youtube_quiz_passes`
    CHANGE COLUMN `checkpoint_id` `quiz_id`
    INT(11) NOT NULL',
  'SELECT 1 AS skip_change_quiz_column'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @uq_user_quiz := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
    AND INDEX_NAME = 'user_quiz'
);

SET @s := IF(
  @have_legacy_passes > 0 AND @uq_user_quiz = 0,
  'ALTER TABLE `user_youtube_quiz_passes`
    ADD UNIQUE KEY `user_quiz` (`user_id`, `quiz_id`)',
  'SELECT 1 AS skip_add_user_quiz'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @ix_quiz_id := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
    AND INDEX_NAME = 'quiz_id'
);

SET @s := IF(
  @have_legacy_passes > 0 AND @ix_quiz_id = 0,
  'ALTER TABLE `user_youtube_quiz_passes` ADD KEY `quiz_id` (`quiz_id`)',
  'SELECT 1 AS skip_add_quiz_id_key'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @fk1 := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
    AND CONSTRAINT_NAME = 'user_youtube_quiz_passes_ibfk_1'
);

SET @s := IF(
  @have_legacy_passes > 0 AND @fk1 = 0,
  'ALTER TABLE `user_youtube_quiz_passes`
    ADD CONSTRAINT `user_youtube_quiz_passes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`)',
  'SELECT 1 AS skip_add_fk_user'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET @fk2 := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_youtube_quiz_passes'
    AND CONSTRAINT_NAME = 'user_youtube_quiz_passes_ibfk_2'
);

SET @s := IF(
  @have_legacy_passes > 0 AND @fk2 = 0,
  'ALTER TABLE `user_youtube_quiz_passes`
    ADD CONSTRAINT `user_youtube_quiz_passes_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `course_module_youtube_quizzes` (`id`)',
  'SELECT 1 AS skip_add_fk_quiz'
);
PREPARE p FROM @s; EXECUTE p; DEALLOCATE PREPARE p;

SET FOREIGN_KEY_CHECKS = 1;
