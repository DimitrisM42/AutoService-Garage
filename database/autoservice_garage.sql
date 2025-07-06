-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2025 at 04:25 PM
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
-- Database: `autoservice_garage`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` enum('repair','service') NOT NULL,
  `problem_description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('CREATED','IN_PROGRESS','COMPLETED','CANCELLED') DEFAULT 'CREATED',
  `cost` decimal(10,2) DEFAULT 0.00,
  `car_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `mechanic_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_date`, `appointment_time`, `reason`, `problem_description`, `created_at`, `status`, `cost`, `car_id`, `customer_id`, `mechanic_id`) VALUES
(12, '2025-04-18', '08:00:00', 'repair', '', '2025-04-17 14:49:58', 'COMPLETED', 0.00, 1, 2, 6),
(13, '2025-04-18', '08:00:00', 'repair', '', '2025-04-17 14:50:24', 'CANCELLED', 0.00, 1, 2, 4),
(14, '2025-04-27', '11:00:00', 'service', '', '2025-04-19 16:28:03', 'CANCELLED', 40.00, 2, 2, 6),
(15, '2025-04-19', '14:00:00', 'service', '', '2025-04-19 22:21:02', 'CANCELLED', 0.00, 1, 2, 4),
(16, '2025-04-20', '14:00:00', 'repair', '', '2025-04-19 22:27:29', 'CANCELLED', 0.00, 1, 2, 6),
(17, '2025-04-25', '14:00:00', 'repair', '', '2025-04-25 17:35:21', 'CANCELLED', 100.00, 1, 2, 6),
(20, '2025-04-17', '10:00:00', 'repair', '', '2025-04-27 22:17:02', 'CANCELLED', 0.00, 1, 2, 6),
(21, '2025-04-30', '10:00:00', 'service', '', '2025-04-28 00:21:26', 'CANCELLED', 0.00, 2, 2, 6),
(23, '2025-06-10', '10:00:00', 'repair', 'repair', '2025-06-10 10:28:55', 'COMPLETED', 80.00, 1, 2, 6),
(24, '2025-06-10', '12:00:00', 'service', '', '2025-06-10 11:13:22', 'CANCELLED', 0.00, 2, 2, 6),
(26, '2025-06-15', '14:00:00', 'repair', '', '2025-06-14 13:28:52', 'COMPLETED', 45.00, 1, 2, 6),
(27, '2025-06-15', '08:00:00', 'repair', '', '2025-06-15 11:25:44', 'CANCELLED', 0.00, 1, 2, 6),
(30, '2025-06-20', '12:00:00', 'service', '', '2025-06-20 11:37:54', 'COMPLETED', 40.00, 2, 2, 4),
(31, '2025-06-20', '12:00:00', 'repair', '', '2025-06-20 12:57:18', 'CANCELLED', 0.00, 1, 2, 6),
(35, '2025-06-21', '12:00:00', 'repair', 'test', '2025-06-20 19:59:43', 'COMPLETED', 28.00, 1, 2, 6),
(39, '2025-06-21', '14:00:00', 'service', '', '2025-06-20 21:22:02', 'CANCELLED', 45.00, 20, 49, 4),
(40, '2025-06-22', '10:00:00', 'service', '', '2025-06-21 21:58:25', 'CANCELLED', 0.00, 1, 2, 4),
(41, '2025-06-22', '10:00:00', 'service', '', '2025-06-21 22:04:27', 'CANCELLED', 0.00, 2, 2, 6),
(42, '2025-06-22', '12:00:00', 'service', '', '2025-06-21 22:06:00', 'COMPLETED', 92.00, 1, 2, 6),
(43, '2025-06-22', '12:00:00', 'repair', '', '2025-06-21 22:11:59', 'COMPLETED', 65.00, 16, 3, 4);

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `serial_number` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `type` enum('sedan','truck','bus') NOT NULL,
  `engine_type` enum('electric','diesel','gas','hybrid') NOT NULL,
  `door_count` int(11) NOT NULL,
  `wheel_count` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `acquisition_year` year(4) NOT NULL,
  `customer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `serial_number`, `model`, `brand`, `type`, `engine_type`, `door_count`, `wheel_count`, `production_date`, `acquisition_year`, `customer_id`) VALUES
