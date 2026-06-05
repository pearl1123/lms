-- Personal learning notes workspace
-- Safe to re-run patterns. Run after backup.

SET @db = DATABASE();

CREATE TABLE IF NOT EXISTS `learning_notes` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`            INT UNSIGNED NOT NULL,
  `course_id`          INT UNSIGNED NOT NULL,
  `module_id`          INT UNSIGNED NULL DEFAULT NULL,
  `note_title`         VARCHAR(255) NOT NULL DEFAULT '',
  `note_content`       TEXT NOT NULL,
  `content_type`       ENUM('video','pdf','slides','audio','general') NOT NULL DEFAULT 'general',
  `timestamp_seconds`  INT UNSIGNED NULL DEFAULT NULL,
  `pdf_page`           INT UNSIGNED NULL DEFAULT NULL,
  `slide_number`       INT UNSIGNED NULL DEFAULT NULL,
  `tags_json`          TEXT NULL,
  `is_pinned`          TINYINT(1) NOT NULL DEFAULT 0,
  `is_favorite`        TINYINT(1) NOT NULL DEFAULT 0,
  `color_label`        VARCHAR(20) NULL DEFAULT NULL COMMENT 'blue|yellow|green',
  `created_at`         DATETIME NOT NULL,
  `updated_at`         DATETIME NOT NULL,
  `archived`           TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ln_user` (`user_id`, `archived`),
  KEY `idx_ln_course` (`course_id`, `user_id`),
  KEY `idx_ln_module` (`module_id`, `user_id`),
  KEY `idx_ln_favorite` (`user_id`, `is_favorite`, `archived`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
