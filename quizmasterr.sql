-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2025 at 02:11 PM
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
-- Database: `quizmasterr`
--

-- --------------------------------------------------------

--
-- Table structure for table `choices`
--

CREATE TABLE `choices` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `choice_text` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `choices`
--

INSERT INTO `choices` (`id`, `question_id`, `choice_text`, `is_correct`) VALUES
(124, 75, 'a', 1),
(125, 75, 'b', 0),
(126, 75, 'c', 0),
(127, 75, 'd', 0),
(132, 86, 'w', 0),
(133, 86, 'w', 0),
(134, 86, 'w', 1),
(135, 86, 'w', 0);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('mcq','true_false','short_answer','essay') NOT NULL,
  `correct_answer` text DEFAULT NULL,
  `max_score` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `question_text`, `question_type`, `correct_answer`, `max_score`) VALUES
(75, 35, 'what is a system', 'mcq', '0', 0),
(84, 36, '2121', 'essay', '', 0),
(86, 39, 'ewew', 'mcq', '2', 0),
(87, 40, '2222', 'essay', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `instructions` text DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL,
  `attempts_allowed` int(11) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `quiz_token` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `title`, `subject`, `instructions`, `time_limit`, `attempts_allowed`, `deadline`, `status`, `quiz_token`, `created_at`) VALUES
(35, 'System Fundamentals', 'System', '', 1, NULL, '2025-07-21 10:24:00', 'published', 'e7909e574d8d54572a3f23165a0ea807', '2025-07-21 02:22:51'),
(36, 'ewww', 'rere', 'rere', 12, 12, '2025-07-21 01:15:00', 'published', 'f3a28ec0863e7c85f0913d9d9be74cd9', '2025-07-21 03:16:05'),
(39, 'fff', 'ff', 'fff', 1, 1, '2025-07-23 13:14:00', 'published', '0f982933cdcfeb24219806aa6cffb5c4', '2025-07-21 05:14:26'),
(40, 'eeee', '', '', 1, 1, '2025-07-24 13:32:00', 'published', 'f0513d055cf4fc6108f46da2ae36ee83', '2025-07-21 05:32:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `attempt_id`, `question_id`, `answer`, `created_at`) VALUES
(117, 125, 75, 'a', '2025-07-21 10:23:42'),
(118, 126, 75, 'a', '2025-07-21 11:02:01'),
(119, 128, 86, 'w', '2025-07-21 13:14:55'),
(120, 129, 87, 'eqweqwe', '2025-07-21 13:35:39');

-- --------------------------------------------------------

--
-- Table structure for table `student_attempts`
--

CREATE TABLE `student_attempts` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempt_number` int(11) NOT NULL,
  `started_at` datetime DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `is_graded` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_attempts`
--

INSERT INTO `student_attempts` (`id`, `first_name`, `middle_initial`, `last_name`, `suffix`, `year_level`, `program`, `email`, `student_id`, `quiz_id`, `attempt_number`, `started_at`, `submitted_at`, `score`, `is_graded`) VALUES
(125, 'mj', '', 'cuiman', '', '1st Year', 'BEED', NULL, '1212', 35, 1, '2025-07-21 10:23:19', '2025-07-21 10:23:42', 1, 1),
(126, 'mj', 'a', 'dd', '', '2nd Year', 'BEED', NULL, '3232', 35, 1, '2025-07-21 11:01:57', '2025-07-21 11:02:01', 1, 1),
(127, 'mj', '', 'mmm', '', '1st Year', 'BEED', NULL, '2121', 35, 1, '2025-07-21 11:12:39', NULL, NULL, 1),
(128, 'ffff', '', 'fff', '', '1st Year', 'BEED', NULL, '2313', 39, 1, '2025-07-21 13:14:53', '2025-07-21 13:14:55', 1, 1),
(129, 'ggg', '', 'ggg', '', '2nd Year', 'BSED', NULL, '2313', 40, 1, '2025-07-21 13:35:36', '2025-07-21 13:35:39', 40, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `full_name`, `email`, `password`) VALUES
(1, 'Mj Macalos', 'mj@gmail.com', '$2y$10$CFuQ1YHOpZQF8lIviJYJxOunjitbo1KEaUN458ksDje2vy6oD4hl6'),
(2, 'dan', 'dani@gmail.com', '$2y$10$8RlbSdEtusO8skV7g9BKr.67RQy4UkHDDln5lSEX0S8QbHzsA1LXq');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `choices`
--
ALTER TABLE `choices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quiz_token` (`quiz_token`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `student_attempts`
--
ALTER TABLE `student_attempts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `choices`
--
ALTER TABLE `choices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `student_attempts`
--
ALTER TABLE `student_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `choices`
--
ALTER TABLE `choices`
  ADD CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
