-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 15, 2025 at 02:14 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u810471454_jzgmnhsportal`
--

-- --------------------------------------------------------

--
-- Table structure for table `advisers`
--

CREATE TABLE `advisers` (
  `id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `rank` varchar(200) NOT NULL,
  `major` varchar(200) NOT NULL,
  `profile_photo` varchar(200) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `expiry_date` datetime DEFAULT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_record`
--

CREATE TABLE `attendance_record` (
  `id` int(11) NOT NULL,
  `student_id` int(20) NOT NULL,
  `amlogin` time NOT NULL,
  `amlogout` time NOT NULL,
  `pmlogin` time NOT NULL,
  `pmlogout` time NOT NULL,
  `currentdate` date NOT NULL,
  `logged_time` time DEFAULT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(50) NOT NULL,
  `adviser_id` int(50) NOT NULL,
  `grade_id` int(10) NOT NULL,
  `section` varchar(200) NOT NULL,
  `school_year_id` int(10) NOT NULL,
  `status` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_adviser_schedules`
--

CREATE TABLE `class_adviser_schedules` (
  `id` int(11) NOT NULL,
  `adviser_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `section` varchar(100) NOT NULL,
  `time_frame` varchar(100) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `id` int(10) NOT NULL,
  `class_id` int(10) NOT NULL,
  `student_id` int(10) NOT NULL,
,
  `school_year_id` int(11) NOT NULL,
  `hidden_grade` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_student_grades`
--

CREATE TABLE `class_student_grades` (
  `id` int(11) NOT NULL,
  `class_id` int(10) NOT NULL,
  `student_id` int(10) NOT NULL,
  `grade_id` int(50) NOT NULL,
  `subject_id` int(50) NOT NULL,
  `q1` int(50) NOT NULL,
  `q2` int(50) NOT NULL,
  `q3` int(50) NOT NULL,
  `q4` int(50) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(10) NOT NULL,
  `class_id` int(10) NOT NULL,
  `subject_id` int(10) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade`
--

CREATE TABLE `grade` (
  `id` int(10) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `status` varchar(10) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_answers`
--

CREATE TABLE `quiz_answers` (
  `id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_group`
--

CREATE TABLE `quiz_group` (
  `id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `grade_id` int(11) NOT NULL,
  `week` text NOT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_group_days`
--

CREATE TABLE `quiz_group_days` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `quiz_group_id` int(11) NOT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `quiz_duration_seconds` int(11) NOT NULL,
  `record_created` timestamp NULL DEFAULT current_timestamp(),
  `record_updated` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_quiz_answers`
--

CREATE TABLE `quiz_quiz_answers` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `answer_id` int(11) DEFAULT NULL,
  `is_correct_answer` tinyint(1) DEFAULT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_quiz_group_days`
--

CREATE TABLE `quiz_quiz_group_days` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `quiz_group_days_id` int(11) NOT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_year`
--

CREATE TABLE `school_year` (
  `id` int(10) NOT NULL,
  `school_year` varchar(10) NOT NULL,
  `active` tinyint(1) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `settingName` varchar(200) NOT NULL,
  `settingValue` varchar(1000) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(50) NOT NULL,
  `user_id` int(10) NOT NULL,
  `lrn` varchar(15) NOT NULL,
  `lastname` varchar(30) NOT NULL,
  `firstname` varchar(30) NOT NULL,
  `middlename` varchar(30) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(20) NOT NULL,
  `birthplace` varchar(50) NOT NULL,
  `profile_photo` varchar(200) NOT NULL,
  `parent_guardian` varchar(50) NOT NULL,
  `parent_guardian_address` varchar(60) NOT NULL,
  `parent_guardian_relationship` varchar(200) NOT NULL,
  `is_4ps_member` tinyint(1) NOT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `form137path` varchar(200) DEFAULT NULL,
  `elementary_school_name` varchar(200) DEFAULT NULL,
  `elementary_school_id` varchar(100) DEFAULT NULL,
  `elementary_school_address` varchar(300) DEFAULT NULL,
  `elementary_school_grade` varchar(10) DEFAULT NULL,
  `elementary_school_citation` varchar(10) DEFAULT NULL,
  `deworming_grade_7` tinyint(1) DEFAULT NULL,
  `deworming_grade_8` tinyint(1) DEFAULT NULL,
  `deworming_grade_9` tinyint(1) DEFAULT NULL,
  `deworming_grade_10` tinyint(1) DEFAULT NULL,
,
  `archived` bit(1) DEFAULT NULL,
  `archive_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_access`
--

CREATE TABLE `student_access` (
  `id` int(10) NOT NULL,
  `student_id` int(10) NOT NULL,
  `user_id` tinyint(1) NOT NULL,
  `active` int(11) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_class`
--

CREATE TABLE `student_class` (
  `id` int(11) NOT NULL,
  `student_id` int(50) NOT NULL,
  `grade_id` int(50) NOT NULL,
  `school_year` varchar(200) NOT NULL,
  `section` varchar(200) NOT NULL,
  `status` varchar(50) NOT NULL,

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_quiz_answers`
--

CREATE TABLE `student_quiz_answers` (
  `id` int(11) NOT NULL,
  `quiz_group_days_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `answer_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `record_created` datetime DEFAULT current_timestamp(),
  `record_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `record_deleted` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject` varchar(50) NOT NULL,
  `include_in_average` bit(1) NOT NULL,
  `record_order` int(10) DEFAULT NULL,
  `record_orders` int(10) DEFAULT 0,

) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advisers`
--
ALTER TABLE `advisers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attendance_record_student_id` (`student_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_adviser_schedules`
--
ALTER TABLE `class_adviser_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adviser_id` (`adviser_id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_class_students_class_id` (`class_id`),
  ADD KEY `fk_class_students_student_id` (`student_id`);

--
-- Indexes for table `class_student_grades`
--
ALTER TABLE `class_student_grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_class_student_grades_class_id` (`class_id`),
  ADD KEY `fk_class_student_grades_student_id` (`student_id`),
  ADD KEY `fk_class_student_grades_grade_id` (`grade_id`),
  ADD KEY `fk_class_student_grades_subject_id` (`subject_id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_class_subjects_class_id` (`class_id`),
  ADD KEY `fk_class_subjects_subject_id` (`subject_id`);

--
-- Indexes for table `grade`
--
ALTER TABLE `grade`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quiz_group`
--
ALTER TABLE `quiz_group`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `grade_id` (`grade_id`);

--
-- Indexes for table `quiz_group_days`
--
ALTER TABLE `quiz_group_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_group_id` (`quiz_group_id`);

--
-- Indexes for table `quiz_quiz_answers`
--
ALTER TABLE `quiz_quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `answer_id` (`answer_id`);

--
-- Indexes for table `quiz_quiz_group_days`
--
ALTER TABLE `quiz_quiz_group_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_group_days_id` (`quiz_group_days_id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `school_year`
--
ALTER TABLE `school_year`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_class`
--
ALTER TABLE `student_class`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_quiz_answers`
--
ALTER TABLE `student_quiz_answers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advisers`
--
ALTER TABLE `advisers`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_record`
--
ALTER TABLE `attendance_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_adviser_schedules`
--
ALTER TABLE `class_adviser_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_students`
--
ALTER TABLE `class_students`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_student_grades`
--
ALTER TABLE `class_student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade`
--
ALTER TABLE `grade`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_answers`
--
ALTER TABLE `quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_group`
--
ALTER TABLE `quiz_group`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_group_days`
--
ALTER TABLE `quiz_group_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_quiz_answers`
--
ALTER TABLE `quiz_quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_quiz_group_days`
--
ALTER TABLE `quiz_quiz_group_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_year`
--
ALTER TABLE `school_year`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_class`
--
ALTER TABLE `student_class`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_quiz_answers`
--
ALTER TABLE `student_quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_record`
--
ALTER TABLE `attendance_record`
  ADD CONSTRAINT `fk_attendance_record_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `class_adviser_schedules`
--
ALTER TABLE `class_adviser_schedules`
  ADD CONSTRAINT `class_adviser_schedules_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `advisers` (`id`);

--
-- Constraints for table `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `fk_class_students_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_class_students_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`);

--
-- Constraints for table `class_student_grades`
--
ALTER TABLE `class_student_grades`
  ADD CONSTRAINT `fk_class_student_grades_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_class_student_grades_grade_id` FOREIGN KEY (`grade_id`) REFERENCES `grade` (`id`),
  ADD CONSTRAINT `fk_class_student_grades_student_id` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `fk_class_student_grades_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `fk_class_subjects_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `fk_class_subjects_subject_id` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `quiz_group`
--
ALTER TABLE `quiz_group`
  ADD CONSTRAINT `quiz_group_ibfk_1` FOREIGN KEY (`school_year_id`) REFERENCES `school_year` (`id`),
  ADD CONSTRAINT `quiz_group_ibfk_2` FOREIGN KEY (`grade_id`) REFERENCES `grade` (`id`);

--
-- Constraints for table `quiz_group_days`
--
ALTER TABLE `quiz_group_days`
  ADD CONSTRAINT `quiz_group_days_ibfk_1` FOREIGN KEY (`quiz_group_id`) REFERENCES `quiz_group` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_quiz_answers`
--
ALTER TABLE `quiz_quiz_answers`
  ADD CONSTRAINT `quiz_quiz_answers_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`),
  ADD CONSTRAINT `quiz_quiz_answers_ibfk_2` FOREIGN KEY (`answer_id`) REFERENCES `quiz_answers` (`id`);

--
-- Constraints for table `quiz_quiz_group_days`
--
ALTER TABLE `quiz_quiz_group_days`
  ADD CONSTRAINT `quiz_quiz_group_days_ibfk_1` FOREIGN KEY (`quiz_group_days_id`) REFERENCES `quiz_group_days` (`id`),
  ADD CONSTRAINT `quiz_quiz_group_days_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
