-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 10:02 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `assignment_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `total_marks` int(11) DEFAULT 100,
  `due_date` datetime NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment`
--

INSERT INTO `assignment` (`assignment_id`, `title`, `description`, `course_id`, `total_marks`, `due_date`, `created_by`, `created_at`) VALUES
(1, 'tutorial1', '', 3, 100, '2026-05-16 23:45:00', 9, '2026-05-13 17:49:50'),
(2, 'TEST', 'SUBMIT TEST', 5, 100, '2026-05-24 10:11:00', 9, '2026-05-14 04:27:04'),
(3, 'workshop2', '', 3, 98, '2026-05-16 23:45:00', 9, '2026-05-14 16:28:44'),
(4, 'assignment1', '', 3, 100, '2026-05-31 23:45:00', 9, '2026-05-14 18:19:08');

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
(7, 1, 1, '2026-04-17', 'Absent'),
(10, 3, 5, '2026-04-17', 'Present'),
(11, 4, 5, '2026-04-17', 'Present'),
(14, 1, 3, '2026-05-05', 'Present'),
(15, 3, 3, '2026-05-05', 'Present'),
(16, 4, 3, '2026-05-05', 'Present'),
(17, 5, 1, '2026-05-15', 'Absent'),
(20, 5, 3, '2026-05-21', 'Present'),
(21, 5, 1, '2026-05-21', 'Absent'),
(22, 1, 3, '2026-05-21', 'Present'),
(23, 1, 1, '2026-05-21', 'Present'),
(24, 1, 1, '2026-05-20', 'Absent'),
(25, 5, 3, '2026-05-20', 'Present'),
(26, 5, 3, '2026-05-19', 'Absent'),
(27, 5, 1, '2026-05-19', 'Present'),
(30, 1, 1, '2026-05-19', 'Absent'),
(31, 6, 1, '2026-05-19', 'Absent'),
(35, 3, 1, '2026-05-21', 'Present'),
(36, 6, 1, '2026-05-21', 'Present'),
(39, 3, 5, '2026-05-21', 'Present'),
(40, 4, 5, '2026-05-21', 'Present'),
(47, 3, 3, '2026-05-21', 'Absent'),
(48, 4, 3, '2026-05-21', 'Present'),
(53, 4, 5, '2026-05-20', 'Present'),
(54, 4, 1, '2026-05-21', 'Present'),
(58, 5, 3, '2026-05-22', 'Absent'),
(61, 5, 1, '2026-05-22', 'Present'),
(62, 5, 3, '2026-05-06', 'Absent'),
(63, 5, 1, '2026-04-28', 'Present'),
(64, 5, 1, '2026-04-27', 'Present'),
(65, 5, 1, '2026-05-05', 'Absent'),
(67, 4, 5, '2026-05-05', 'Present'),
(68, 3, 5, '2026-05-22', 'Absent'),
(69, 4, 5, '2026-05-22', 'Present'),
(71, 1, 3, '2026-05-22', 'Late'),
(72, 3, 3, '2026-05-22', 'Absent'),
(73, 4, 3, '2026-05-22', 'Present');

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
(5, 'Algorithms', 'cs1033', 2),
(6, 'ISA', 'Cs104', 1),
(7, 'Marth', 'Cs105', 1),
(8, 'Full stack', 'Cs106', 2),
(9, 'Java', 'Cs107', 1),
(11, 'kkk', 'Cs108', 1),
(13, 'test', 't123', 1);

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
(5, 3, 5, '2026-04-04 17:05:32'),
(6, 4, 5, '2026-04-10 02:27:51'),
(7, 1, 3, '2026-04-18 12:05:13'),
(8, 3, 3, '2026-04-18 12:05:20'),
(9, 4, 3, '2026-04-18 12:05:24'),
(11, 3, 1, '2026-04-18 12:06:33'),
(12, 4, 1, '2026-04-18 12:06:37'),
(14, 5, 1, '2026-04-18 13:05:28'),
(16, 5, 3, '2026-05-14 18:49:31'),
(18, 6, 1, '2026-05-14 19:08:28'),
(19, 9, 1, '2026-05-21 12:42:19');

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exam` (
  `id` int(11) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `total_marks` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam`
--

