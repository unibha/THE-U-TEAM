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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late') DEFAULT 'Present',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`course_id`,`attendance_date`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher_attendance`
--

CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late','On Leave') DEFAULT 'Present',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_attendance` (`teacher_id`,`attendance_date`),
  CONSTRAINT `teacher_attendance_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marks` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`assignment_id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_submission` (`assignment_id`,`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`resource_id`),
  KEY `course_id` (`course_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resources_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

CREATE TABLE `notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` enum('General','Urgent','Academic') DEFAULT 'General',
  `target_audience` enum('All','Students','Teachers') DEFAULT 'All',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'System',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
- -   M a r i a D B   d u m p   1 0 . 1 9     D i s t r i b   1 0 . 4 . 3 2 - M a r i a D B ,   f o r   W i n 6 4   ( A M D 6 4 )  
 - -  
 - -   H o s t :   l o c a l h o s t         D a t a b a s e :   a m s _ d b  
 - -   - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -  
 - -   S e r v e r   v e r s i o n 	 1 0 . 4 . 3 2 - M a r i a D B  
  
 / * ! 4 0 1 0 1   S E T   @ O L D _ C H A R A C T E R _ S E T _ C L I E N T = @ @ C H A R A C T E R _ S E T _ C L I E N T   * / ;  
 / * ! 4 0 1 0 1   S E T   @ O L D _ C H A R A C T E R _ S E T _ R E S U L T S = @ @ C H A R A C T E R _ S E T _ R E S U L T S   * / ;  
 / * ! 4 0 1 0 1   S E T   @ O L D _ C O L L A T I O N _ C O N N E C T I O N = @ @ C O L L A T I O N _ C O N N E C T I O N   * / ;  
 / * ! 4 0 1 0 1   S E T   N A M E S   u t f 8 m b 4   * / ;  
 / * ! 4 0 1 0 3   S E T   @ O L D _ T I M E _ Z O N E = @ @ T I M E _ Z O N E   * / ;  
 / * ! 4 0 1 0 3   S E T   T I M E _ Z O N E = ' + 0 0 : 0 0 '   * / ;  
 / * ! 4 0 0 1 4   S E T   @ O L D _ U N I Q U E _ C H E C K S = @ @ U N I Q U E _ C H E C K S ,   U N I Q U E _ C H E C K S = 0   * / ;  
 / * ! 4 0 0 1 4   S E T   @ O L D _ F O R E I G N _ K E Y _ C H E C K S = @ @ F O R E I G N _ K E Y _ C H E C K S ,   F O R E I G N _ K E Y _ C H E C K S = 0   * / ;  
 / * ! 4 0 1 0 1   S E T   @ O L D _ S Q L _ M O D E = @ @ S Q L _ M O D E ,   S Q L _ M O D E = ' N O _ A U T O _ V A L U E _ O N _ Z E R O '   * / ;  
 / * ! 4 0 1 1 1   S E T   @ O L D _ S Q L _ N O T E S = @ @ S Q L _ N O T E S ,   S Q L _ N O T E S = 0   * / ;  
  
 - -  
 - -   T a b l e   s t r u c t u r e   f o r   t a b l e   ` e x a m `  
 - -  
  
 D R O P   T A B L E   I F   E X I S T S   ` e x a m ` ;  
 / * ! 4 0 1 0 1   S E T   @ s a v e d _ c s _ c l i e n t           =   @ @ c h a r a c t e r _ s e t _ c l i e n t   * / ;  
 / * ! 4 0 1 0 1   S E T   c h a r a c t e r _ s e t _ c l i e n t   =   u t f 8   * / ;  
 C R E A T E   T A B L E   ` e x a m `   (  
     ` i d `   i n t ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
     ` e x a m _ n a m e `   v a r c h a r ( 1 0 0 )   N O T   N U L L ,  
     ` c o u r s e _ i d `   i n t ( 1 1 )   N O T   N U L L ,  
     ` e x a m _ d a t e `   d a t e   N O T   N U L L ,  
     ` s t a r t _ t i m e `   t i m e   N O T   N U L L ,  
     ` e n d _ t i m e `   t i m e   N O T   N U L L ,  
     ` t o t a l _ m a r k s `   i n t ( 1 1 )   N O T   N U L L ,  
     ` c r e a t e d _ a t `   t i m e s t a m p   N O T   N U L L   D E F A U L T   c u r r e n t _ t i m e s t a m p ( ) ,  
     P R I M A R Y   K E Y   ( ` i d ` ) ,  
     K E Y   ` c o u r s e _ i d `   ( ` c o u r s e _ i d ` ) ,  
     C O N S T R A I N T   ` e x a m _ i b f k _ 1 `   F O R E I G N   K E Y   ( ` c o u r s e _ i d ` )   R E F E R E N C E S   ` c o u r s e s `   ( ` i d ` )   O N   D E L E T E   C A S C A D E  
 )   E N G I N E = I n n o D B   A U T O _ I N C R E M E N T = 8   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ g e n e r a l _ c i ;  
 / * ! 4 0 1 0 1   S E T   c h a r a c t e r _ s e t _ c l i e n t   =   @ s a v e d _ c s _ c l i e n t   * / ;  
  
 - -  
 - -   D u m p i n g   d a t a   f o r   t a b l e   ` e x a m `  
 - -  
  
 L O C K   T A B L E S   ` e x a m `   W R I T E ;  
 / * ! 4 0 0 0 0   A L T E R   T A B L E   ` e x a m `   D I S A B L E   K E Y S   * / ;  
 I N S E R T   I N T O   ` e x a m `   V A L U E S   ( 1 , ' S e m e s t e r 3 ' , 1 , ' 2 0 2 6 - 0 9 - 2 2 ' , ' 1 3 : 0 0 : 0 0 ' , ' 1 5 : 0 0 : 0 0 ' , 1 0 0 , ' 2 0 2 6 - 0 5 - 1 3   1 1 : 4 8 : 3 3 ' ) , ( 2 , ' S e m e s t e r 2 ' , 8 , ' 2 0 2 6 - 0 9 - 2 2 ' , ' 1 2 : 0 0 : 0 0 ' , ' 1 4 : 0 0 : 0 0 ' , 1 0 0 , ' 2 0 2 6 - 0 5 - 1 3   1 4 : 3 8 : 2 5 ' ) , ( 3 , ' S e m e s t e r 2 ' , 3 , ' 2 0 2 6 - 0 8 - 2 2 ' , ' 0 8 : 0 0 : 0 0 ' , ' 1 0 : 0 0 : 0 0 ' , 7 0 , ' 2 0 2 6 - 0 5 - 1 3   1 4 : 4 5 : 0 8 ' ) , ( 4 , ' S e m e s t e r 1 ' , 3 , ' 2 0 2 6 - 0 8 - 2 2 ' , ' 1 4 : 0 0 : 0 0 ' , ' 1 6 : 0 0 : 0 0 ' , 1 0 0 , ' 2 0 2 6 - 0 5 - 1 3   1 4 : 4 8 : 4 7 ' ) , ( 5 , ' S e m e s t e r 4 ' , 3 , ' 2 0 2 6 - 0 8 - 2 2 ' , ' 1 0 : 0 0 : 0 0 ' , ' 1 2 : 0 0 : 0 0 ' , 1 0 0 , ' 2 0 2 6 - 0 5 - 1 3   1 4 : 5 1 : 3 8 ' ) , ( 6 , ' S e m e s t e r 2 ' , 6 , ' 2 0 2 6 - 0 8 - 0 7 ' , ' 0 8 : 0 0 : 0 0 ' , ' 1 0 : 0 0 : 0 0 ' , 5 0 , ' 2 0 2 6 - 0 5 - 1 4   1 6 : 2 2 : 2 3 ' ) , ( 7 , ' s s ' , 3 , ' 2 0 2 6 - 0 5 - 1 7 ' , ' 1 0 : 0 0 : 0 0 ' , ' 1 2 : 0 0 : 0 0 ' , 1 0 0 , ' 2 0 2 6 - 0 5 - 1 4   1 8 : 4 3 : 2 1 ' ) ;  
 / * ! 4 0 0 0 0   A L T E R   T A B L E   ` e x a m `   E N A B L E   K E Y S   * / ;  
 U N L O C K   T A B L E S ;  
  
 - -  
 - -   T a b l e   s t r u c t u r e   f o r   t a b l e   ` m a r k s `  
 - -  
  
 D R O P   T A B L E   I F   E X I S T S   ` m a r k s ` ;  
 / * ! 4 0 1 0 1   S E T   @ s a v e d _ c s _ c l i e n t           =   @ @ c h a r a c t e r _ s e t _ c l i e n t   * / ;  
 / * ! 4 0 1 0 1   S E T   c h a r a c t e r _ s e t _ c l i e n t   =   u t f 8   * / ;  
 C R E A T E   T A B L E   ` m a r k s `   (  
     ` i d `   i n t ( 1 1 )   N O T   N U L L   A U T O _ I N C R E M E N T ,  
     ` e x a m _ i d `   i n t ( 1 1 )   N O T   N U L L ,  
     ` s t u d e n t _ i d `   i n t ( 1 1 )   N O T   N U L L ,  
     ` c o u r s e _ i d `   i n t ( 1 1 )   N O T   N U L L ,  
     ` m a r k s _ o b t a i n e d `   d e c i m a l ( 5 , 2 )   N O T   N U L L ,  
     ` g r a d e `   v a r c h a r ( 1 0 )   D E F A U L T   N U L L ,  
     ` r e m a r k s `   t e x t   D E F A U L T   N U L L ,  
     ` e n t e r e d _ b y `   i n t ( 1 1 )   N O T   N U L L ,  
     ` c r e a t e d _ a t `   t i m e s t a m p   N O T   N U L L   D E F A U L T   c u r r e n t _ t i m e s t a m p ( ) ,  
     P R I M A R Y   K E Y   ( ` i d ` ) ,  
     U N I Q U E   K E Y   ` u n i q u e _ e x a m _ s t u d e n t `   ( ` e x a m _ i d ` , ` s t u d e n t _ i d ` ) ,  
     K E Y   ` e x a m _ i d `   ( ` e x a m _ i d ` ) ,  
     K E Y   ` s t u d e n t _ i d `   ( ` s t u d e n t _ i d ` ) ,  
     K E Y   ` c o u r s e _ i d `   ( ` c o u r s e _ i d ` ) ,  
     K E Y   ` m a r k s _ i b f k _ 4 `   ( ` e n t e r e d _ b y ` ) ,  
     C O N S T R A I N T   ` m a r k s _ i b f k _ 1 `   F O R E I G N   K E Y   ( ` e x a m _ i d ` )   R E F E R E N C E S   ` e x a m `   ( ` i d ` )   O N   D E L E T E   C A S C A D E ,  
     C O N S T R A I N T   ` m a r k s _ i b f k _ 2 `   F O R E I G N   K E Y   ( ` s t u d e n t _ i d ` )   R E F E R E N C E S   ` s t u d e n t s `   ( ` i d ` )   O N   D E L E T E   C A S C A D E ,  
     C O N S T R A I N T   ` m a r k s _ i b f k _ 3 `   F O R E I G N   K E Y   ( ` c o u r s e _ i d ` )   R E F E R E N C E S   ` c o u r s e s `   ( ` i d ` )   O N   D E L E T E   C A S C A D E ,  
     C O N S T R A I N T   ` m a r k s _ i b f k _ 4 `   F O R E I G N   K E Y   ( ` e n t e r e d _ b y ` )   R E F E R E N C E S   ` u s e r s `   ( ` i d ` )  
 )   E N G I N E = I n n o D B   A U T O _ I N C R E M E N T = 2 7   D E F A U L T   C H A R S E T = u t f 8 m b 4   C O L L A T E = u t f 8 m b 4 _ g e n e r a l _ c i ;  
 / * ! 4 0 1 0 1   S E T   c h a r a c t e r _ s e t _ c l i e n t   =   @ s a v e d _ c s _ c l i e n t   * / ;  
  
 - -  
 - -   D u m p i n g   d a t a   f o r   t a b l e   ` m a r k s `  
 - -  
  
 L O C K   T A B L E S   ` m a r k s `   W R I T E ;  
 / * ! 4 0 0 0 0   A L T E R   T A B L E   ` m a r k s `   D I S A B L E   K E Y S   * / ;  
 I N S E R T   I N T O   ` m a r k s `   V A L U E S   ( 1 , 1 , 5 , 1 , 6 0 . 0 0 , ' D ' , ' ' , 3 , ' 2 0 2 6 - 0 5 - 1 3   1 2 : 0 5 : 1 8 ' ) , ( 2 , 1 , 1 , 1 , 9 8 . 0 0 , ' A ' , ' ' , 3 , ' 2 0 2 6 - 0 5 - 1 3   1 2 : 0 5 : 1 8 ' ) , ( 3 , 1 , 2 , 1 , 2 0 . 0 0 , ' F ' , ' ' , 3 , ' 2 0 2 6 - 0 5 - 1 3   1 2 : 0 5 : 1 8 ' ) , ( 4 , 1 , 3 , 1 , 3 1 . 0 0 , ' F ' , ' ' , 3 , ' 2 0 2 6 - 0 5 - 1 3   1 2 : 0 5 : 1 8 ' ) , ( 5 , 1 , 4 , 1 , 3 3 . 0 0 , ' F ' , ' ' , 3 , ' 2 0 2 6 - 0 5 - 1 3   1 2 : 0 5 : 1 8 ' ) , ( 2 6 , 5 , 1 , 3 , 2 7 . 0 0 , ' F ' , ' ' , 9 , ' 2 0 2 6 - 0 5 - 1 4   1 8 : 3 9 : 4 8 ' ) ;  
 / * ! 4 0 0 0 0   A L T E R   T A B L E   ` m a r k s `   E N A B L E   K E Y S   * / ;  
 U N L O C K   T A B L E S ;  
 / * ! 4 0 1 0 3   S E T   T I M E _ Z O N E = @ O L D _ T I M E _ Z O N E   * / ;  
  
 / * ! 4 0 1 0 1   S E T   S Q L _ M O D E = @ O L D _ S Q L _ M O D E   * / ;  
 / * ! 4 0 0 1 4   S E T   F O R E I G N _ K E Y _ C H E C K S = @ O L D _ F O R E I G N _ K E Y _ C H E C K S   * / ;  
 / * ! 4 0 0 1 4   S E T   U N I Q U E _ C H E C K S = @ O L D _ U N I Q U E _ C H E C K S   * / ;  
 / * ! 4 0 1 0 1   S E T   C H A R A C T E R _ S E T _ C L I E N T = @ O L D _ C H A R A C T E R _ S E T _ C L I E N T   * / ;  
 / * ! 4 0 1 0 1   S E T   C H A R A C T E R _ S E T _ R E S U L T S = @ O L D _ C H A R A C T E R _ S E T _ R E S U L T S   * / ;  
 / * ! 4 0 1 0 1   S E T   C O L L A T I O N _ C O N N E C T I O N = @ O L D _ C O L L A T I O N _ C O N N E C T I O N   * / ;  
 / * ! 4 0 1 1 1   S E T   S Q L _ N O T E S = @ O L D _ S Q L _ N O T E S   * / ;  
  
 - -   D u m p   c o m p l e t e d   o n   2 0 2 6 - 0 5 - 1 5     0 : 4 8 : 0 3  
 