-- Phase 4 ETD enhancements (incremental, safe to re-run patterns)
-- Run once on LMS database after backups.

-- Assessment randomization flag
SET @db = DATABASE();

SET @t = 'lib_assessments';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `lib_assessments` ADD COLUMN `randomize_questions` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Shuffle Q/choices per attempt'' AFTER `sort_order`',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'randomize_questions');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Default ON for existing pre/post assessments (checkpoints stay OFF)
UPDATE `lib_assessments`
SET `randomize_questions` = 1
WHERE `archived` = 0
  AND `type` IN ('pre', 'post')
  AND `randomize_questions` = 0;

-- Essay response mode on questions
SET @t = 'lib_assessment_questions';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `lib_assessment_questions` ADD COLUMN `essay_response_mode` VARCHAR(20) NOT NULL DEFAULT ''text'' COMMENT ''text|pdf|text_or_pdf'' AFTER `min_words`',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'essay_response_mode');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Essay PDF upload path on answers
SET @t = 'assessment_answers';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `assessment_answers` ADD COLUMN `essay_file_path` VARCHAR(255) NULL DEFAULT NULL AFTER `answer_text`',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'essay_file_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Certificate signatory image
SET @t = 'certificate_signatories';
SET @sql = (SELECT IF(
  COUNT(*) = 0,
  'ALTER TABLE `certificate_signatories` ADD COLUMN `signature_image_path` VARCHAR(255) NULL DEFAULT NULL AFTER `title`',
  'SELECT 1'
) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'signature_image_path');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Category UX fields (optional)
SET @t = 'course_categories';
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t) > 0
  AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'description') = 0,
  'ALTER TABLE `course_categories` ADD COLUMN `description` TEXT NULL AFTER `name`',
  'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t) > 0
  AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = @t AND COLUMN_NAME = 'color_hex') = 0,
  'ALTER TABLE `course_categories` ADD COLUMN `color_hex` VARCHAR(7) NULL DEFAULT NULL AFTER `description`',
  'SELECT 1'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
