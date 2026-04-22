-- DEPRECATED: superseded by lib_assessments (type=checkpoint, context=video)
-- + lib_assessment_questions / lib_assessment_choices + assessment_answers.
-- Kept for reference / rollback; new installs should use the unified engine only.
--
-- YouTube checkpoint quizzes (per course module).
-- Audit columns align with lib_* tables (e.g. lib_certificates).
-- choices: LONGTEXT storing JSON (use json_encode / json_decode in PHP — no native JSON column).

CREATE TABLE IF NOT EXISTS `course_module_youtube_quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `choices` longtext NOT NULL COMMENT 'JSON array of choice strings',
  `correct_choice_index` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `trigger_percent` decimal(5,2) DEFAULT NULL COMMENT '0-100; used when trigger_seconds is 0',
  `trigger_seconds` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'If > 0, fires at this timestamp (seconds)',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `course_module_youtube_quizzes_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `course_modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_youtube_quiz_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `passed_at` datetime NOT NULL,
  `date_encoded` datetime DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `date_last_modified` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_quiz` (`user_id`,`quiz_id`),
  KEY `quiz_id` (`quiz_id`),
  CONSTRAINT `user_youtube_quiz_passes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `aauth_users` (`id`),
  CONSTRAINT `user_youtube_quiz_passes_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `course_module_youtube_quizzes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
