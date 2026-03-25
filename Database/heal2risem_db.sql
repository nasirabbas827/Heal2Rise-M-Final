-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 08:41 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `heal2risem_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `team_member_id` int(11) DEFAULT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `case_title` varchar(150) DEFAULT NULL,
  `issue_category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `status` enum('open','in_progress','rehabilitation','closed','rejected','completed') DEFAULT 'open',
  `closure_date` date DEFAULT NULL,
  `closure_reason` text DEFAULT NULL,
  `assigned_date` datetime DEFAULT current_timestamp(),
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`case_id`, `user_id`, `ngo_id`, `team_member_id`, `counselor_id`, `case_title`, `issue_category`, `description`, `start_date`, `status`, `closure_date`, `closure_reason`, `assigned_date`, `last_updated`) VALUES
(1, 1, 1, 1, NULL, 'One', 'Advocacy', 'fdsa', '2026-02-14', 'closed', '2026-02-21', 'Noti', '2026-02-14 10:39:32', '2026-02-14 11:32:28'),
(2, 1, NULL, NULL, NULL, 'fads', 'Financial', 'sacxz', '2026-02-14', 'open', NULL, NULL, '2026-02-14 11:31:55', '2026-02-14 11:31:55'),
(3, 1, 1, 1, NULL, 'Video', 'Education', 'XYZ', '2026-02-28', 'open', NULL, NULL, '2026-02-28 21:32:48', '2026-02-28 21:33:18'),
(4, 1, 1, 1, 1, 'Prototype', 'Education', 'XYZ', '2026-03-25', 'completed', '2026-03-25', NULL, '2026-03-25 11:51:02', '2026-03-25 12:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `case_progress`
--

CREATE TABLE `case_progress` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `updated_by_team_member` int(11) DEFAULT NULL,
  `progress_note` text DEFAULT NULL,
  `skill_training_status` varchar(100) DEFAULT NULL,
  `counseling_status` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_progress`
--

INSERT INTO `case_progress` (`id`, `case_id`, `updated_by_team_member`, `progress_note`, `skill_training_status`, `counseling_status`, `created_at`) VALUES
(1, 1, 1, 'fdsa', 'Completed', 'Stable', '2026-02-14 06:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

CREATE TABLE `counseling_sessions` (
  `session_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `counselor_name` varchar(100) DEFAULT NULL,
  `duration_minutes` smallint(6) DEFAULT 45,
  `notes` text DEFAULT NULL,
  `attended` tinyint(1) DEFAULT 1,
  `next_suggested_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `counseling_sessions`
--

INSERT INTO `counseling_sessions` (`session_id`, `case_id`, `session_date`, `counselor_name`, `duration_minutes`, `notes`, `attended`, `next_suggested_date`, `created_at`) VALUES
(1, 1, '2026-02-14 10:48:00', 'Haider', 45, 'fdsa', 1, '2026-02-20', '2026-02-14 10:48:35'),
(2, 4, '2026-03-25 08:18:00', 'Haider Ali', 45, 'XYZdd', 1, NULL, '2026-03-25 12:18:29');

-- --------------------------------------------------------

--
-- Table structure for table `counselors`
--

CREATE TABLE `counselors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `counselors`
--

INSERT INTO `counselors` (`id`, `full_name`, `email`, `phone`, `specialization`, `password`, `created_at`) VALUES
(1, 'Haider Ali', 'haider@gmail.com', '03266029053', 'Depression', '$2y$10$D3MvI65tIgMhnxIy5WU7We3UP/PtJ9bNkxaCMhEqFY7LHMDMwnxFe', '2026-03-25 06:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `ngos`
--

CREATE TABLE `ngos` (
  `id` int(11) NOT NULL,
  `ngo_name` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ngos`
--

INSERT INTO `ngos` (`id`, `ngo_name`, `username`, `email`, `password_hash`, `contact_person`, `phone`, `address`, `city`, `specialties`, `registration_date`, `is_active`, `last_updated`) VALUES
(1, 'Eedhi', 'ngo12', 'nasiryt.827@gmail.com', '$2y$10$IxuQHBu4VguDNqViNzzS4.GHTavgr9lSMBdZVVh8avbZFrhh.bIHe', 'NASIR ABBAS', '03176526827', 'Street jeff xxxxAp04', 'Salvador', 'Medical', '2026-02-14 10:10:23', 1, '2026-02-14 10:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `skill_development`
--

CREATE TABLE `skill_development` (
  `skill_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','dropped') DEFAULT 'planned',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skill_development`
--

INSERT INTO `skill_development` (`skill_id`, `case_id`, `skill_name`, `start_date`, `target_date`, `completion_date`, `status`, `notes`) VALUES
(1, 1, 'fds', '2026-02-14', '2026-02-28', '2026-03-03', 'planned', 'afdsa'),
(2, 4, 'XYZ', '2026-03-25', '2026-04-02', NULL, 'planned', 'adsfd');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `team_member_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `expertise` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`team_member_id`, `full_name`, `email`, `password_hash`, `phone`, `ngo_id`, `expertise`, `is_active`, `created_at`) VALUES
(1, 'Nasir Abbas', 'nasiryt.827@gmail.com', '$2y$10$JBAW8w2S2sjwzcKFnalZCuNAFKk6q3MkMakL4g5XlyESgWsQ7W4PC', '03266029050', NULL, 'Legal Aid', 1, '2026-02-14 10:29:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `cnic` varchar(15) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `dob`, `cnic`, `contact_number`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'NASIR ABBAS', 'nasir12', 'nasiryt.827@gmail.com', '$2y$10$TiWF92C/55jAOb09ZLNyguws4aER61jeytMmHZecEmm1iR1Q41DXS', '2026-02-07', '3240254008217', '03176526827', 'Street jeff xxxx\r\nAp04', 1, '2026-02-07 07:48:15', '2026-02-14 05:17:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `ngo_id` (`ngo_id`),
  ADD KEY `team_member_id` (`team_member_id`);

--
-- Indexes for table `case_progress`
--
ALTER TABLE `case_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `updated_by_team_member` (`updated_by_team_member`);

--
-- Indexes for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `counselors`
--
ALTER TABLE `counselors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `ngos`
--
ALTER TABLE `ngos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ngo_name` (`ngo_name`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `skill_development`
--
ALTER TABLE `skill_development`
  ADD PRIMARY KEY (`skill_id`),
  ADD KEY `case_id` (`case_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`team_member_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `case_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `case_progress`
--
ALTER TABLE `case_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `counselors`
--
ALTER TABLE `counselors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ngos`
--
ALTER TABLE `ngos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skill_development`
--
ALTER TABLE `skill_development`
  MODIFY `skill_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `team_member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cases_ibfk_3` FOREIGN KEY (`team_member_id`) REFERENCES `team_members` (`team_member_id`) ON DELETE SET NULL;

--
-- Constraints for table `case_progress`
--
ALTER TABLE `case_progress`
  ADD CONSTRAINT `case_progress_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_progress_ibfk_2` FOREIGN KEY (`updated_by_team_member`) REFERENCES `team_members` (`team_member_id`) ON DELETE SET NULL;

--
-- Constraints for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD CONSTRAINT `counseling_sessions_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE;

--
-- Constraints for table `skill_development`
--
ALTER TABLE `skill_development`
  ADD CONSTRAINT `skill_development_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `ngos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
