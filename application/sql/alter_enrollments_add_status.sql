-- Enrollment approval workflow. Run once on your LMS database.
-- Existing rows become approved so behavior stays the same until new requests are made.

ALTER TABLE `enrollments`
  ADD COLUMN `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `course_id`;

UPDATE `enrollments` SET `status` = 'approved';
