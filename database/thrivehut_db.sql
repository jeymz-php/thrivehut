-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2025 at 05:10 PM
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
-- Database: `thrivehut_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `Product_ID` varchar(20) NOT NULL,
  `Brand` varchar(100) NOT NULL,
  `Product_Name` varchar(150) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `Date_Acquired` date NOT NULL,
  `Expiration_Date` date DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`Product_ID`, `Brand`, `Product_Name`, `Type`, `Quantity`, `Date_Acquired`, `Expiration_Date`, `Price`, `status`) VALUES
('20251001', 'RS8', 'RS8 Enginer Lube', 'Parts', 12, '2025-12-12', NULL, 200.00, 'active'),
('20251002', 'Platinum', 'Radiator Coolant', 'Products', 0, '2025-12-20', NULL, 140.00, 'active'),
('20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 'Products', 0, '2025-03-03', '2029-10-10', 380.00, 'active'),
('20251004', 'MAKOTO', 'Alloy Rim', 'Parts', 1, '2025-06-24', NULL, 1225.00, 'archived'),
('20251005', 'EURO MOTORS', 'MAKOTO LIGHT ASSY, HEAD', 'Parts', 2, '2025-06-24', NULL, 6500.00, 'active'),
('20251006', 'Prestone', 'Prestone Super Heavy Duty Brake Fluid', 'Products', 12, '2025-06-24', NULL, 139.00, 'active'),
('20251007', 'Zic', 'Zic M7 4AT 10W-40 Synthetic Scooter Engine Oil', 'Products', 5, '2025-06-24', '2029-05-08', 279.00, 'active'),
('20251008', 'Sparco', 'Sparco Led Smd Bulbs SPL121 T11X 36 SV.5 Festoon (Set of 2)', 'Parts', 9, '2025-06-24', NULL, 499.00, 'active'),
('20251009', 'Meguiar\'s', 'Meguiar\'s Hybrid Ceramic Liquid Wax', 'Products', 2, '2025-06-24', NULL, 1739.00, 'active'),
('20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 'Products', 258, '2025-06-24', NULL, 139.00, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `item_sales`
--

CREATE TABLE `item_sales` (
  `Transaction_Number` varchar(20) NOT NULL,
  `Date` date NOT NULL,
  `Product_ID` varchar(20) NOT NULL,
  `Brand` varchar(100) NOT NULL,
  `Product_Name` varchar(150) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Date_Acquired` date NOT NULL,
  `Expiration_Date` date DEFAULT NULL,
  `Type` varchar(50) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `returned_quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_sales`
--

