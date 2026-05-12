-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2026 at 07:19 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ams_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `course_id`, `title`, `description`, `due_date`, `created_at`) VALUES
(1, 1, 'workshop1', '', '2026-04-16 23:59:00', '2026-04-04 11:01:09'),
(2, 1, 'workshop1', '', '2026-04-16 23:59:00', '2026-04-04 11:01:22'),
(3, 1, 'sss', '', '2026-04-11 23:59:00', '2026-04-04 15:38:20'),
(4, 5, 'Tutorial1', '', '2026-04-11 23:59:00', '2026-04-04 17:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `course_id`, `attendance_date`, `status`) VALUES
(5, 1, 1, '2026-04-04', 'Late'),
(6, 2, 1, '2026-04-04', 'Present'),
(7, 1, 1, '2026-04-17', 'Absent'),
(9, 2, 1, '2026-04-17', 'Late'),
(10, 3, 5, '2026-04-17', 'Present'),
(11, 4, 5, '2026-04-17', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_code`, `teacher_id`) VALUES
(1, 'Collaborative Development', 'Cs101', 1),
(3, 'Cloud System', 'Cs102', 2),
(5, 'Algorithms and concurrency', 'cs103', 2);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`, `enrolled_at`) VALUES
(1, 1, 1, '2026-04-04 10:04:43'),
(3, 2, 1, '2026-04-04 16:13:47'),
(5, 3, 5, '2026-04-04 17:05:32'),
(6, 4, 5, '2026-04-10 02:27:51'),
(7, 1, 3, '2026-04-18 12:05:13'),
(8, 3, 3, '2026-04-18 12:05:20'),
(9, 4, 3, '2026-04-18 12:05:24'),
(11, 3, 1, '2026-04-18 12:06:33'),
(12, 4, 1, '2026-04-18 12:06:37'),
(14, 5, 1, '2026-04-18 13:05:28');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `class_name`) VALUES
(1, 2, '7'),
(2, 7, '11'),
(3, 9, '11'),
(4, 10, '11'),
(5, 11, '7');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `department`) VALUES
(1, 2, NULL),
(2, 3, 'collaboration ');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','On Leave') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `attendance_date`, `status`) VALUES
(1, 2, '2026-04-04', 'Present'),
(2, 1, '2026-04-04', 'Present'),
(3, 1, '2026-04-07', 'Late'),
(4, 2, '2026-04-07', 'Absent'),
(6, 1, '2026-04-17', 'Present'),
(9, 2, '2026-04-17', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Teacher','Student') NOT NULL,
  `contact` varchar(20) NOT NULL,
  `mother_name` varchar(100) DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `dob`, `address`, `email`, `password_hash`, `role`, `contact`, `mother_name`, `father_name`, `guardian_contact`, `is_active`, `otp_code`, `otp_expires_at`, `reset_token`, `reset_expires_at`, `created_at`) VALUES
