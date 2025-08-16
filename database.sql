-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 16, 2025 at 08:07 AM
-- Server version: 10.3.39-MariaDB-0ubuntu0.20.04.2
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `task_audiensi`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProjectTaskCounts` (IN `project_id_param` INT)   BEGIN
    UPDATE projects 
    SET total_tasks = (
            SELECT COUNT(*) 
            FROM project_tasks 
            WHERE project_id = project_id_param
        ),
        completed_tasks = (
            SELECT COUNT(*) 
            FROM project_tasks 
            WHERE project_id = project_id_param AND status = 'completed'
        )
    WHERE id = project_id_param;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `admin_dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `admin_dashboard_stats` (
`total_employees` bigint(21)
,`total_admins` bigint(21)
,`total_managers` bigint(21)
,`total_regular_employees` bigint(21)
,`total_tasks` bigint(21)
,`pending_tasks` bigint(21)
,`ongoing_tasks` bigint(21)
,`completed_tasks` bigint(21)
,`overdue_tasks` bigint(21)
,`dropped_tasks` bigint(21)
,`total_points_all` decimal(32,0)
,`avg_points_per_employee` decimal(14,4)
,`unread_notifications` bigint(21)
,`todays_admin_activities` bigint(21)
,`active_admins_today` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `created_at`) VALUES
(1, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:31:26'),
(2, NULL, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:03'),
(3, NULL, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:10'),
(4, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:17'),
(5, NULL, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:21'),
(6, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:45'),
(7, NULL, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 05:36:58'),
(8, NULL, 'view_reports', NULL, NULL, 'Date range: 2025-08-01 to 2025-08-16', '125.165.105.190', '2025-08-16 05:37:24'),
(9, NULL, 'view_reports', NULL, NULL, 'Date range: 2025-08-01 to 2025-08-16', '125.165.105.190', '2025-08-16 05:39:03'),
(10, NULL, 'view_reports', NULL, NULL, 'Date range: 2025-08-01 to 2025-08-16', '125.165.105.190', '2025-08-16 05:59:14'),
(11, NULL, 'view_reports', NULL, NULL, 'Date range: 2025-08-01 to 2025-08-16', '125.165.105.190', '2025-08-16 06:05:47'),
(12, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:05:49'),
(13, NULL, 'admin_logout', 'employee', 7, 'Admin logged out', '125.165.105.190', '2025-08-16 06:05:52'),
(14, NULL, 'system_initialized', 'system', NULL, 'Admin system initialized with secure authentication', '127.0.0.1', '2025-08-16 06:12:35'),
(15, NULL, 'admin_created', NULL, NULL, 'New admin account created: kontaknurman@gmail.com', '125.165.105.190', '2025-08-16 06:34:04'),
(16, NULL, 'admin_login', NULL, NULL, 'Successful admin login', '125.165.105.190', '2025-08-16 06:38:43'),
(17, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:38:43'),
(18, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:40:17'),
(19, NULL, 'admin_logout', 'employee', 16, 'Admin logged out', '125.165.105.190', '2025-08-16 06:40:19'),
(20, NULL, 'admin_login', NULL, NULL, 'Successful admin login', '125.165.105.190', '2025-08-16 06:40:25'),
(21, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:40:25'),
(22, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:50:02'),
(23, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:50:10'),
(24, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:50:14'),
(25, NULL, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:50:17'),
(26, NULL, 'admin_logout', 'employee', 16, 'Admin logged out', '125.165.105.190', '2025-08-16 06:52:31'),
(27, 17, 'admin_login', NULL, NULL, 'Successful admin login', '125.165.105.190', '2025-08-16 06:52:37'),
(28, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:52:38'),
(29, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:54:00'),
(30, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:29'),
(31, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:33'),
(32, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:38'),
(33, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:45'),
(34, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:46'),
(35, 17, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:52'),
(36, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:55:57'),
(37, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:56:06'),
(38, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 06:56:08'),
(39, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:20:22'),
(40, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:20:43'),
(41, 17, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:20:47'),
(42, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:20:51'),
(43, 17, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:21:20'),
(44, 17, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:29:01'),
(45, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:29:06'),
(46, 17, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:29:12'),
(47, 18, 'admin_created', NULL, NULL, 'New admin account created: kontaknurman@gmail.com', '125.165.105.190', '2025-08-16 07:30:26'),
(48, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:30:34'),
(49, 17, 'view_logs', NULL, NULL, 'Page 1', '125.165.105.190', '2025-08-16 07:30:44'),
(50, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:30:47'),
(51, 17, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:30:49'),
(52, 17, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:30:50'),
(53, 17, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:30:51'),
(54, 17, 'admin_logout', 'employee', 17, 'Admin logged out', '125.165.105.190', '2025-08-16 07:30:55'),
(55, 18, 'admin_login', NULL, NULL, 'Successful admin login', '125.165.105.190', '2025-08-16 07:31:01'),
(56, 18, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:31:01'),
(57, 18, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:31:05'),
(58, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:32:34'),
(59, 18, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:34:51'),
(60, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:35:22'),
(61, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:48:29'),
(62, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:53:13'),
(63, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:54:14'),
(64, 18, 'create_project', 'project', 14, 'Created project: TopUpGim', '125.165.105.190', '2025-08-16 07:56:23'),
(65, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:23'),
(66, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:30'),
(67, 18, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:35'),
(68, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:38'),
(69, 18, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:44'),
(70, 18, 'view_tasks', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:56:47'),
(71, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:59:29'),
(72, 18, 'view_projects', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:59:29'),
(73, 18, 'view_departments', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:59:49'),
(74, 18, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 07:59:55'),
(75, 18, 'view_logs', NULL, NULL, 'Page 1', '125.165.105.190', '2025-08-16 07:59:56'),
(76, 18, 'view_reports', NULL, NULL, 'Date range: 2025-08-01 to 2025-08-16', '125.165.105.190', '2025-08-16 08:00:00'),
(77, 18, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 08:00:32'),
(78, 18, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 08:00:33'),
(79, 18, 'view_employees', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 08:00:50'),
(80, 18, 'view_dashboard', NULL, NULL, NULL, '125.165.105.190', '2025-08-16 08:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','manager') NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `allowed` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_permissions`
--

INSERT INTO `admin_permissions` (`id`, `role`, `module`, `action`, `allowed`, `created_at`) VALUES
(1, 'admin', 'employees', 'view', 1, '2025-08-16 06:05:37'),
(2, 'admin', 'employees', 'create', 1, '2025-08-16 06:05:37'),
(3, 'admin', 'employees', 'edit', 1, '2025-08-16 06:05:37'),
(4, 'admin', 'employees', 'delete', 1, '2025-08-16 06:05:37'),
(5, 'admin', 'tasks', 'view', 1, '2025-08-16 06:05:37'),
(6, 'admin', 'tasks', 'create', 1, '2025-08-16 06:05:37'),
(7, 'admin', 'tasks', 'edit', 1, '2025-08-16 06:05:37'),
(8, 'admin', 'tasks', 'delete', 1, '2025-08-16 06:05:37'),
(9, 'admin', 'reports', 'view', 1, '2025-08-16 06:05:37'),
(10, 'admin', 'reports', 'export', 1, '2025-08-16 06:05:37'),
(11, 'admin', 'logs', 'view', 1, '2025-08-16 06:05:37'),
(12, 'admin', 'settings', 'view', 1, '2025-08-16 06:05:37'),
(13, 'admin', 'settings', 'edit', 1, '2025-08-16 06:05:37'),
(14, 'manager', 'employees', 'view', 1, '2025-08-16 06:05:37'),
(15, 'manager', 'employees', 'create', 0, '2025-08-16 06:05:37'),
(16, 'manager', 'employees', 'edit', 1, '2025-08-16 06:05:37'),
(17, 'manager', 'employees', 'delete', 0, '2025-08-16 06:05:37'),
(18, 'manager', 'tasks', 'view', 1, '2025-08-16 06:05:37'),
(19, 'manager', 'tasks', 'create', 1, '2025-08-16 06:05:37'),
(20, 'manager', 'tasks', 'edit', 1, '2025-08-16 06:05:37'),
(21, 'manager', 'tasks', 'delete', 0, '2025-08-16 06:05:37'),
(22, 'manager', 'reports', 'view', 1, '2025-08-16 06:05:37'),
(23, 'manager', 'reports', 'export', 0, '2025-08-16 06:05:37'),
(24, 'manager', 'logs', 'view', 0, '2025-08-16 06:05:37'),
(25, 'manager', 'settings', 'view', 0, '2025-08-16 06:05:37'),
(26, 'manager', 'settings', 'edit', 0, '2025-08-16 06:05:37'),
(27, 'admin', 'logs', 'delete', 1, '2025-08-16 06:12:35'),
(28, 'admin', 'departments', 'manage', 1, '2025-08-16 06:12:35'),
(29, 'admin', 'security', 'manage', 1, '2025-08-16 06:12:35'),
(30, 'manager', 'logs', 'delete', 0, '2025-08-16 06:12:35'),
(31, 'manager', 'departments', 'manage', 0, '2025-08-16 06:12:35'),
(32, 'manager', 'security', 'manage', 0, '2025-08-16 06:12:35');

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `dashboard_layout` varchar(50) DEFAULT 'default',
  `items_per_page` int(11) DEFAULT 20,
  `email_notifications` tinyint(1) DEFAULT 1,
  `whatsapp_notifications` tinyint(1) DEFAULT 0,
  `theme` varchar(20) DEFAULT 'light',
  `language` varchar(10) DEFAULT 'en',
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `admin_id`, `dashboard_layout`, `items_per_page`, `email_notifications`, `whatsapp_notifications`, `theme`, `language`, `two_factor_enabled`, `two_factor_secret`, `last_login`, `last_ip`, `login_attempts`, `locked_until`, `api_key`, `permissions`, `created_at`, `updated_at`) VALUES
(5, 10, 'default', 20, 1, 0, 'light', 'en', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-08-16 06:12:35', '2025-08-16 06:12:35'),
(9, 15, 'default', 20, 1, 0, 'light', 'en', 0, NULL, NULL, NULL, 0, NULL, NULL, NULL, '2025-08-16 06:17:53', '2025-08-16 06:17:53'),
(13, 17, 'default', 20, 1, 0, 'light', 'en', 0, NULL, '2025-08-16 08:52:37', '125.165.105.190', 0, NULL, NULL, NULL, '2025-08-16 06:52:37', '2025-08-16 06:52:37'),
(14, 18, 'default', 20, 1, 0, 'light', 'en', 0, NULL, '2025-08-16 09:31:01', '125.165.105.190', 0, NULL, NULL, NULL, '2025-08-16 07:30:26', '2025-08-16 07:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `csrf_tokens`
--

CREATE TABLE `csrf_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `department_code`, `description`, `project_id`, `manager_id`, `location`, `budget`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Frontend Team', 'FE-WEB', 'Frontend development for web project', NULL, NULL, 'Building A, Floor 3', 0.00, 'active', NULL, '2025-08-16 07:28:22', '2025-08-16 07:28:22'),
(2, 'Backend Team', 'BE-WEB', 'Backend development for web project', NULL, NULL, 'Building A, Floor 3', 0.00, 'active', NULL, '2025-08-16 07:28:22', '2025-08-16 07:28:22'),
(3, 'Mobile Dev Team', 'MOB-DEV', 'Mobile application development', NULL, NULL, 'Building B, Floor 2', 0.00, 'active', NULL, '2025-08-16 07:28:22', '2025-08-16 07:28:22'),
(4, 'QA Team', 'QA', 'Quality Assurance', NULL, NULL, 'Building A, Floor 2', 0.00, 'active', NULL, '2025-08-16 07:28:22', '2025-08-16 07:28:22'),
(5, 'DevOps', 'DEVOPS', 'Development Operations', NULL, NULL, 'Building A, Floor 1', 0.00, 'active', NULL, '2025-08-16 07:28:22', '2025-08-16 07:28:22');

-- --------------------------------------------------------

--
-- Table structure for table `department_members`
--

CREATE TABLE `department_members` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `position` varchar(100) DEFAULT 'Member',
  `joined_date` date DEFAULT curdate(),
  `left_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_head` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `department_summary`
-- (See below for the actual view)
--
CREATE TABLE `department_summary` (
`id` int(11)
,`department_name` varchar(100)
,`department_code` varchar(20)
,`description` text
,`project_id` int(11)
,`manager_id` int(11)
,`location` varchar(255)
,`budget` decimal(15,2)
,`status` enum('active','inactive')
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`manager_name` varchar(100)
,`created_by_name` varchar(100)
,`member_count` bigint(21)
,`project_name` varchar(255)
,`project_code` varchar(50)
,`project_status` enum('planning','active','on_hold','completed','cancelled')
);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') DEFAULT 'employee',
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `notification_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `password`, `role`, `whatsapp_number`, `department`, `total_points`, `notification_enabled`, `created_at`) VALUES
(3, 'Bob Johnson', 'bob@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1234567892', 'Sales', 0, 1, '2025-08-16 04:54:36'),
(4, 'Alice Brown', 'alicebrown@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1234567893', 'HR', 0, 1, '2025-08-16 04:54:36'),
(10, 'Department Manager', 'manager@yourcompany.com', '$2y$10$YourHashedPasswordHere', 'manager', '+1234567891', 'Management', 0, 1, '2025-08-16 06:12:35'),
(15, 'Operations Manager', 'opsmanager@yourcompany.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+1234567891', 'Management', 0, 1, '2025-08-16 06:17:53'),
(17, 'nurman 2', 'kontaknurman2@gmail.com', '$2y$10$n/TEF8RAS4z/P0QV.eTAFuKdA2mutiR3cc0v0jxFPKTcUmK9gHxny', 'manager', '', '', 0, 1, '2025-08-16 06:52:27'),
(18, 'Nurman', 'kontaknurman@gmail.com', '$2y$10$3pJ5DlRmqKFqEfYNrMC9S.rSoxOtLerSpuODL7ayyxHdNhrUWozC.', 'admin', '', 'Administration', 0, 1, '2025-08-16 07:30:26');

-- --------------------------------------------------------

--
-- Table structure for table `employees_backup`
--

CREATE TABLE `employees_backup` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','employee') DEFAULT 'employee',
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `total_points` int(11) DEFAULT 0,
  `notification_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `employees_backup`
--

INSERT INTO `employees_backup` (`id`, `name`, `email`, `password`, `role`, `whatsapp_number`, `department`, `total_points`, `notification_enabled`, `created_at`) VALUES
(2, 'Jane Smith', 'jane@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+1234567891', 'Marketing', 0, 1, '2025-08-16 04:54:36'),
(9, 'System Administrator', 'admin@yourcompany.com', '$2y$10$YourHashedPasswordHere', 'admin', '+1234567890', 'Administration', 0, 1, '2025-08-16 06:12:35'),
(10, 'Department Manager', 'manager@yourcompany.com', '$2y$10$YourHashedPasswordHere', 'manager', '+1234567891', 'Management', 0, 1, '2025-08-16 06:12:35');

-- --------------------------------------------------------

--
-- Stand-in structure for view `employee_active_departments`
-- (See below for the actual view)
--
CREATE TABLE `employee_active_departments` (
`employee_id` int(11)
,`employee_name` varchar(100)
,`department_id` int(11)
,`department_name` varchar(100)
,`department_code` varchar(20)
,`position` varchar(100)
,`is_head` tinyint(1)
,`joined_date` date
,`manager_id` int(11)
,`manager_name` varchar(100)
,`project_id` int(11)
,`project_name` varchar(255)
,`project_code` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `employee_active_projects`
-- (See below for the actual view)
--
CREATE TABLE `employee_active_projects` (
`employee_id` int(11)
,`employee_name` varchar(100)
,`project_id` int(11)
,`project_name` varchar(255)
,`project_code` varchar(50)
,`status` enum('planning','active','on_hold','completed','cancelled')
,`priority` enum('low','medium','high','urgent')
,`role_in_project` varchar(100)
,`assigned_date` date
,`start_date` date
,`end_date` date
,`progress` int(3)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL,
  `type` enum('new_task','deadline_reminder','overdue','status_change') NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','manager','employee') DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `module`, `can_view`, `can_create`, `can_edit`, `can_delete`) VALUES
(1, 'admin', 'employees', 1, 1, 1, 1),
(2, 'admin', 'tasks', 1, 1, 1, 1),
(3, 'admin', 'reports', 1, 1, 1, 1),
(4, 'admin', 'settings', 1, 1, 1, 1),
(5, 'manager', 'employees', 1, 0, 1, 0),
(6, 'manager', 'tasks', 1, 1, 1, 0),
(7, 'manager', 'reports', 1, 0, 0, 0),
(8, 'manager', 'settings', 0, 0, 0, 0),
(9, 'employee', 'employees', 0, 0, 0, 0),
(10, 'employee', 'tasks', 1, 1, 0, 0),
(11, 'employee', 'reports', 0, 0, 0, 0),
(12, 'employee', 'settings', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_code` varchar(50) DEFAULT NULL,
  `project_type` enum('fixed','lifetime') DEFAULT 'fixed',
  `description` text DEFAULT NULL,
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `project_manager_id` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `progress` int(3) DEFAULT 0,
  `total_tasks` int(11) DEFAULT 0,
  `completed_tasks` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `project_code`, `project_type`, `description`, `status`, `start_date`, `end_date`, `budget`, `created_by`, `project_manager_id`, `priority`, `progress`, `total_tasks`, `completed_tasks`, `created_at`, `updated_at`) VALUES
(11, 'Customer Support System', 'SUPPORT-2024', 'lifetime', 'Ongoing customer support and ticket management', 'active', NULL, NULL, 0.00, NULL, NULL, 'high', 0, 0, 0, '2025-08-16 07:51:46', '2025-08-16 07:51:46'),
(12, 'Maintenance & Updates', 'MAINT-2024', 'lifetime', 'Continuous system maintenance and updates', 'active', NULL, NULL, 0.00, NULL, NULL, 'medium', 0, 0, 0, '2025-08-16 07:51:46', '2025-08-16 07:51:46'),
(13, 'Research & Development', 'RND-2024', 'lifetime', 'Ongoing R&D initiatives', 'active', NULL, NULL, 0.00, NULL, NULL, 'medium', 0, 0, 0, '2025-08-16 07:51:46', '2025-08-16 07:51:46'),
(14, 'TopUpGim', 'TG', 'lifetime', 'TopUpGim Project', 'active', '2025-08-16', NULL, 0.00, 18, 17, 'urgent', 0, 0, 0, '2025-08-16 07:56:23', '2025-08-16 07:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

CREATE TABLE `project_members` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `role_in_project` varchar(100) DEFAULT 'Member',
  `assigned_date` date DEFAULT curdate(),
  `removed_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `hours_allocated` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_members`
--

INSERT INTO `project_members` (`id`, `project_id`, `employee_id`, `role_in_project`, `assigned_date`, `removed_date`, `is_active`, `hours_allocated`, `notes`, `assigned_by`, `created_at`) VALUES
(1, 14, 4, 'Member', '2025-08-16', NULL, 1, 0, NULL, 18, '2025-08-16 07:56:23'),
(2, 14, 3, 'Member', '2025-08-16', NULL, 1, 0, NULL, 18, '2025-08-16 07:56:23'),
(3, 14, 10, 'Member', '2025-08-16', NULL, 1, 0, NULL, 18, '2025-08-16 07:56:23'),
(4, 14, 18, 'Member', '2025-08-16', NULL, 1, 0, NULL, 18, '2025-08-16 07:56:23'),
(5, 14, 17, 'Project Manager', '2025-08-16', NULL, 1, 0, NULL, 18, '2025-08-16 07:56:23');

-- --------------------------------------------------------

--
-- Stand-in structure for view `project_summary`
-- (See below for the actual view)
--
CREATE TABLE `project_summary` (
`id` int(11)
,`project_name` varchar(255)
,`project_code` varchar(50)
,`project_type` enum('fixed','lifetime')
,`description` text
,`status` enum('planning','active','on_hold','completed','cancelled')
,`start_date` date
,`end_date` date
,`budget` decimal(15,2)
,`created_by` int(11)
,`project_manager_id` int(11)
,`priority` enum('low','medium','high','urgent')
,`progress` int(3)
,`total_tasks` int(11)
,`completed_tasks` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`created_by_name` varchar(100)
,`project_manager_name` varchar(100)
,`member_count` bigint(21)
,`task_count` bigint(21)
,`completed_task_count` bigint(21)
,`calculated_progress` decimal(25,1)
,`progress_display` varbinary(49)
);

-- --------------------------------------------------------

--
-- Table structure for table `project_tasks`
--

CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `task_name` varchar(255) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `project_tasks`
--
DELIMITER $$
CREATE TRIGGER `update_project_task_count_on_delete` AFTER DELETE ON `project_tasks` FOR EACH ROW BEGIN
    UPDATE projects 
    SET total_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = OLD.project_id),
        completed_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = OLD.project_id AND status = 'completed')
    WHERE id = OLD.project_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_project_task_count_on_insert` AFTER INSERT ON `project_tasks` FOR EACH ROW BEGIN
    UPDATE projects 
    SET total_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = NEW.project_id),
        completed_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = NEW.project_id AND status = 'completed')
    WHERE id = NEW.project_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_project_task_count_on_update` AFTER UPDATE ON `project_tasks` FOR EACH ROW BEGIN
    UPDATE projects 
    SET total_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = NEW.project_id),
        completed_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_id = NEW.project_id AND status = 'completed')
    WHERE id = NEW.project_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `repeat_days` varchar(20) DEFAULT NULL,
  `custom_days` varchar(50) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `time_limit_hours` int(11) DEFAULT 0,
  `time_limit_minutes` int(11) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','ongoing','finished','dropped','overdue') DEFAULT 'pending',
  `points` int(11) DEFAULT 10,
  `confirmation_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `employee_id`, `repeat_days`, `custom_days`, `due_date`, `due_time`, `time_limit_hours`, `time_limit_minutes`, `started_at`, `completed_at`, `status`, `points`, `confirmation_token`, `created_at`, `updated_at`) VALUES
(3, 'Client Calls', 'Follow up with clients', 3, 'none', NULL, '2025-08-18', '16:00:00', 0, 45, NULL, NULL, 'pending', 20, '7e2575359b74444b0d271fc4779ed18b', '2025-08-16 04:54:36', '2025-08-16 04:54:36');

-- --------------------------------------------------------

--
-- Table structure for table `task_history`
--

CREATE TABLE `task_history` (
  `id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `status_changed_from` varchar(20) DEFAULT NULL,
  `status_changed_to` varchar(20) DEFAULT NULL,
  `points_earned` int(11) DEFAULT 0,
  `time_taken_minutes` int(11) DEFAULT 0,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `admin_dashboard_stats`
--
DROP TABLE IF EXISTS `admin_dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `admin_dashboard_stats`  AS SELECT (select count(0) from `employees`) AS `total_employees`, (select count(0) from `employees` where `employees`.`role` = 'admin') AS `total_admins`, (select count(0) from `employees` where `employees`.`role` = 'manager') AS `total_managers`, (select count(0) from `employees` where `employees`.`role` = 'employee') AS `total_regular_employees`, (select count(0) from `tasks`) AS `total_tasks`, (select count(0) from `tasks` where `tasks`.`status` = 'pending') AS `pending_tasks`, (select count(0) from `tasks` where `tasks`.`status` = 'ongoing') AS `ongoing_tasks`, (select count(0) from `tasks` where `tasks`.`status` = 'finished') AS `completed_tasks`, (select count(0) from `tasks` where `tasks`.`status` = 'overdue') AS `overdue_tasks`, (select count(0) from `tasks` where `tasks`.`status` = 'dropped') AS `dropped_tasks`, (select sum(`employees`.`total_points`) from `employees`) AS `total_points_all`, (select avg(`employees`.`total_points`) from `employees`) AS `avg_points_per_employee`, (select count(0) from `notifications` where `notifications`.`is_read` = 0) AS `unread_notifications`, (select count(0) from `admin_logs` where cast(`admin_logs`.`created_at` as date) = curdate()) AS `todays_admin_activities`, (select count(distinct `admin_logs`.`admin_id`) from `admin_logs` where cast(`admin_logs`.`created_at` as date) = curdate()) AS `active_admins_today` ;

-- --------------------------------------------------------

--
-- Structure for view `department_summary`
--
DROP TABLE IF EXISTS `department_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `department_summary`  AS SELECT `d`.`id` AS `id`, `d`.`department_name` AS `department_name`, `d`.`department_code` AS `department_code`, `d`.`description` AS `description`, `d`.`project_id` AS `project_id`, `d`.`manager_id` AS `manager_id`, `d`.`location` AS `location`, `d`.`budget` AS `budget`, `d`.`status` AS `status`, `d`.`created_by` AS `created_by`, `d`.`created_at` AS `created_at`, `d`.`updated_at` AS `updated_at`, `mgr`.`name` AS `manager_name`, `creator`.`name` AS `created_by_name`, count(distinct `dm`.`employee_id`) AS `member_count`, `p`.`project_name` AS `project_name`, `p`.`project_code` AS `project_code`, `p`.`status` AS `project_status` FROM ((((`departments` `d` left join `employees` `mgr` on(`d`.`manager_id` = `mgr`.`id`)) left join `employees` `creator` on(`d`.`created_by` = `creator`.`id`)) left join `department_members` `dm` on(`d`.`id` = `dm`.`department_id` and `dm`.`is_active` = 1)) left join `projects` `p` on(`d`.`project_id` = `p`.`id`)) WHERE `d`.`status` = 'active' GROUP BY `d`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `employee_active_departments`
--
DROP TABLE IF EXISTS `employee_active_departments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `employee_active_departments`  AS SELECT `dm`.`employee_id` AS `employee_id`, `e`.`name` AS `employee_name`, `d`.`id` AS `department_id`, `d`.`department_name` AS `department_name`, `d`.`department_code` AS `department_code`, `dm`.`position` AS `position`, `dm`.`is_head` AS `is_head`, `dm`.`joined_date` AS `joined_date`, `d`.`manager_id` AS `manager_id`, `mgr`.`name` AS `manager_name`, `d`.`project_id` AS `project_id`, `p`.`project_name` AS `project_name`, `p`.`project_code` AS `project_code` FROM ((((`department_members` `dm` join `departments` `d` on(`dm`.`department_id` = `d`.`id`)) join `employees` `e` on(`dm`.`employee_id` = `e`.`id`)) left join `employees` `mgr` on(`d`.`manager_id` = `mgr`.`id`)) left join `projects` `p` on(`d`.`project_id` = `p`.`id`)) WHERE `dm`.`is_active` = 1 AND `d`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `employee_active_projects`
--
DROP TABLE IF EXISTS `employee_active_projects`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `employee_active_projects`  AS SELECT `pm`.`employee_id` AS `employee_id`, `e`.`name` AS `employee_name`, `p`.`id` AS `project_id`, `p`.`project_name` AS `project_name`, `p`.`project_code` AS `project_code`, `p`.`status` AS `status`, `p`.`priority` AS `priority`, `pm`.`role_in_project` AS `role_in_project`, `pm`.`assigned_date` AS `assigned_date`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, `p`.`progress` AS `progress` FROM ((`project_members` `pm` join `projects` `p` on(`pm`.`project_id` = `p`.`id`)) join `employees` `e` on(`pm`.`employee_id` = `e`.`id`)) WHERE `pm`.`is_active` = 1 AND `p`.`status` in ('planning','active') ;

-- --------------------------------------------------------

--
-- Structure for view `project_summary`
--
DROP TABLE IF EXISTS `project_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `project_summary`  AS SELECT `p`.`id` AS `id`, `p`.`project_name` AS `project_name`, `p`.`project_code` AS `project_code`, `p`.`project_type` AS `project_type`, `p`.`description` AS `description`, `p`.`status` AS `status`, `p`.`start_date` AS `start_date`, `p`.`end_date` AS `end_date`, `p`.`budget` AS `budget`, `p`.`created_by` AS `created_by`, `p`.`project_manager_id` AS `project_manager_id`, `p`.`priority` AS `priority`, `p`.`progress` AS `progress`, `p`.`total_tasks` AS `total_tasks`, `p`.`completed_tasks` AS `completed_tasks`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `creator`.`name` AS `created_by_name`, `pm_user`.`name` AS `project_manager_name`, count(distinct `pm`.`employee_id`) AS `member_count`, count(distinct `pt`.`id`) AS `task_count`, count(distinct case when `pt`.`status` = 'completed' then `pt`.`id` end) AS `completed_task_count`, CASE WHEN `p`.`project_type` = 'lifetime' AND count(distinct `pt`.`id`) > 0 THEN round(count(distinct case when `pt`.`status` = 'completed' then `pt`.`id` end) * 100.0 / count(distinct `pt`.`id`),1) WHEN `p`.`project_type` = 'fixed' THEN `p`.`progress` ELSE 0 END AS `calculated_progress`, CASE WHEN `p`.`project_type` = 'lifetime' THEN concat(count(distinct case when `pt`.`status` = 'completed' then `pt`.`id` end),'/',count(distinct `pt`.`id`),' tasks') ELSE concat(`p`.`progress`,'%') END AS `progress_display` FROM ((((`projects` `p` left join `employees` `creator` on(`p`.`created_by` = `creator`.`id`)) left join `employees` `pm_user` on(`p`.`project_manager_id` = `pm_user`.`id`)) left join `project_members` `pm` on(`p`.`id` = `pm`.`project_id` and `pm`.`is_active` = 1)) left join `project_tasks` `pt` on(`p`.`id` = `pt`.`project_id`)) GROUP BY `p`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`role`,`module`,`action`);

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_id` (`admin_id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Indexes for table `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_code` (`department_code`),
  ADD KEY `idx_manager` (`manager_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_dept_creator` (`created_by`);

--
-- Indexes for table `department_members`
--
ALTER TABLE `department_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_member_active` (`department_id`,`employee_id`,`is_active`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `fk_dm_assigner` (`assigned_by`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_total_points` (`total_points`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `idx_employee_read` (`employee_id`,`is_read`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_module` (`role`,`module`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_code` (`project_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_manager` (`project_manager_id`);

--
-- Indexes for table `project_members`
--
ALTER TABLE `project_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_member` (`project_id`,`employee_id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `fk_pm_assigner` (`assigned_by`);

--
-- Indexes for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_assigned` (`assigned_to`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `confirmation_token` (`confirmation_token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `task_history`
--
ALTER TABLE `task_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_task` (`task_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `department_members`
--
ALTER TABLE `department_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `project_members`
--
ALTER TABLE `project_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `project_tasks`
--
ALTER TABLE `project_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `task_history`
--
ALTER TABLE `task_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `fk_settings_admin` FOREIGN KEY (`admin_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `csrf_tokens`
--
ALTER TABLE `csrf_tokens`
  ADD CONSTRAINT `fk_csrf_user` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dept_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dept_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `department_members`
--
ALTER TABLE `department_members`
  ADD CONSTRAINT `fk_dm_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dm_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dm_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_project_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_manager` FOREIGN KEY (`project_manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_pm_assigner` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD CONSTRAINT `fk_pt_employee` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pt_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_history`
--
ALTER TABLE `task_history`
  ADD CONSTRAINT `task_history_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_history_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
