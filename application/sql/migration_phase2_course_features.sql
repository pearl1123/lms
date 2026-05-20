-- Phase 2: course categories, instructors, visibility mappings, access, publish, invitations
-- Safe to re-run (IF NOT EXISTS / conditional ALTER).
--
-- All new LMS tables include standard audit columns (see fragments/lms_standard_audit_columns.sql).
--
-- HRMIS (dbhrmis_staging) is the ONLY source for department / job data:
--   tbldepartment.id     → course_departments.department_id
--   tblemployee.Position → course_professions.profession_id (stable key via app helper)

-- ---------------------------------------------------------------------------
-- Course mapping tables (LMS)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `course_categories_map` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_category` (`course_id`,`category_id`),
  KEY `idx_ccm_category` (`category_id`),
  KEY `idx_ccm_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_instructors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_instructor` (`course_id`,`user_id`),
  KEY `idx_ci_user` (`user_id`),
  KEY `idx_ci_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `department_id` int(10) unsigned NOT NULL COMMENT 'HRMIS tbldepartment.id',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_department` (`course_id`,`department_id`),
  KEY `idx_cd_department` (`department_id`),
  KEY `idx_cd_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_professions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `profession_id` int(10) unsigned NOT NULL COMMENT 'Key from HRMIS tblemployee.Position',
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_profession` (`course_id`,`profession_id`),
  KEY `idx_cp_profession` (`profession_id`),
  KEY `idx_cp_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `course_invitations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `invited_by` int(10) unsigned NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `status` enum('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` datetime DEFAULT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cinv_course` (`course_id`),
  KEY `idx_cinv_user` (`user_id`),
  KEY `idx_cinv_token` (`token`),
  KEY `idx_cinv_status` (`status`),
  KEY `idx_cinv_archived` (`archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- courses: access workflow + publish lifecycle
-- ---------------------------------------------------------------------------
SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `access_type` VARCHAR(32) NOT NULL DEFAULT ''approval_required'' COMMENT ''open|approval_required|invitation_only|hidden'' AFTER `access_type_id`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'access_type'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `publish_status` VARCHAR(20) NOT NULL DEFAULT ''published'' COMMENT ''draft|published|unpublished'' AFTER `access_type`',
    'SELECT 1'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'publish_status'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- Add standard columns to Phase 2 tables created before this revision (safe re-run)
-- ---------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS lms_add_standard_audit_columns;
DELIMITER //
CREATE PROCEDURE lms_add_standard_audit_columns(IN p_table VARCHAR(64))
BEGIN
  SET @db = DATABASE();
  SET @t = p_table;

  SET @sql = (SELECT IF(COUNT(*)=0, CONCAT('ALTER TABLE `', @t, '` ADD COLUMN `date_encoded` datetime DEFAULT NULL'), 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@t AND COLUMN_NAME='date_encoded');
  PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

  SET @sql = (SELECT IF(COUNT(*)=0, CONCAT('ALTER TABLE `', @t, '` ADD COLUMN `encoded_by` int(11) DEFAULT NULL'), 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@t AND COLUMN_NAME='encoded_by');
  PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

  SET @sql = (SELECT IF(COUNT(*)=0, CONCAT('ALTER TABLE `', @t, '` ADD COLUMN `date_last_modified` datetime DEFAULT NULL'), 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@t AND COLUMN_NAME='date_last_modified');
  PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

  SET @sql = (SELECT IF(COUNT(*)=0, CONCAT('ALTER TABLE `', @t, '` ADD COLUMN `modified_by` int(11) DEFAULT NULL'), 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@t AND COLUMN_NAME='modified_by');
  PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

  SET @sql = (SELECT IF(COUNT(*)=0, CONCAT('ALTER TABLE `', @t, '` ADD COLUMN `archived` tinyint(1) NOT NULL DEFAULT 0'), 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME=@t AND COLUMN_NAME='archived');
  PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
END//
DELIMITER ;

CALL lms_add_standard_audit_columns('course_categories_map');
CALL lms_add_standard_audit_columns('course_instructors');
CALL lms_add_standard_audit_columns('course_departments');
CALL lms_add_standard_audit_columns('course_professions');
CALL lms_add_standard_audit_columns('course_invitations');

DROP PROCEDURE IF EXISTS lms_add_standard_audit_columns;

-- Backfill category map from legacy single category_id
INSERT IGNORE INTO course_categories_map (course_id, category_id, date_encoded, archived)
SELECT c.id, c.category_id, COALESCE(c.date_encoded, c.created_at, NOW()), 0
FROM courses c
WHERE c.category_id IS NOT NULL AND c.category_id > 0;

-- Backfill primary instructor from created_by
INSERT IGNORE INTO course_instructors (course_id, user_id, date_encoded, encoded_by, archived)
SELECT c.id, c.created_by, COALESCE(c.date_encoded, c.created_at, NOW()), c.created_by, 0
FROM courses c
WHERE c.created_by IS NOT NULL AND c.created_by > 0;