(1, 'System', 'Admin', '1990-01-01', 'AMS H0', 'admin123@gmail.com', '$2y$12$uCEntgbcR755oUhB/hVTteRqS799.UXKuQ70EUuQPp2WWxE9QLF9i', 'Admin', '0000000000', 'Admin Mother', 'Admin Father', '0000000000', 1, NULL, NULL, NULL, NULL, '2026-04-03 17:04:24'),
(2, 'Manavi', 'Rai', '2025-02-03', '', 'raimanavi66@gmail.com', '$2y$12$Kod0A4ArN2BYrAQOphBUFOMIwVwu8KMjA2W.5KK8iHL6T7pL59mlO', 'Student', '9800000001', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-03 16:50:10'),
(3, 'Bibek', 'Jung', '1999-05-12', 'Balaju', 'bibek123@gmail.com', '$2y$12$vf2XnTmp1nIExqZ5jlVVaOPYyeEcTs28nnW0tbrITyN4i72nTaFpu', 'Teacher', '9888776655', 'sita', 'Hari', '987777777777', 1, NULL, NULL, NULL, NULL, '2026-04-03 16:54:02'),
(7, 'Maria ', 'Rai', '2005-03-11', '', 'maria@123', '$2y$10$vx60vdccnmAcbwR9A/L04.9UCvHshAUP.ZOkQP06/OAA.os0shSbO', 'Student', '', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-04 15:56:22'),
(8, 'Sarayu', 'Gautam', '1996-10-11', '', 'sarayu123@gmail.com', '$2y$12$rbh7prQDD6CbvnQoDlq59u96L2HoF0.HyOM4wP41eb2/9XbeIr2u2', 'Teacher', '9876543211', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-04 16:19:18'),
(9, 'Pema', 'Tamang', '2005-03-11', '', 'pema123@gmail.com', '$2y$12$KTQ/ewqcuKr/Thkrl.YlN.5imTKv0e/4ObIVpwPbGAnjhR2WOpvXS', 'Student', '9876543212', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-04 17:01:25'),
(10, 'Priyanka', 'Moktan', '2005-09-07', 'Boudha', 'priyankamoktan19@gmail.com', '$2y$10$ZEdxO6mG0pT7MWdL4eus0O6EMf094E7QnEA98CKGGNaalyE7vDbiW', 'Student', '9808080808', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-07 01:43:36'),
(11, 'Arshiya', 'Magar', '2006-06-06', 'Naxal', 'arshiyarana90@gmail.com', '$2y$12$v3AFzgyd0wpCSMWOmUQWa.vVS/RtEfNxoski5tDLbG36GQ5aIjWmi', 'Student', '9887878787', 'Maya', 'Mayan', '9812312312', 1, NULL, NULL, NULL, NULL, '2026-04-18 12:58:08');

-- --------------------------------------------------------

-- Sprint 2 Additional Tables for Academic Management System

-- Notices Table
CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('General','Exam','Assignment','Holiday','Event') DEFAULT 'General',
  `target_audience` enum('All','Students','Teachers','Admin') DEFAULT 'All',
  `posted_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exams Table
CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `exam_type` enum('Midterm','Final','Quiz','Assignment','Practical') DEFAULT 'Midterm',
  `max_marks` decimal(10,2) DEFAULT 100.00,
  `exam_date` datetime NOT NULL,
  `duration` int(11) DEFAULT 60 COMMENT 'Duration in minutes',
  `venue` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Marks Table
CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(10,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- GPA Table
CREATE TABLE `gpa` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `total_credit_hours` decimal(10,2) DEFAULT 0.00,
  `total_grade_points` decimal(10,2) DEFAULT 0.00,
  `gpa` decimal(3,2) DEFAULT 0.00,
  `cgpa` decimal(3,2) DEFAULT 0.00,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Course Credits Table (for GPA calculation)
CREATE TABLE `course_credits` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `credit_hours` decimal(3,2) DEFAULT 3.00,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Exam Schedule Conflicts Table (for Clash Detection)
CREATE TABLE `exam_conflicts` (
  `id` int(11) NOT NULL,
  `exam1_id` int(11) NOT NULL,
  `exam2_id` int(11) NOT NULL,
  `conflict_type` enum('Same Time','Same Venue','Overlapping Time') DEFAULT 'Same Time',
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`attendance_date`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_attendance` (`teacher_id`,`attendance_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for new tables
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `is_active` (`is_active`);

ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `exam_date` (`exam_date`),
  ADD KEY `is_active` (`is_active`);

ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `graded_by` (`graded_by`);

ALTER TABLE `gpa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_year_semester` (`student_id`,`academic_year`,`semester`),
  ADD KEY `student_id` (`student_id`);

ALTER TABLE `course_credits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_course_year_semester` (`course_id`,`academic_year`,`semester`),
  ADD KEY `course_id` (`course_id`);

ALTER TABLE `exam_conflicts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam1_id` (`exam1_id`),
  ADD KEY `exam2_id` (`exam2_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for new tables
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `gpa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `course_credits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `exam_conflicts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Foreign Key Constraints for new tables
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `gpa`
  ADD CONSTRAINT `gpa_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

ALTER TABLE `course_credits`
  ADD CONSTRAINT `course_credits_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

ALTER TABLE `exam_conflicts`
  ADD CONSTRAINT `exam_conflicts_ibfk_1` FOREIGN KEY (`exam1_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_conflicts_ibfk_2` FOREIGN KEY (`exam2_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

-- Sample Data for Sprint 2 Tables
INSERT INTO `notices` (`title`, `description`, `type`, `target_audience`, `posted_by`) VALUES
('Midterm Examination Schedule', 'Midterm examinations will start from next week. Please check your exam schedules.', 'Exam', 'All', 1),
('System Maintenance Notice', 'The system will be under maintenance this weekend.', 'General', 'All', 1),
('Assignment Submission Reminder', 'Please submit your pending assignments before the deadline.', 'Assignment', 'Students', 1);

INSERT INTO `course_credits` (`course_id`, `credit_hours`, `academic_year`, `semester`) VALUES
(1, 3.00, '2026', 'Spring'),
(3, 4.00, '2026', 'Spring'),
(5, 3.00, '2026', 'Spring');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
