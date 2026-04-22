-- =============================================================================
-- Unified assessments: extend lib_assessments for video checkpoints
-- Run ONCE after backup. Adjust the MODIFY line if `type` is VARCHAR (see note).
--
-- If some columns already exist, use instead:
--   application/sql/patch_lib_assessments_columns_incremental.sql
-- =============================================================================

-- New columns (pre/post rows keep defaults: context=module, trigger_type=manual)
ALTER TABLE `lib_assessments`
  ADD COLUMN `context` ENUM('course','module','video') NOT NULL DEFAULT 'module'
    AFTER `type`,
  ADD COLUMN `trigger_type` ENUM('manual','percent','seconds') NOT NULL DEFAULT 'manual'
    AFTER `context`,
  ADD COLUMN `trigger_value` DECIMAL(5,2) NULL DEFAULT NULL
    AFTER `trigger_type`,
  ADD COLUMN `is_required` TINYINT(1) NOT NULL DEFAULT 1
    AFTER `trigger_value`,
  ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0
    AFTER `is_required`,
  ADD COLUMN `legacy_youtube_quiz_id` INT NULL DEFAULT NULL
    COMMENT 'Set during migration from course_module_youtube_quizzes; optional'
    AFTER `sort_order`;

-- Allow checkpoint type.
-- If this fails because `type` is VARCHAR, keep VARCHAR and ensure only pre|post|checkpoint are inserted.
ALTER TABLE `lib_assessments`
  MODIFY COLUMN `type` ENUM('pre','post','checkpoint') NOT NULL;

-- Optional: idempotent re-runs of migration script
CREATE UNIQUE INDEX `uq_lib_assessments_legacy_youtube`
  ON `lib_assessments` (`legacy_youtube_quiz_id`);
