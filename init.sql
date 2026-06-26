-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2026 at 10:13 PM
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
-- Database: `motor_dealer_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `username`, `action`, `log_time`) VALUES
(1, 'Unknown', 'Logged into the system', '2025-10-25 15:30:55'),
(2, 'Unknown', 'Logged into the system', '2025-10-25 15:39:04'),
(3, 'Unknown', 'Logged into the system', '2025-10-25 15:43:45'),
(4, 'Unknown', 'Logged into the system', '2025-10-25 15:54:23'),
(5, 'Unknown', 'Logged into the system', '2025-10-25 16:33:03'),
(6, 'Unknown', 'Logged into the system', '2025-10-25 16:34:14'),
(7, 'Unknown', 'Logged into the system', '2025-10-25 23:34:02'),
(8, 'Unknown', 'Logged into the system', '2025-10-25 23:56:36'),
(9, 'Unknown', 'Logged into the system', '2025-10-25 23:56:45'),
(10, 'abdul', 'Edited user \'admin\' (ID: 3), role updated to \'admin\'', '2025-10-26 00:12:03'),
(11, 'abdul', 'Admin \'abdul\' updated user \'admin\' (ID: 3) — Role: admin.', '2025-10-26 00:17:19'),
(12, 'Unknown', 'Logged into the system', '2025-10-26 00:18:04'),
(13, 'admin', 'Edited sale #3 for Layla', '2025-10-26 00:19:26'),
(14, 'Unknown', 'Logged into the system', '2025-10-26 00:20:02'),
(15, 'Unknown', 'Logged into the system', '2025-10-26 00:20:45'),
(16, 'Unknown', 'Logged into the system', '2025-10-26 00:20:54'),
(17, 'waachey', 'User \'waachey\' logged into the system', '2025-10-26 00:25:48'),
(18, 'test', 'User \'test\' logged into the system', '2025-10-26 00:31:54'),
(19, 'admin', 'User \'admin\' logged into the system', '2025-10-26 00:32:36'),
(20, 'admin', 'User \'admin\' logged into the system', '2025-10-27 15:45:02'),
(21, 'admin', 'Made a sale to 123456 for ₵100000', '2025-10-27 15:55:57'),
(22, 'test', 'User \'test\' logged into the system', '2025-10-27 16:39:10'),
(23, 'admin', 'User \'admin\' logged into the system', '2025-10-27 16:51:56'),
(24, 'abdul', 'User \'abdul\' logged into the system', '2026-02-08 18:00:37'),
(25, 'abdul', 'User \'abdul\' logged into the system', '2026-02-08 18:10:15'),
(26, 'abdul', 'User \'abdul\' logged into the system', '2026-02-08 18:40:17'),
(27, 'abdul', 'User \'abdul\' logged into the system', '2026-02-10 13:32:34'),
(28, 'abdul', 'Deleted vehicle ID 1', '2026-02-10 14:09:19'),
(29, 'abdul', 'Deleted vehicle ID 1', '2026-02-10 14:09:32'),
(30, 'abdul', 'User \'abdul\' logged into the system', '2026-02-10 15:37:21'),
(31, 'waachey', 'User \'waachey\' logged into the system', '2026-02-10 15:43:29'),
(32, 'abdul', 'User \'abdul\' logged into the system', '2026-02-10 15:46:32'),
(33, 'waachey', 'User \'waachey\' logged into the system', '2026-02-10 16:14:00'),
(34, 'abdul', 'User \'abdul\' logged into the system', '2026-02-14 00:08:12'),
(35, 'waachey', 'User \'waachey\' logged into the system', '2026-02-14 00:09:40'),
(36, 'waachey', 'Deleted sale #5 (123456)', '2026-02-14 00:59:12'),
(37, 'waachey', 'Deleted vehicle #2 (Honda Accord) from inventory', '2026-02-14 01:21:52'),
(38, 'waachey', 'Deleted vehicle #4 (Acura MDX) from inventory', '2026-02-14 01:22:00'),
(39, 'waachey', 'User \'waachey\' logged into the system', '2026-02-14 01:27:07'),
(40, 'abdul', 'User \'abdul\' logged into the system', '2026-02-14 01:28:05'),
(41, 'waachey', 'User \'waachey\' logged into the system', '2026-02-14 01:28:56'),
(42, 'abdul', 'User \'abdul\' logged into the system', '2026-02-14 01:31:56'),
(43, 'waachey', 'User \'waachey\' logged into the system', '2026-02-14 01:33:54'),
(44, 'abdul', 'User \'abdul\' logged into the system', '2026-02-14 01:36:08'),
(45, 'waachey', 'User \'waachey\' logged into the system', '2026-02-14 01:37:09'),
(46, 'waachey', 'User \'waachey\' logged into the system', '2026-03-30 07:33:35'),
(47, 'waachey', 'User \'waachey\' logged into the system', '2026-06-26 20:01:07');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `sold_by` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `sale_date` date DEFAULT curdate(),
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `vehicle_id`, `sold_by`, `customer_name`, `sale_date`, `amount`, `payment_method`, `notes`, `created_at`) VALUES
(1, 1, 3, 'Awudu', '2025-10-23', 220000.00, 'Cash', '', '2025-10-25 00:04:51'),
(3, 3, 3, 'Layla', '2025-10-25', 450000.00, 'Bank Transfer', '', '2025-10-25 00:58:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','sales') DEFAULT 'sales',
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `reset_token`, `token_expiry`) VALUES
(3, 'admin', '', '$2y$10$6lhbHyv0Q79CiVEa9sEvnun5nLN47pybOMCuT54IKMFWmeb/axmAy', 'admin', NULL, NULL),
(5, 'waachey', 'waachey@gmail.com', '$2y$10$OALigagRNtBMYpHIP5OPruUC1Y1gdVZEv6Qj84lsqogfRsiurjGoe', 'admin', NULL, NULL),
(6, 'abdul', 'abdulrahamansalifu999@gmail.com', '$2y$10$dXVSlL0hYNMXZxTbFWR3sOs5zKxKBG8bgBWfcQAr/I.Q8UABbnewa', 'sales', NULL, NULL),
(7, 'test', 'test@gmail.com', '$2y$10$Ed94S7Z4oZty2dGDHfagKeznikYQwe8qOVv.N9B4JqKb9vjZnaRmK', 'sales', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `make` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` enum('available','sold') DEFAULT 'available',
  `image` varchar(255) DEFAULT NULL COMMENT 'Path to image file',
  `description` text DEFAULT NULL COMMENT 'Vehicle details'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `model`, `make`, `year`, `price`, `status`, `image`, `description`) VALUES
(1, 'Civic', 'Honda', 2019, 220000.00, 'sold', NULL, NULL),
(3, 'CRV', 'Honda', 2021, 450000.00, 'sold', NULL, NULL),
(5, 'Accord', 'Honda', NULL, 220000.00, 'available', 'uploads/1761351724_JDPA_2020 Honda Accord Touring Red Front View.jpg', '2020 Honda Accord');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `sold_by` (`sold_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`sold_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