INSERT INTO `item_sales` (`Transaction_Number`, `Date`, `Product_ID`, `Brand`, `Product_Name`, `Quantity`, `Date_Acquired`, `Expiration_Date`, `Type`, `Price`, `returned_quantity`) VALUES
('4', '0000-00-00', '20251001', 'RS8', 'RS8 Enginer Lube', 1, '0000-00-00', NULL, 'Parts', 200.00, 0),
('4202001', '0000-00-00', '20251003', '', '', 3, '0000-00-00', NULL, '', 1140.00, 0),
('4202002', '0000-00-00', '20251001', '', '', 3, '0000-00-00', NULL, '', 600.00, 0),
('4202003', '0000-00-00', '20251001', '', '', 4, '0000-00-00', NULL, '', 800.00, 0),
('4202004', '0000-00-00', '20251002', '', '', 1, '0000-00-00', NULL, '', 140.00, 0),
('4202005', '0000-00-00', '20251002', '', '', 1, '0000-00-00', NULL, '', 140.00, 0),
('4202006', '0000-00-00', '20251001', 'RS8', 'RS8 Enginer Lube', 1, '0000-00-00', NULL, 'Parts', 200.00, 0),
('4202007', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 2, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202008', '0000-00-00', '20251001', 'RS8', 'RS8 Enginer Lube', 3, '0000-00-00', NULL, 'Parts', 200.00, 0),
('4202009', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 3, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202010', '0000-00-00', '20251008', 'Sparco', 'Sparco Led Smd Bulbs SPL121 T11X 36 SV.5 Festoon (Set of 2)', 1, '0000-00-00', NULL, 'Parts', 499.00, 0),
('4202011', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 1, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202012', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 3, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202013', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 2, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202013', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 1, '0000-00-00', NULL, 'Products', 380.00, 0),
('4202014', '0000-00-00', '20251008', 'Sparco', 'Sparco Led Smd Bulbs SPL121 T11X 36 SV.5 Festoon (Set of 2)', 1, '0000-00-00', NULL, 'Parts', 499.00, 1),
('4202015', '0000-00-00', '20251001', 'RS8', 'RS8 Enginer Lube', 2, '0000-00-00', NULL, 'Parts', 200.00, 1),
('4202015', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 1, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202015', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 1, '0000-00-00', NULL, 'Products', 380.00, 0),
('4202015', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 1, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202016', '0000-00-00', '20251005', 'EURO MOTORS', 'MAKOTO LIGHT ASSY, HEAD', 1, '0000-00-00', NULL, 'Parts', 6500.00, 0),
('4202017', '0000-00-00', '20251004', 'MAKOTO', 'Alloy Rim', 1, '0000-00-00', NULL, 'Parts', 1225.00, 0),
('4202017', '0000-00-00', '20251005', 'EURO MOTORS', 'MAKOTO LIGHT ASSY, HEAD', 1, '0000-00-00', NULL, 'Parts', 6500.00, 1),
('4202018', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 1, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202018', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 2, '0000-00-00', NULL, 'Products', 380.00, 0),
('4202019', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 2, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202020', '0000-00-00', '20251007', 'Zic', 'Zic M7 4AT 10W-40 Synthetic Scooter Engine Oil', 2, '0000-00-00', NULL, 'Products', 279.00, 0),
('4202020', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 1, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202021', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 1, '0000-00-00', NULL, 'Products', 140.00, 0),
('4202021', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 2, '0000-00-00', NULL, 'Products', 380.00, 0),
('4202021', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 1, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202022', '0000-00-00', '20251001', 'RS8', 'RS8 Enginer Lube', 1, '0000-00-00', NULL, 'Parts', 200.00, 0),
('4202022', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 2, '0000-00-00', NULL, 'Products', 380.00, 0),
('4202023', '0000-00-00', '20251006', 'Prestone', 'Prestone Super Heavy Duty Brake Fluid', 1, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202024', '0000-00-00', '20251009', 'Meguiar\'s', 'Meguiar\'s Hybrid Ceramic Liquid Wax', 1, '0000-00-00', NULL, 'Products', 1739.00, 0),
('4202025', '0000-00-00', '20251009', 'Meguiar\'s', 'Meguiar\'s Hybrid Ceramic Liquid Wax', 1, '0000-00-00', NULL, 'Products', 1739.00, 0),
('4202026', '0000-00-00', '20251010', 'Microtex', 'Microtex Tire Black MA-T250 250ml', 1, '0000-00-00', NULL, 'Products', 139.00, 0),
('4202027', '0000-00-00', '20251005', 'EURO MOTORS', 'MAKOTO LIGHT ASSY, HEAD', 1, '0000-00-00', NULL, 'Parts', 6500.00, 0),
('5', '0000-00-00', '20251002', 'Platinum', 'Radiator Coolant', 1, '0000-00-00', NULL, 'Products', 140.00, 0),
('5', '0000-00-00', '20251003', 'YAMAHA', 'YAMAHA ENGINE OIL', 1, '0000-00-00', NULL, 'Products', 380.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `returned_item`
--

CREATE TABLE `returned_item` (
  `Product_ID` int(11) NOT NULL,
  `Brand` varchar(255) NOT NULL,
  `Product_Name` varchar(255) NOT NULL,
  `Type` varchar(50) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Date_Acquired` date DEFAULT NULL,
  `Expiration_Date` date DEFAULT NULL,
  `Reason_of_Return_Item` text DEFAULT NULL,
  `Returned_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returned_item`
--

INSERT INTO `returned_item` (`Product_ID`, `Brand`, `Product_Name`, `Type`, `Quantity`, `Price`, `Date_Acquired`, `Expiration_Date`, `Reason_of_Return_Item`, `Returned_At`) VALUES
(20251001, 'RS8', 'RS8 Enginer Lube', 'Parts', 2, 200.00, NULL, NULL, 'PRODUCT WAS FOUND OPEN', '2025-06-25 13:37:00'),
(20251001, 'RS8', 'RS8 Enginer Lube', 'Parts', 1, 200.00, NULL, NULL, 'PRODUCT WAS FOUND OPEN', '2025-06-25 13:43:21'),
(20251005, 'EURO MOTORS', 'MAKOTO LIGHT ASSY, HEAD', 'Parts', 1, 6500.00, NULL, NULL, 'DEFECTIVE ITEM', '2025-06-25 13:44:08'),
(20251008, 'Sparco', 'Sparco Led Smd Bulbs SPL121 T11X 36 SV.5 Festoon (Set of 2)', 'Parts', 1, 499.00, NULL, NULL, 'DEFECTIVE ITEM', '2025-06-26 14:31:31'),
(20251010, 'Microtex', 'Microtex Tire Black MA-T250 250ml', 'Products', 1, 139.00, NULL, NULL, 'PRODUCT WAS FOUND OPEN', '2025-06-25 13:35:05'),
(20251010, 'Microtex', 'Microtex Tire Black MA-T250 250ml', 'Products', 2, 139.00, NULL, NULL, 'PRODUCT WAS FOUND OPEN', '2025-06-25 13:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `transaction_number` varchar(32) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','cashless') NOT NULL,
  `reference_code` varchar(64) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `gcash_amount` decimal(10,2) DEFAULT NULL,
  `gcash_account_name` varchar(255) DEFAULT NULL,
  `gcash_reference_num` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `returned_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `transaction_number`, `date`, `payment_method`, `reference_code`, `price`, `discount`, `cash_amount`, `change_amount`, `gcash_amount`, `gcash_account_name`, `gcash_reference_num`, `created_at`, `returned_amount`) VALUES
(1, NULL, '2025-06-25 01:13:09', 'cash', NULL, 100.00, 0.00, 100.00, NULL, NULL, NULL, NULL, '2025-06-24 17:13:09', 0.00),
(2, NULL, '2025-06-25 01:20:06', 'cash', NULL, 100.00, 0.00, 100.00, NULL, NULL, NULL, NULL, '2025-06-24 17:20:06', 0.00),
(4, '4', '2025-06-25 01:21:40', 'cash', NULL, 200.00, 0.00, 200.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:21:40', 0.00),
(5, '5', '2025-06-25 01:25:34', 'cash', NULL, 520.00, 0.00, 520.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:25:34', 0.00),
(4202013, '4202013', '2025-06-25 01:28:09', 'cash', NULL, 660.00, 0.00, 660.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:28:09', 0.00),
(4202014, '4202014', '2025-06-25 01:30:01', 'cash', NULL, 0.00, 0.00, 499.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:30:01', 499.00),
(4202015, '4202015', '2025-06-25 01:30:32', 'cash', NULL, 859.00, 0.00, 1059.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:30:32', 200.00),
(4202016, '4202016', '2025-06-25 01:34:17', 'cashless', NULL, 6500.00, 0.00, 0.00, NULL, 6500.00, 'JAMES RYAN GREGORIO', NULL, '2025-06-24 17:34:17', 0.00),
(4202017, '4202017', '2025-06-25 01:36:19', 'cash', NULL, 1225.00, 0.00, 7725.00, NULL, 0.00, NULL, NULL, '2025-06-24 17:36:19', 6500.00),
(4202018, '4202018', '2025-06-25 01:38:10', 'cashless', NULL, 900.00, 0.00, 0.00, NULL, 900.00, 'JAMES RYAN GREGORIO', NULL, '2025-06-24 17:38:10', 0.00),
(4202019, '4202019', '2025-06-25 01:40:08', 'cash', NULL, 278.00, 0.00, 300.00, 22.00, 0.00, NULL, NULL, '2025-06-24 17:40:08', 0.00),
(4202020, '4202020', '2025-06-25 21:29:52', 'cash', NULL, 697.00, 0.00, 1000.00, 303.00, 0.00, NULL, NULL, '2025-06-25 13:29:52', 0.00),
(4202021, '4202021', '2025-06-25 21:30:55', 'cashless', NULL, 1039.00, 0.00, 0.00, 0.00, 1039.00, 'JAMES RYAN GREGORIO', NULL, '2025-06-25 13:30:55', 0.00),
(4202022, '4202022', '2025-06-26 18:36:08', 'cash', NULL, 768.00, 192.00, 800.00, 32.00, 0.00, NULL, NULL, '2025-06-26 10:36:08', 0.00),
(4202023, '4202023', '2025-06-26 22:46:56', 'cash', NULL, 139.00, 0.00, 150.00, 11.00, 0.00, NULL, NULL, '2025-06-26 14:46:56', 0.00),
(4202024, '4202024', '2025-06-26 22:47:28', 'cashless', NULL, 1739.00, 0.00, 0.00, 0.00, 1739.00, 'JAMES RYAN GREGORIO', NULL, '2025-06-26 14:47:28', 0.00),
(4202025, '4202025', '2025-06-26 23:00:54', 'cash', NULL, 1739.00, 0.00, 2000.00, 261.00, 0.00, NULL, NULL, '2025-06-26 15:00:54', 0.00),
(4202026, '4202026', '2025-06-26 23:01:25', 'cash', NULL, 139.00, 0.00, 200.00, 61.00, 0.00, NULL, NULL, '2025-06-26 15:01:25', 0.00),
(4202027, '4202027', '2025-06-26 23:06:47', 'cash', NULL, 5200.00, 1300.00, 5200.00, 0.00, 0.00, NULL, NULL, '2025-06-26 15:06:47', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `services_sales`
--

CREATE TABLE `services_sales` (
  `id` int(11) NOT NULL,
  `transaction_number` varchar(32) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(32) NOT NULL,
  `cash_amount` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `gcash_amount` decimal(10,2) DEFAULT 0.00,
  `gcash_account_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services_sales`
--

INSERT INTO `services_sales` (`id`, `transaction_number`, `total_price`, `discount`, `payment_method`, `cash_amount`, `change_amount`, `gcash_amount`, `gcash_account_name`, `created_at`) VALUES
(1, 'S4202010001', 250.00, 0.00, 'cash', 250.00, 0.00, 0.00, NULL, '2025-06-26 11:22:11');

-- --------------------------------------------------------

--
-- Table structure for table `services_sales_items`
--

CREATE TABLE `services_sales_items` (
  `id` int(11) NOT NULL,
  `services_sales_id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services_sales_items`
--

INSERT INTO `services_sales_items` (`id`, `services_sales_id`, `service_name`, `price`, `quantity`) VALUES
(1, 1, 'Change Oil', 150.00, 1),
(2, 1, 'Change Gear Oil', 100.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','manager') NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`) VALUES
(1, 'owner', '$2y$10$r9hO.jEtlcHSrtD9mcGS3uizWjanot7exgUBZvdy3ijHSFFkaNoMa', 'owner', 'active'),
(2, 'manager', '$2y$10$qcAcEwJzKqd1WtZl4cE6xuk8zwUJTWAs09GdrSoFmwTPAYMkmObjW', 'manager', 'active'),
(5, 'managerjeymz', '$2y$10$CEasmWcUzHhpSm0OCBIb/OUvaAfP.cJtbpIhbv.ts2wow0hnD/VOS', 'manager', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`Product_ID`);

--
-- Indexes for table `item_sales`
--
ALTER TABLE `item_sales`
  ADD PRIMARY KEY (`Transaction_Number`,`Product_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `returned_item`
--
ALTER TABLE `returned_item`
  ADD PRIMARY KEY (`Product_ID`,`Returned_At`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`);

--
-- Indexes for table `services_sales`
--
ALTER TABLE `services_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`);

--
-- Indexes for table `services_sales_items`
--
ALTER TABLE `services_sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_sales_id` (`services_sales_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4202028;

--
-- AUTO_INCREMENT for table `services_sales`
--
ALTER TABLE `services_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services_sales_items`
--
ALTER TABLE `services_sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `item_sales`
--
ALTER TABLE `item_sales`
  ADD CONSTRAINT `item_sales_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `inventory` (`Product_ID`);

--
-- Constraints for table `services_sales_items`
--
ALTER TABLE `services_sales_items`
  ADD CONSTRAINT `services_sales_items_ibfk_1` FOREIGN KEY (`services_sales_id`) REFERENCES `services_sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
