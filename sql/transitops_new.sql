-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2026 at 12:44 PM
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
-- Database: `transitops_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `license_category` varchar(20) NOT NULL,
  `license_expiry_date` date NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `safety_score` decimal(5,2) DEFAULT 100.00,
  `status` enum('Available','On_Trip','Off_Duty','Suspended') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `full_name`, `license_number`, `license_category`, `license_expiry_date`, `contact_number`, `safety_score`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mike Johnson', 'DL2024-001', 'Class B', '2025-12-31', '+1 555-0101', 95.50, 'Available', '2026-07-12 04:56:08', NULL),
(2, 'Sarah Wilson', 'DL2024-002', 'Class A', '2025-11-15', '+1 555-0102', 88.00, 'Available', '2026-07-12 04:56:08', NULL),
(3, 'David Chen', 'DL2024-003', 'Class C', '2025-06-20', '+1 555-0103', 92.00, 'Off_Duty', '2026-07-12 04:56:08', NULL),
(4, 'Emily Davis', 'DL2024-004', 'Class A', '2025-12-01', '+1 555-0104', 75.00, 'Suspended', '2026-07-12 04:56:08', NULL),
(5, 'Robert Taylor', 'DL2024-005', 'Class B', '2026-03-15', '+1 555-0105', 90.00, 'Available', '2026-07-12 04:56:08', NULL),
(6, 'Lisa Anderson', 'DL2024-006', 'Class A', '2025-09-30', '+1 555-0106', 85.50, 'On_Trip', '2026-07-12 04:56:08', NULL),
(7, 'Krushnraj Zala', 'GJ-01-2022', 'Class B', '2030-07-21', '1234567890', 99.90, 'Available', '2026-07-12 05:15:55', '2026-07-12 06:30:11'),
(8, 'Om Nagdev', 'GJ-02-2214', 'Class D', '2029-02-25', '7895254896', 100.00, 'Off_Duty', '2026-07-12 05:49:47', NULL),
(9, 'Rajesh Patel', 'GJ-09-8741', 'Class A', '2026-08-10', '7895632147', 80.00, 'Available', '2026-07-12 09:54:44', '2026-07-12 09:57:08');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `expense_type` enum('Fuel','Toll','Maintenance','Repair','Insurance','Other') NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `vehicle_id`, `expense_type`, `description`, `amount`, `expense_date`, `receipt_image`, `created_at`) VALUES
(1, 1, 'Toll', 'NY Toll Plaza - I-95', 15.50, '2024-01-01', NULL, '2026-07-12 04:56:08'),
(2, 1, 'Fuel', 'Fuel stop - Boston', 159.25, '2024-01-01', NULL, '2026-07-12 04:56:08'),
(3, 3, 'Toll', 'CA Toll Road - I-5', 22.00, '2024-01-02', NULL, '2026-07-12 04:56:08'),
(4, 5, 'Insurance', 'Monthly insurance premium', 350.00, '2024-01-05', NULL, '2026-07-12 04:56:08'),
(5, 2, 'Maintenance', 'Tire replacement - all 4 tires', 800.00, '2024-01-12', NULL, '2026-07-12 04:56:08'),
(6, 4, 'Repair', 'Engine repair parts', 2500.00, '2024-01-08', NULL, '2026-07-12 04:56:08'),
(7, 6, 'Fuel', 'Regular fuel fill-up', 195.25, '2024-01-06', NULL, '2026-07-12 04:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_logs`
--

