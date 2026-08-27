-- School dummy-data cleanup for MySQL 8+ / MariaDB 10.2+.
--
-- PRESERVES:
--   * The MoonShine admin whose username is "admin" and whose role is "Admin"
--   * moonshine_user_roles (required for creating future portal accounts)
--   * settings rows (feature/configuration structure)
--   * payment_types (system lookup values)
--   * migrations (Laravel schema history)
--
-- REMOVES:
--   * All academic, student, teacher, quiz, attendance, payment, assignment,
--     announcement, college, demo web-user, session, queue, and cache records
--   * Every MoonShine login except the preserved admin
--
-- IMPORTANT: Back up the database before running this file.
-- Run the complete script in one database session.

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;

-- Abort before the transaction unless exactly one expected admin exists.
-- NOT NULL is used instead of relying only on CHECK, for older MySQL versions.
DROP TEMPORARY TABLE IF EXISTS `cleanup_admin_guard`;
CREATE TEMPORARY TABLE `cleanup_admin_guard` (
    `valid` TINYINT NOT NULL
) ENGINE = MEMORY;

INSERT INTO `cleanup_admin_guard` (`valid`)
SELECT IF(COUNT(*) = 1, 1, NULL)
FROM `moonshine_users` AS `u`
INNER JOIN `moonshine_user_roles` AS `r`
    ON `r`.`id` = `u`.`moonshine_user_role_id`
WHERE `u`.`username` = 'admin'
  AND `r`.`name` = 'Admin';

SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

-- Dependent/transactional records first.
DELETE FROM `assignment_submissions`;
DELETE FROM `assignments`;
DELETE FROM `college_enrollment_courses`;
DELETE FROM `college_enrollments`;
DELETE FROM `college_course_offerings`;
DELETE FROM `college_curriculum_subjects`;
DELETE FROM `student_payment_histories`;
DELETE FROM `student_quiz_answers`;
DELETE FROM `quiz_quiz_group_days`;
DELETE FROM `quiz_quiz_answers`;
DELETE FROM `quiz_group_days`;
DELETE FROM `quiz_group`;
DELETE FROM `quizzes`;
DELETE FROM `quiz_answers`;
DELETE FROM `attendance_record`;
DELETE FROM `class_student_grades`;
DELETE FROM `class_students`;
DELETE FROM `class_subjects`;
DELETE FROM `class_adviser_schedules`;
DELETE FROM `student_class`;
DELETE FROM `student_access`;

-- Core school records.
DELETE FROM `classes`;
DELETE FROM `college_programs`;
DELETE FROM `students`;
DELETE FROM `advisers`;
DELETE FROM `subjects`;
DELETE FROM `grade`;
DELETE FROM `school_year`;
DELETE FROM `announcements`;

-- Demo/framework records. This also signs out all active sessions.
DELETE FROM `notifications`;
DELETE FROM `password_reset_tokens`;
DELETE FROM `sessions`;
DELETE FROM `users`;
DELETE FROM `jobs`;
DELETE FROM `failed_jobs`;
DELETE FROM `job_batches`;
DELETE FROM `cache`;
DELETE FROM `cache_locks`;

-- Keep only the expected admin login. Match by role and username, not by ID.
DELETE FROM `moonshine_users`
WHERE NOT (
    `username` = 'admin'
    AND `moonshine_user_role_id` = (
        SELECT `admin_role`.`id`
        FROM `moonshine_user_roles` AS `admin_role`
        WHERE `admin_role`.`name` = 'Admin'
        LIMIT 1
    )
);

-- Clear seeded school identity while retaining app configuration rows.
UPDATE `settings`
SET `settingValue` = '',
    `settingFileValue` = NULL,
    `settingJSONValue` = NULL,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `settingName` IN (
    'school_name',
    'school_shortname',
    'school_logo',
    'school_id',
    'school_region',
    'school_district',
    'school_division'
);

-- Replace the public seed token with a new random 32-character token.
UPDATE `settings`
SET `settingValue` = REPLACE(UUID(), '-', ''),
    `updated_at` = CURRENT_TIMESTAMP
WHERE `settingName` = 'api_authcode';

COMMIT;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
DROP TEMPORARY TABLE IF EXISTS `cleanup_admin_guard`;

-- Verification: this should return exactly one row (the admin).
SELECT `id`, `name`, `username`, `email`
FROM `moonshine_users`
ORDER BY `id`;

