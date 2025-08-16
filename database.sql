-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 16, 2025 at 05:40 AM
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
(1, 'John Doe', 'john@company.com', '$2y$10$HuUEq5DPy74huwvN4.2v.OdlGpyTHrLU3.37RP4j5X8khf4wajghm', 'employee', '+1234567895', 'Development', 0, 1, '2025-08-16 04:54:36'),
(2, 'Jane Smith', 'jane@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', '+1234567891', 'Marketing', 0, 1, '2025-08-16 04:54:36'),
(3, 'Bob Johnson', 'bob@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1234567892', 'Sales', 0, 1, '2025-08-16 04:54:36'),
(4, 'Alice Brown', 'alice@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', '+1234567893', 'HR', 0, 1, '2025-08-16 04:54:36'),
(7, 'Super Admin', 'admin@company.com', '$2y$10$mU3ct3Molo1DMhn7H0FnA.Bg7tcje4Ppq.dPAHzS5E1hkQcQEsgXa', 'admin', '+1000000000', 'Administration', 0, 1, '2025-08-16 05:31:03'),
(8, 'Manager One', 'manager@company.com', '$2y$10$qVkVpNMKVNQFZMizQ0dYsOJqGxlplqRfRXL5wqe0wH5cVfGvYqnLa', 'manager', '+1000000001', 'Management', 0, 1, '2025-08-16 05:31:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