INSERT INTO `exam` (`id`, `exam_name`, `course_id`, `exam_date`, `start_time`, `end_time`, `total_marks`, `created_at`) VALUES
(1, 'Semester3', 1, '2026-09-22', '13:00:00', '15:00:00', 100, '2026-05-13 11:48:33'),
(2, 'Semester2', 8, '2026-09-22', '12:00:00', '14:00:00', 100, '2026-05-13 14:38:25'),
(3, 'Semester2', 3, '2026-08-22', '08:00:00', '10:00:00', 70, '2026-05-13 14:45:08'),
(4, 'Semester1', 3, '2026-08-22', '14:00:00', '16:00:00', 100, '2026-05-13 14:48:47'),
(5, 'Semester4', 3, '2026-08-22', '10:00:00', '12:00:00', 100, '2026-05-13 14:51:38'),
(7, 'Mid Term Exam', 3, '2026-05-17', '10:00:00', '12:00:00', 100, '2026-05-14 18:43:21'),
(8, 'test', 13, '2026-05-31', '15:30:00', '19:30:00', 40, '2026-05-22 07:44:25');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `entered_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `exam_id`, `student_id`, `course_id`, `marks_obtained`, `grade`, `remarks`, `entered_by`, `created_at`) VALUES
(1, 1, 5, 1, 60.00, 'D', '', 3, '2026-05-13 12:05:18'),
(2, 1, 1, 1, 98.00, 'A', '', 3, '2026-05-13 12:05:18'),
(4, 1, 3, 1, 31.00, 'F', '', 3, '2026-05-13 12:05:18'),
(5, 1, 4, 1, 33.00, 'F', '', 3, '2026-05-13 12:05:18'),
(26, 5, 1, 3, 27.00, 'F', '', 9, '2026-05-14 18:39:48'),
(27, 3, 5, 3, 58.00, 'B', '', 9, '2026-05-22 07:54:27'),
(28, 3, 1, 3, 70.00, 'A', '', 9, '2026-05-22 07:54:27'),
(29, 3, 3, 3, 40.00, 'F', '', 9, '2026-05-22 07:54:27'),
(30, 3, 4, 3, 70.00, 'A', '', 9, '2026-05-22 07:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

CREATE TABLE `notice` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('Normal','Urgent') DEFAULT 'Normal',
  `target_audience` enum('All','Student','Teacher') DEFAULT 'All',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `publish_date` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notice`
--

