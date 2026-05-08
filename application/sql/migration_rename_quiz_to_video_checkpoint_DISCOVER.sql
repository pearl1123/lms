-- Run these in the SAME database as your LMS tables (e.g. db_lms).
-- Use the output to fix FK / index names if the UP migration errors.

SELECT '--- Foreign keys on user_youtube_quiz_passes ---' AS info;
SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'user_youtube_quiz_passes'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT '--- Foreign keys FROM lib_assessments (any referenced table) ---' AS info;
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'lib_assessments'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT '--- Indexes on lib_assessments containing legacy ---' AS info;
SHOW INDEX FROM `lib_assessments` WHERE Key_name LIKE '%legacy%' OR Column_name LIKE '%legacy%';

SELECT '--- SHOW CREATE (copy/paste for support) ---' AS info;
-- SHOW CREATE TABLE `user_youtube_quiz_passes`;
-- SHOW CREATE TABLE `lib_assessments`;
-- SHOW CREATE TABLE `course_module_youtube_quizzes`;