(1, 'c12341234', 'model1', 'brand1', 'sedan', 'hybrid', 4, 4, '2013-07-14', '2020', 2),
(2, 'c12341233', 'model2', 'brand2', 'sedan', 'electric', 2, 4, '2018-04-20', '2020', 2),
(16, 'CAR-003', 'Model 3', 'Tesla', 'sedan', 'electric', 4, 4, '2021-02-20', '2022', 3),
(20, '12345678910', 'model5', 'brand5', 'sedan', 'diesel', 4, 4, '2015-02-20', '2018', 49);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `user_id` int(11) NOT NULL,
  `vat_number` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`user_id`, `vat_number`, `address`) VALUES
(2, '12341234', '1st cust 11'),
(3, '23452345', '2nd cust 22'),
(9, '123446789', '3rd cust 33'),
(49, '87654321', 'test address');

-- --------------------------------------------------------

--
-- Table structure for table `job`
--

CREATE TABLE `job` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `materials` text NOT NULL,
  `duration` time NOT NULL,
  `cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job`
--

INSERT INTO `job` (`id`, `appointment_id`, `description`, `materials`, `duration`, `cost`) VALUES
(1, 14, 'test', 'material1 material2', '01:10:00', 10.00),
(2, 14, 'testsssss', 'matssssssssss\r\n', '01:00:00', 30.00),
(5, 17, 'aasdfasdf', 'asdfasdfddd', '01:00:00', 80.00),
(6, 17, 'testtest', 'testmat', '01:00:00', 20.00),
(8, 23, 'τεστ', 'τεστ', '01:10:00', 30.00),
(9, 23, 'Test2', 'Test234 test432 test56789 test53728', '00:50:00', 50.00),
(11, 26, 'test', 'test', '01:05:00', 45.00),
(12, 30, ' τεστ', 'τεστ', '00:10:00', 40.00),
(17, 39, 'Test', 'Test1 test2 test3', '01:10:00', 45.00),
(18, 35, 'desc', 'mat1 mat2', '00:30:00', 28.00),
(19, 42, 'ετεστ', 'τεστ1 32352', '01:05:00', 92.00),
(20, 43, 'ασδφ', 'ασδφ', '00:45:00', 65.00);

-- --------------------------------------------------------

--
-- Table structure for table `mechanics`
--

CREATE TABLE `mechanics` (
  `user_id` int(11) NOT NULL,
  `specialty` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mechanics`
--

INSERT INTO `mechanics` (`user_id`, `specialty`) VALUES
(4, 'general'),
(6, 'electrician');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','mechanic','secretary') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `full_name`, `id_number`, `is_active`) VALUES
(2, 'cust1', 'cust1@mail.com', '$2y$10$BlmbvvPH9Zsd6Sks6Oz21O7mgzYVuML73S3GW5Fn/xvPrpGDbWREK', 'customer', 'customer1', 'an12341234', 1),
(3, 'cust2', 'cust2@mail.com', '$2y$10$rNnOOG/PjatBswqqhpRxRenPwZ978VdAzxqqzUMQHYEcVHj0Q7ope', 'customer', 'customer2', 'an23452345', 1),
(4, 'mech1', 'mech1@mail.com', '$2y$10$nhD.nRSc3VGHPSxkMcupyu63crdP6SJjc5vZIoGzrvj5yHWJPil6e', 'mechanic', 'mechanic1', 'am123123123', 1),
(5, 'secr1', 'secr1@mail.com', '$2a$12$7IocKG6hGqi7d3rAK0T9FeqqPX8nwq5ffJ9lNYH6vfUH/PdSXCxQm', 'secretary', 'secretary1', 'as12341234', 1),
(6, 'mech2', 'mech2@mail.com', '$2y$10$Lf0dJkyBA1Anz3FxYuBDVORVBNOl7hRfPn1TjJ4yWLj0Hh5DPgRyW', 'mechanic', 'mechanic2', 'am234234234', 1),
(9, 'cust3', 'cust3@mail.com', '$2y$10$UDzxTKcy4YWzrNGLtd3iNuhhmhVkZprcrmsLCjf6Z5fSinx1Bf3TW', 'customer', 'customer3', 'An7636789', 1),
(49, 'test', 'test@gmail.com', '$2y$10$A4CDxlILlrN8Lj3SrWaZ7ekw5UasaHH6Gk6Hka9u3j0VnomPHZvPW', 'customer', 'test', '123456781', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `mechanic_id` (`mechanic_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `job`
--
ALTER TABLE `job`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `job`
--
ALTER TABLE `job`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`user_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`mechanic_id`) REFERENCES `mechanics` (`user_id`);

--
-- Constraints for table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job`
--
ALTER TABLE `job`
  ADD CONSTRAINT `job_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mechanics`
--
ALTER TABLE `mechanics`
  ADD CONSTRAINT `mechanics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