INSERT INTO `notice` (`id`, `title`, `content`, `priority`, `target_audience`, `created_by`, `created_at`, `publish_date`) VALUES
(1, 'HOLIDAY NOTICE', 'Hello Everyone. There Will be Holiday on 13 May, Wednesday for Exam Preparation.Thankyou.', '', 'All', 4, '2026-05-12 14:39:49', '2026-05-13'),
(3, 'INDRUSTY VISIT', 'Hello Everyone. There will be Indrusty visit on May 15, Friday. So ALL STUDENTS ARE REQIURED TO BRING ID CARDS.Thankyou....', 'Urgent', 'All', 4, '2026-05-13 14:26:12', '2026-05-13'),
(4, 'ccccccccccccccknj', 'njhbjyujyt', 'Normal', 'Student', 4, '2026-05-14 16:34:11', '2026-05-14'),
(5, 'ddddd', 'aa', 'Urgent', 'All', 4, '2026-05-14 18:42:46', '2026-05-15'),
(7, 'hello', 'ssss', 'Urgent', 'All', 4, '2026-05-22 07:49:15', '2026-05-22');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('System','Academic','Security','Attendance') DEFAULT 'System',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_urgent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `is_urgent`) VALUES
(2, 4, 'New Course Added: ISA', 'A new course (Cs104) has been added to the system curriculum.', 'System', 1, '2026-05-12 14:23:57', 0),
(4, 4, 'New Course Added: Marth', 'A new course (Cs105) has been added to the system curriculum.', 'System', 1, '2026-05-12 14:28:22', 0),
(5, 3, 'New Course Assigned: Marth', 'You have been assigned as the lead instructor for the new course: Marth (Cs105).', 'Academic', 0, '2026-05-12 14:28:22', 0),
(6, 2, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Collaborative Development on 2026-09-22 from 13:00 to 15:00.', 'Academic', 1, '2026-05-13 11:48:33', 0),
(8, 10, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Collaborative Development on 2026-09-22 from 13:00 to 15:00.', 'Academic', 0, '2026-05-13 11:48:33', 0),
(9, 11, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Collaborative Development on 2026-09-22 from 13:00 to 15:00.', 'Academic', 0, '2026-05-13 11:48:33', 0),
(10, 12, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Collaborative Development on 2026-09-22 from 13:00 to 15:00.', 'Academic', 0, '2026-05-13 11:48:33', 0),
(11, 4, 'New Course Added: Full stack', 'A new course (Cs106) has been added to the system curriculum.', 'System', 1, '2026-05-13 11:56:57', 0),
(12, 9, 'New Course Assigned: Full stack', 'You have been assigned as the lead instructor for the new course: Full stack (Cs106).', 'Academic', 1, '2026-05-13 11:56:57', 0),
(13, 12, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:18', 0),
(14, 2, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: A', 'Academic', 1, '2026-05-13 12:05:18', 0),
(16, 10, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:18', 0),
(17, 11, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:18', 0),
(18, 12, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:25', 0),
(19, 2, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: A', 'Academic', 1, '2026-05-13 12:05:25', 0),
(21, 10, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:25', 0),
(22, 11, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:25', 0),
(23, 12, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:48', 0),
(24, 2, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: A', 'Academic', 1, '2026-05-13 12:05:48', 0),
(26, 10, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:48', 0),
(27, 11, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:05:48', 0),
(28, 12, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: D', 'Academic', 0, '2026-05-13 12:06:02', 0),
(29, 2, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: A', 'Academic', 1, '2026-05-13 12:06:02', 0),
(31, 10, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:06:02', 0),
(32, 11, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:06:02', 0),
(33, 12, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: D', 'Academic', 0, '2026-05-13 12:06:09', 0),
(34, 2, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: A', 'Academic', 1, '2026-05-13 12:06:09', 0),
(36, 10, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:06:09', 0),
(37, 11, 'Exam Result Published', 'Your results for \'Semester3\' have been published. Grade: F', 'Academic', 0, '2026-05-13 12:06:09', 0),
(38, 4, 'New Course Added: Java', 'A new course (Cs107) has been added to the system curriculum.', 'System', 1, '2026-05-13 14:23:11', 0),
(39, 3, 'New Course Assigned: Java', 'You have been assigned as the lead instructor for the new course: Java (Cs107).', 'Academic', 0, '2026-05-13 14:23:11', 0),
(40, 2, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 08:00 to 10:00.', 'Academic', 1, '2026-05-13 14:45:08', 0),
(41, 10, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 08:00 to 10:00.', 'Academic', 0, '2026-05-13 14:45:08', 0),
(42, 11, 'New Exam Scheduled: Semester2', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 08:00 to 10:00.', 'Academic', 0, '2026-05-13 14:45:08', 0),
(43, 2, 'New Exam Scheduled: Semester1', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 14:00 to 16:00.', 'Academic', 1, '2026-05-13 14:48:47', 0),
(44, 10, 'New Exam Scheduled: Semester1', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 14:00 to 16:00.', 'Academic', 0, '2026-05-13 14:48:47', 0),
(45, 11, 'New Exam Scheduled: Semester1', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 14:00 to 16:00.', 'Academic', 0, '2026-05-13 14:48:47', 0),
(46, 2, 'New Exam Scheduled: Semester4', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 10:00 to 12:00.', 'Academic', 1, '2026-05-13 14:51:38', 0),
(47, 10, 'New Exam Scheduled: Semester4', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 10:00 to 12:00.', 'Academic', 0, '2026-05-13 14:51:38', 0),
(48, 11, 'New Exam Scheduled: Semester4', 'A new exam has been scheduled for Cloud System on 2026-08-22 from 10:00 to 12:00.', 'Academic', 0, '2026-05-13 14:51:38', 0),
(49, 9, 'New Exam Scheduled: Semester4', 'An examination (Semester4) has been scheduled for your module Cloud System.', 'Academic', 1, '2026-05-13 14:51:38', 0),
(50, 2, 'New Resource Available', 'A new study material \'lecture\' has been uploaded for Cloud System.', 'Academic', 1, '2026-05-13 15:13:50', 0),
(51, 10, 'New Resource Available', 'A new study material \'lecture\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-13 15:13:50', 0),
(52, 11, 'New Resource Available', 'A new study material \'lecture\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-13 15:13:50', 0),
(53, 10, 'New Resource Available', 'A new study material \'fffff\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-13 17:04:41', 0),
(54, 11, 'New Resource Available', 'A new study material \'fffff\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-13 17:04:41', 0),
(55, 10, 'New Resource Available', 'A new study material \'ccc\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-13 17:19:09', 0),
(56, 11, 'New Resource Available', 'A new study material \'ccc\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-13 17:19:09', 0),
(57, 2, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-05 has been marked as: Present.', 'Attendance', 1, '2026-05-14 04:27:22', 0),
(58, 10, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-05 has been marked as: Present.', 'Attendance', 0, '2026-05-14 04:27:22', 0),
(59, 11, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-05 has been marked as: Present.', 'Attendance', 0, '2026-05-14 04:27:22', 0),
(60, 3, 'New Exam Scheduled: Semester2', 'An examination (Semester2) has been scheduled for your module ISA.', 'Academic', 0, '2026-05-14 16:22:23', 0),
(61, 2, 'New Notice: ccccccccccccccknj', 'njhbjyujyt', 'System', 1, '2026-05-14 16:34:11', 0),
(63, 10, 'New Notice: ccccccccccccccknj', 'njhbjyujyt', 'System', 0, '2026-05-14 16:34:11', 0),
(64, 11, 'New Notice: ccccccccccccccknj', 'njhbjyujyt', 'System', 0, '2026-05-14 16:34:11', 0),
(65, 12, 'New Notice: ccccccccccccccknj', 'njhbjyujyt', 'System', 0, '2026-05-14 16:34:11', 0),
(66, 13, 'New Notice: ccccccccccccccknj', 'njhbjyujyt', 'System', 1, '2026-05-14 16:34:11', 0),
(67, 2, 'Exam Result Published', 'Your results for \'Semester4\' have been published. Grade: F', 'Academic', 1, '2026-05-14 18:39:48', 0),
(68, 2, 'New Notice: ddddd', 'aa', 'System', 1, '2026-05-14 18:42:46', 0),
(70, 10, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(71, 11, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(72, 12, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(73, 13, 'New Notice: ddddd', 'aa', 'System', 1, '2026-05-14 18:42:46', 0),
(74, 15, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(75, 17, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(76, 3, 'New Notice: ddddd', 'aa', 'System', 0, '2026-05-14 18:42:46', 0),
(77, 9, 'New Notice: ddddd', 'aa', 'System', 1, '2026-05-14 18:42:46', 0),
(78, 2, 'New Exam Scheduled: ss', 'A new exam has been scheduled for Cloud System on 2026-05-17 from 10:00 to 12:00.', 'Academic', 1, '2026-05-14 18:43:21', 0),
(79, 10, 'New Exam Scheduled: ss', 'A new exam has been scheduled for Cloud System on 2026-05-17 from 10:00 to 12:00.', 'Academic', 0, '2026-05-14 18:43:21', 0),
(80, 11, 'New Exam Scheduled: ss', 'A new exam has been scheduled for Cloud System on 2026-05-17 from 10:00 to 12:00.', 'Academic', 0, '2026-05-14 18:43:21', 0),
(81, 9, 'New Exam Scheduled: ss', 'An examination (ss) has been scheduled for your module Cloud System.', 'Academic', 1, '2026-05-14 18:43:21', 0),
(82, 4, 'New Enrollment: Arshiya Magar', 'Student Arshiya Magar has been successfully enrolled in Cloud System.', 'System', 1, '2026-05-14 18:49:31', 0),
(83, 9, 'New Student Enrolled', 'Student Arshiya Magar has joined your module: Cloud System.', 'Academic', 1, '2026-05-14 18:49:31', 0),
(84, 12, 'Enrolled in Cloud System', 'You have been successfully enrolled in the Cloud System course.', 'Academic', 0, '2026-05-14 18:49:31', 0),
(85, 4, 'New Course Added: kkk', 'A new course (Cs108) has been added to the system curriculum.', 'System', 1, '2026-05-14 18:50:48', 0),
(86, 3, 'New Course Assigned: kkk', 'You have been assigned as the lead instructor for the new course: kkk (Cs108).', 'Academic', 0, '2026-05-14 18:50:48', 0),
(87, 4, 'New Course Added: fff', 'A new course (Cs109) has been added to the system curriculum.', 'System', 1, '2026-05-14 19:07:31', 0),
(88, 3, 'New Course Assigned: fff', 'You have been assigned as the lead instructor for the new course: fff (Cs109).', 'Academic', 0, '2026-05-14 19:07:31', 0),
(89, 4, 'New Enrollment: hellen Tamang', 'Student hellen Tamang has been successfully enrolled in Collaborative Development.', 'System', 1, '2026-05-14 19:08:28', 0),
(90, 3, 'New Student Enrolled', 'Student hellen Tamang has joined your module: Collaborative Development.', 'Academic', 0, '2026-05-14 19:08:28', 0),
(91, 13, 'Enrolled in Collaborative Development', 'You have been successfully enrolled in the Collaborative Development course.', 'Academic', 1, '2026-05-14 19:08:28', 0),
(92, 2, 'New Notice: nn', 'kugijh', 'System', 1, '2026-05-16 11:03:08', 0),
(94, 10, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(95, 11, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(96, 12, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(97, 13, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(98, 15, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(99, 17, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(100, 3, 'New Notice: nn', 'kugijh', 'System', 0, '2026-05-16 11:03:08', 0),
(101, 9, 'New Notice: nn', 'kugijh', 'System', 1, '2026-05-16 11:03:08', 0),
(102, 10, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Absent.', 'Attendance', 0, '2026-05-21 09:06:20', 0),
(103, 11, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:20', 0),
(104, 10, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Absent.', 'Attendance', 0, '2026-05-21 09:06:22', 0),
(105, 11, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:22', 0),
(106, 10, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:25', 0),
(107, 11, 'Attendance Marked: Algorithms and concurrency', 'Your attendance for Algorithms and concurrency on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:25', 0),
(108, 12, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:58', 0),
(109, 2, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 1, '2026-05-21 09:06:58', 0),
(110, 10, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Absent.', 'Attendance', 0, '2026-05-21 09:06:58', 0),
(111, 11, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:06:58', 0),
(112, 12, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:07:37', 0),
(113, 2, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 1, '2026-05-21 09:07:37', 0),
(114, 10, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Absent.', 'Attendance', 0, '2026-05-21 09:07:37', 0),
(115, 11, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-21 has been marked as: Present.', 'Attendance', 0, '2026-05-21 09:07:37', 0),
(116, 4, 'New Enrollment: kritika bhattarai', 'Student kritika bhattarai has been successfully enrolled in Collaborative Development.', 'System', 1, '2026-05-21 12:42:19', 0),
(117, 3, 'New Student Enrolled', 'Student kritika bhattarai has joined your module: Collaborative Development.', 'Academic', 0, '2026-05-21 12:42:19', 0),
(118, 19, 'Enrolled in Collaborative Development', 'You have been successfully enrolled in the Collaborative Development course.', 'Academic', 0, '2026-05-21 12:42:19', 0),
(121, 10, 'New Resource Available', 'A new study material \'tutorial 11\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-22 06:54:21', 0),
(122, 11, 'New Resource Available', 'A new study material \'tutorial 11\' has been uploaded for Algorithms and concurrency.', 'Academic', 0, '2026-05-22 06:54:21', 0),
(123, 2, 'New Resource Available', 'A new study material \'workshop11\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-22 06:59:30', 0),
(124, 10, 'New Resource Available', 'A new study material \'workshop11\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-22 06:59:30', 0),
(125, 11, 'New Resource Available', 'A new study material \'workshop11\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-22 06:59:30', 0),
(126, 12, 'New Resource Available', 'A new study material \'workshop11\' has been uploaded for Cloud System.', 'Academic', 0, '2026-05-22 06:59:30', 0),
(127, 9, 'Course Update: Algorithms', 'You have been assigned as the lead instructor for Algorithms (cs1033).', 'Academic', 0, '2026-05-22 07:09:05', 0),
(128, 10, 'Course Update: Algorithms', 'The instructor for Algorithms has been updated by the administration.', 'Academic', 0, '2026-05-22 07:09:05', 0),
(129, 11, 'Course Update: Algorithms', 'The instructor for Algorithms has been updated by the administration.', 'Academic', 0, '2026-05-22 07:09:05', 0),
(130, 4, 'New Course Added: test', 'A new course (t123) has been added to the system curriculum.', 'System', 1, '2026-05-22 07:09:29', 0),
(131, 3, 'New Course Assigned: test', 'You have been assigned as the lead instructor for the new course: test (t123).', 'Academic', 0, '2026-05-22 07:09:29', 0),
(132, 3, 'New Exam Scheduled: test', 'An examination (test) has been scheduled for your module test.', 'Academic', 0, '2026-05-22 07:44:25', 0),
(133, 9, 'Timetable Update: Cloud System', 'A new session has been scheduled for Cloud System on Sunday (Period 1) in hall4.', 'Academic', 0, '2026-05-22 07:47:16', 0),
(134, 2, 'Timetable Update: Cloud System', 'A new session has been scheduled for Cloud System on Sunday (Period 1) in hall4.', 'Academic', 0, '2026-05-22 07:47:16', 0),
(135, 10, 'Timetable Update: Cloud System', 'A new session has been scheduled for Cloud System on Sunday (Period 1) in hall4.', 'Academic', 0, '2026-05-22 07:47:16', 0),
(136, 11, 'Timetable Update: Cloud System', 'A new session has been scheduled for Cloud System on Sunday (Period 1) in hall4.', 'Academic', 0, '2026-05-22 07:47:16', 0),
(137, 12, 'Timetable Update: Cloud System', 'A new session has been scheduled for Cloud System on Sunday (Period 1) in hall4.', 'Academic', 0, '2026-05-22 07:47:16', 0),
(138, 2, 'New Notice: hello', 'ssss', 'System', 1, '2026-05-22 07:49:15', 1),
(139, 10, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(140, 11, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(141, 12, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(142, 13, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(143, 15, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(144, 17, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(145, 19, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(146, 21, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(147, 3, 'New Notice: hello', 'ssss', 'System', 0, '2026-05-22 07:49:15', 1),
(148, 9, 'New Notice: hello', 'ssss', 'System', 1, '2026-05-22 07:49:15', 1),
(149, 10, 'Attendance Marked: Algorithms', 'Your attendance for Algorithms on 2026-05-22 has been marked as: Absent.', 'Attendance', 0, '2026-05-22 07:49:41', 0),
(150, 11, 'Attendance Marked: Algorithms', 'Your attendance for Algorithms on 2026-05-22 has been marked as: Present.', 'Attendance', 0, '2026-05-22 07:49:41', 0),
(151, 12, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-22 has been marked as: Absent.', 'Attendance', 0, '2026-05-22 07:49:57', 0),
(152, 2, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-22 has been marked as: Late.', 'Attendance', 0, '2026-05-22 07:49:57', 0),
(153, 10, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-22 has been marked as: Absent.', 'Attendance', 0, '2026-05-22 07:49:57', 0),
(154, 11, 'Attendance Marked: Cloud System', 'Your attendance for Cloud System on 2026-05-22 has been marked as: Present.', 'Attendance', 0, '2026-05-22 07:49:57', 0),
(155, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:54:27', 0),
(156, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:54:27', 0),
(157, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:54:27', 0),
(158, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:54:27', 0),
(159, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: B', 'Academic', 0, '2026-05-22 07:55:28', 0),
(160, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:28', 0),
(161, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:28', 0),
(162, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:28', 0),
(163, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: B', 'Academic', 0, '2026-05-22 07:55:39', 0),
(164, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:55:39', 0),
(165, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:39', 0),
(166, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:39', 0),
(167, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: B', 'Academic', 0, '2026-05-22 07:55:44', 0),
(168, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:55:44', 0),
(169, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:44', 0),
(170, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:44', 0),
(174, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: B', 'Academic', 0, '2026-05-22 07:55:58', 0),
(175, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:55:58', 0),
(176, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:55:58', 0),
(177, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:55:58', 0),
(178, 12, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: B', 'Academic', 0, '2026-05-22 07:56:03', 0),
(179, 2, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:56:03', 0),
(180, 10, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: F', 'Academic', 0, '2026-05-22 07:56:03', 0),
(181, 11, 'Exam Result Published', 'Your results for \'Semester2\' have been published. Grade: A', 'Academic', 0, '2026-05-22 07:56:03', 0);

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`resource_id`, `title`, `description`, `course_id`, `file_name`, `file_path`, `file_type`, `uploaded_by`, `created_at`) VALUES
(1, 'lecturee', '', 3, 'Lecture1.pptx', 'uploads/resources/res_6a04952ea941f4.70831953.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 9, '2026-05-13 15:13:50'),
(4, 'tutorial 11', '', 5, 'MVP Sprint 1 Assessment FINAL (2026).docx', 'uploads/resources/res_6a100cb1c0e5c2.85095596.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 9, '2026-05-22 06:54:21');

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
(3, 10, '11'),
(4, 11, '11'),
(5, 12, '11'),
(6, 13, '11'),
(7, 15, NULL),
(8, 17, ''),
(9, 19, ''),
(10, 21, '11');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marks` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`submission_id`, `assignment_id`, `student_id`, `file_name`, `file_path`, `submitted_at`, `marks`, `feedback`) VALUES
(1, 1, 2, 'Tutorial 8  (1).docx', 'uploads/submissions/sub_6a04b9df368a2.docx', '2026-05-13 17:50:23', 30, ''),
(2, 3, 2, 'Tutorial9.docx_a9bad234-7f6e-4f22-a9a8-dc014f509d1d_90180_.pdf', 'uploads/submissions/sub_6a05f872b1a60.pdf', '2026-05-14 16:29:38', NULL, NULL),
(3, 4, 2, '2510725_ManaviRai.pdf', 'uploads/submissions/sub_6a06161c82928.pdf', '2026-05-14 18:36:12', NULL, NULL);

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
(1, 3, 'collaborative development'),
(2, 9, 'Cloud System');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','On Leave') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_attendance`
--

INSERT INTO `teacher_attendance` (`id`, `teacher_id`, `course_id`, `attendance_date`, `status`) VALUES
(3, 1, 1, '2026-05-19', 'Present'),
(11, 2, 5, '2026-05-19', 'Late'),
(15, 2, 3, '2026-05-21', 'Present'),
(41, 1, 6, '2026-05-21', 'Present'),
(49, 2, 5, '2026-05-21', 'Present'),
(50, 1, 1, '2026-05-21', 'Absent'),
(52, 1, 7, '2026-05-21', 'Absent'),
(53, 1, 9, '2026-05-21', 'Present'),
(54, 1, 1, '2026-05-20', 'Present'),
(55, 1, 6, '2026-05-20', 'Absent'),
(56, 1, 7, '2026-05-20', 'Absent'),
(60, 1, 11, '2026-05-21', 'Present'),
(62, 2, 8, '2026-05-21', 'Absent'),
(68, 1, 6, '2026-05-22', 'Absent'),
(69, 1, 7, '2026-05-22', 'Absent'),
(70, 1, 11, '2026-05-22', 'Present'),
(71, 2, 3, '2026-05-22', 'Present'),
(72, 1, 1, '2026-05-05', 'Absent'),
(76, 1, 6, '2026-05-05', 'Present'),
(77, 1, 9, '2026-05-05', 'Present'),
(78, 1, 11, '2026-05-05', 'Late'),
(79, 1, 13, '2026-05-05', 'Late');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `classroom` varchar(50) NOT NULL,
  `day_of_week` enum('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
  `period_number` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `course_id`, `teacher_id`, `classroom`, `day_of_week`, `period_number`, `start_time`, `end_time`, `created_by`, `created_at`) VALUES
(2, 3, 2, 'Shivapuri', 'Sunday', 2, '10:00:00', '12:00:00', 4, '2026-05-13 12:36:06'),
(3, 7, 1, 'Patan', 'Thursday', 1, '07:00:00', '09:00:00', 4, '2026-05-13 12:36:45'),
(6, 3, 2, 'hall4', 'Sunday', 1, '07:00:00', '09:00:00', 4, '2026-05-22 07:47:16');

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
(2, 'Manavi', 'Rai', '2025-02-03', '', 'raimanavi66@gmail.com', '$2y$12$KIvEPauQrr8EoY5Rjr0mA.0.LBVNUbpMfmRMrzUv4F81d7B7dbnXS', 'Student', '9800000001', '', '', '', 1, NULL, NULL, '258166', '2026-05-14 22:51:13', '2026-04-03 16:50:10'),
(3, 'Bibek', 'Jung', '1999-05-12', '', 'bibek123@gmail.com', '$2y$12$vf2XnTmp1nIExqZ5jlVVaOPYyeEcTs28nnW0tbrITyN4i72nTaFpu', 'Teacher', '9888776655', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-03 16:54:02'),
(4, 'System', 'Admin', '1990-01-01', 'AMS H0', 'admin123@gmail.com', '$2y$10$mx8S/JyA./SWfQwSlpS8dOsAhGxbZGJOonkUPCmAhJPO8vq9U.gva', 'Admin', '0000000000', 'Admin Mother', 'Admin Father', '0000000000', 1, NULL, NULL, NULL, NULL, '2026-04-03 17:04:24'),
(9, 'Sarayu', 'g', '1996-10-11', '', 'sarayu123@gmail.com', '$2y$12$rbh7prQDD6CbvnQoDlq59u96L2HoF0.HyOM4wP41eb2/9XbeIr2u2', 'Teacher', '9876543211', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-04 16:19:18'),
(10, 'Pema', 'Tamang', '2005-03-11', '', 'pema123@gmail.com', '$2y$12$KTQ/ewqcuKr/Thkrl.YlN.5imTKv0e/4ObIVpwPbGAnjhR2WOpvXS', 'Student', '9876543213', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-04-04 17:01:25'),
(11, 'Priyanka', 'Moktan', '2005-09-07', 'Boudha', 'priyankamoktan19@gmail.com', '$2y$10$ZEdxO6mG0pT7MWdL4eus0O6EMf094E7QnEA98CKGGNaalyE7vDbiW', 'Student', '9808080808', '', '', '', 1, NULL, NULL, '017003', '2026-05-21 18:32:34', '2026-04-07 01:43:36'),
(12, 'Arshiya', 'Magar', '2006-06-06', 'Naxal', 'arshiyarana90@gmail.com', '$2y$12$v3AFzgyd0wpCSMWOmUQWa.vVS/RtEfNxoski5tDLbG36GQ5aIjWmi', 'Student', '9887878787', 'Maya', 'Mayan', '9812312312', 1, NULL, NULL, NULL, NULL, '2026-04-18 12:58:08'),
(13, 'hellen', 'Tamang', '2012-11-17', 'Naxal', 'manavirai325@gmail.com', '$2y$12$ZE7fvp9SIbKcahmUQv8e/.X40wlFjUvRfTN1E/4pDV00lFHv7tQxi', 'Student', '9876666666', 'Lila', 'Hari', '9811223344', 1, NULL, NULL, NULL, NULL, '2026-04-19 16:39:05'),
(15, 'dss', 'kk', '2023-07-05', 'ssss', 'mariai66@gmail.com', '$2y$12$Uw/PgTzUsRIZJhKTG89z.uFkAKgp9arj6Ts7qOcekyVsK16d1vsze', 'Student', '1234567890', 'dd', 'sss', '90876543212', 0, '218201', '2026-05-14 23:11:47', NULL, NULL, '2026-05-14 17:11:47'),
(17, 'Maria', 'kk', '2008-01-16', '', 'mariae66@gmail.com', '$2y$12$nb5KojHLrmMGF15JVUWtSu6S7d9/hiv0VN16aZON5qlJVWm/o4rxy', 'Student', '1234567890', '', '', '', 0, '576037', '2026-05-14 23:20:07', NULL, NULL, '2026-05-14 17:20:07'),
(19, 'kripa', 'bhattarai', '2008-05-07', '', 'kritika.riviera9@gmail.com', '$2y$12$6dcYAAu3QsoaAtUI6ffAHOJWPdZqb.fn6Kgh.cFX0ssqMJ4SDl.sO', 'Student', '9876543211', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-05-21 12:39:14'),
(21, 'nijmi', 'b', '2006-01-22', '', 'nijmibaj@gmail.com', '$2y$10$1bB.2yUN8yHBa/uNnLa9fuPqaIVaLmUiuJKqkqjDwpm85ricb.Nii', 'Student', '9865365688', '', '', '', 1, NULL, NULL, NULL, NULL, '2026-05-22 07:07:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `created_by` (`created_by`);

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
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `marks_ibfk_4` (`entered_by`);

--
-- Indexes for table `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`resource_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `unique_submission` (`assignment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

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
  ADD UNIQUE KEY `teacher_course_date_idx` (`teacher_id`,`course_id`,`attendance_date`),
  ADD UNIQUE KEY `unique_teacher_course_date` (`teacher_id`,`course_id`,`attendance_date`),
  ADD KEY `fk_teacher_att_course` (`course_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignment`
--
ALTER TABLE `assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `exam`
--
ALTER TABLE `exam`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `notice`
--
ALTER TABLE `notice`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `resource_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `exam_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_3` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_4` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notice`
--
ALTER TABLE `notice`
  ADD CONSTRAINT `notice_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resources`
--
ALTER TABLE `resources`
  ADD CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_attendance`
--
ALTER TABLE `teacher_attendance`
  ADD CONSTRAINT `fk_teacher_att_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `timetable_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timetable_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