CREATE TABLE `fuel_logs` (
  `fuel_log_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `trip_id` int(11) DEFAULT NULL,
  `liters` decimal(10,2) NOT NULL,
  `cost_per_liter` decimal(10,2) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL,
  `fuel_date` date NOT NULL,
  `odometer_reading` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fuel_logs`
--

INSERT INTO `fuel_logs` (`fuel_log_id`, `vehicle_id`, `trip_id`, `liters`, `cost_per_liter`, `total_cost`, `fuel_date`, `odometer_reading`, `created_at`) VALUES
(1, 1, 1, 45.50, 3.50, 159.25, '2024-01-01', 45200.00, '2026-07-12 04:56:08'),
(3, 5, 5, 85.00, 3.40, 289.00, '2024-01-05', 85500.00, '2026-07-12 04:56:08'),
(4, 6, NULL, 55.00, 3.55, 195.25, '2024-01-06', 28100.00, '2026-07-12 04:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `maintenance_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `cost` decimal(12,2) DEFAULT 0.00,
  `maintenance_date` date NOT NULL,
  `status` enum('Active','Closed') DEFAULT 'Active',
  `closed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`maintenance_id`, `vehicle_id`, `maintenance_type`, `description`, `cost`, `maintenance_date`, `status`, `closed_date`, `created_at`) VALUES
(1, 1, 'Oil Change', 'Regular oil change and filter replacement', 150.00, '2024-01-10', 'Closed', '2024-01-11', '2026-07-12 04:56:08'),
(2, 2, 'Tire Replacement', 'Replaced all 4 tires with winter tires', 800.00, '2024-01-12', 'Active', NULL, '2026-07-12 04:56:08'),
(3, 3, 'Brake Service', 'Brake pads and rotors replacement', 450.00, '2024-01-15', 'Closed', '2024-01-16', '2026-07-12 04:56:08'),
(4, 4, 'Engine Repair', 'Major engine overhaul - timing belt and water pump', 2500.00, '2024-01-08', 'Closed', '2026-07-12', '2026-07-12 04:56:08');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `trip_id` int(11) NOT NULL,
  `trip_number` varchar(50) NOT NULL,
  `source_location` varchar(200) NOT NULL,
  `destination_location` varchar(200) NOT NULL,
  `cargo_weight` decimal(10,2) NOT NULL,
  `planned_distance` decimal(10,2) NOT NULL,
  `actual_distance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Draft','Dispatched','Completed','Cancelled') DEFAULT 'Draft',
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`trip_id`, `trip_number`, `source_location`, `destination_location`, `cargo_weight`, `planned_distance`, `actual_distance`, `status`, `vehicle_id`, `driver_id`, `created_by`, `dispatched_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'TRP-20240101-0001', 'New York, NY', 'Boston, MA', 800.00, 215.00, 0.00, 'Completed', 1, 1, 1, '2024-01-01 02:30:00', '2024-01-01 09:00:00', '2026-07-12 04:56:08', NULL),
(4, 'TRP-20240104-0004', 'Miami, FL', 'Orlando, FL', 3000.00, 235.00, 0.00, 'Cancelled', 4, 4, 1, NULL, NULL, '2026-07-12 04:56:08', NULL),
(5, 'TRP-20240105-0005', 'Seattle, WA', 'Portland, OR', 1800.00, 174.00, 0.00, 'Completed', 5, 5, 1, '2024-01-05 02:00:00', '2024-01-05 06:45:00', '2026-07-12 04:56:08', NULL),
(9, 'TRP-20260712-8479', 'London', 'Manchester', 90.00, 1200.00, 0.00, 'Draft', 9, 9, 1, NULL, NULL, '2026-07-12 10:09:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('Fleet_Manager','Driver','Safety_Officer','Financial_Analyst') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `role`, `status`, `created_at`, `last_login`, `profile_image`) VALUES
(1, 'fleet_manager', 'niraj@gmail.com', '$2y$10$.IraJuT4qaj350ZbRyAvfOgq85uJaV4ByFjHLmel70yokzlOih6Lq', 'Niraj Badheka', 'Fleet_Manager', 'Active', '2026-07-12 04:56:08', '2026-07-12 10:20:46', NULL),
(2, 'driver1', 'anand@gmail.com', '$2y$10$iSC/ud6aEms4NbQ.J.1MIesJSuyNYrwzbW.9xUIilcvURgQ7UL4Ta', 'Anand Singh', 'Driver', 'Active', '2026-07-12 04:56:08', '2026-07-12 10:30:17', NULL),
(3, 'driver2', 'driver2@transitops.com', '$2y$10$XBu/0qq5NFdkqqvsfQuKHudjd65WvkIxJ2cJZ44jXcI4FE0KJuvQu', 'Sarah Wilson', 'Driver', 'Active', '2026-07-12 04:56:08', NULL, NULL),
(4, 'safety_officer', 'krushnraj@gmail.com', '$2y$10$kDVKzHzj5vrGkplKt4RSKuI/62w4idz1H3Ykc5kEkMbCnd9QPkBAC', 'Krushnraj Zala', 'Safety_Officer', 'Active', '2026-07-12 04:56:08', '2026-07-12 10:32:03', NULL),
(5, 'financial_analyst', 'krishn@gmail.com', '$2y$10$/urJ6UV8oCap.NRyUGwWveEWagiK/PzeB16ruQuno1vYH79juT.MK', 'Krishn Pitaliya', 'Financial_Analyst', 'Active', '2026-07-12 04:56:08', '2026-07-12 10:37:14', NULL),
(6, 'om_nagdev_197', 'om@gmail.com', '$2y$10$8S0syPxcSaNsZyIn4asDtumBdByLTyM8/JTtbZFs9v9rOSkiXxzAW', 'Om Nagdev', 'Driver', 'Active', '2026-07-12 05:49:47', '2026-07-12 07:28:23', NULL),
(7, 'rajesh_patel_128', 'rajesh@gmail.com', '$2y$10$t77cAgg2qqFe3HYsPxPkS.ae4b1ZaQfxDRyhY11.08tptNA57A9JG', 'Rajesh Patel', 'Driver', 'Active', '2026-07-12 09:54:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `model` varchar(100) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `max_load_capacity` decimal(10,2) NOT NULL,
  `odometer_reading` decimal(10,2) DEFAULT 0.00,
  `acquisition_cost` decimal(12,2) DEFAULT 0.00,
  `status` enum('Available','On_Trip','In_Shop','Retired') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `registration_number`, `model`, `vehicle_type`, `max_load_capacity`, `odometer_reading`, `acquisition_cost`, `status`, `created_at`, `updated_at`) VALUES
(1, 'VAN-001', 'Ford Transit 2023', 'Van', 1200.00, 45200.00, 35000.00, 'Available', '2026-07-12 04:56:08', NULL),
(2, 'VAN-002', 'Mercedes Sprinter', 'Van', 1500.00, 32100.00, 42000.00, 'Available', '2026-07-12 04:56:08', NULL),
(3, 'TRK-001', 'Volvo FH16', 'Truck', 5000.00, 120500.00, 85000.00, 'Available', '2026-07-12 04:56:08', NULL),
(4, 'TRK-002', 'Scania R450', 'Truck', 4800.00, 98200.00, 78000.00, 'Available', '2026-07-12 04:56:08', '2026-07-12 10:28:18'),
(5, 'TRK-003', 'MAN TGX', 'Truck', 5200.00, 85500.00, 92000.00, 'Available', '2026-07-12 04:56:08', '2026-07-12 06:30:11'),
(6, 'VAN-003', 'Renault Master', 'Van', 1300.00, 28100.00, 38000.00, 'On_Trip', '2026-07-12 04:56:08', NULL),
(7, 'TRK-004', 'DAF XF', 'Truck', 4600.00, 150500.00, 72000.00, 'Retired', '2026-07-12 04:56:08', NULL),
(8, 'Van-1010', 'Ford Transit 20202', 'Van', 1000.00, 100.00, 10000.00, 'On_Trip', '2026-07-12 05:09:26', NULL),
(9, 'NRB-007', 'Volvo 22', 'Car', 100.00, 1000.00, 10.00, 'Available', '2026-07-12 10:06:51', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_drivers_status` (`status`),
  ADD KEY `idx_drivers_license` (`license_number`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `idx_expenses_vehicle` (`vehicle_id`),
  ADD KEY `idx_expenses_type` (`expense_type`);

--
-- Indexes for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD PRIMARY KEY (`fuel_log_id`),
  ADD KEY `idx_fuel_logs_vehicle` (`vehicle_id`),
  ADD KEY `idx_fuel_logs_trip` (`trip_id`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `idx_maintenance_vehicle` (`vehicle_id`),
  ADD KEY `idx_maintenance_status` (`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`trip_id`),
  ADD UNIQUE KEY `trip_number` (`trip_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_trips_status` (`status`),
  ADD KEY `idx_trips_vehicle` (`vehicle_id`),
  ADD KEY `idx_trips_driver` (`driver_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_vehicles_status` (`status`),
  ADD KEY `idx_vehicles_type` (`vehicle_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  MODIFY `fuel_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `trip_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `fuel_logs`
--
ALTER TABLE `fuel_logs`
  ADD CONSTRAINT `fuel_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  ADD CONSTRAINT `fuel_logs_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`trip_id`);

--
-- Constraints for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  ADD CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  ADD CONSTRAINT `trips_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
