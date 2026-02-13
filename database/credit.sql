-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 24, 2023 at 06:59 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `credit`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `admin_name` text NOT NULL,
  `admin_password` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill`
--

CREATE TABLE `bill` (
  `bill_id` varchar(11) NOT NULL,
  `cutomer_id` varchar(11) NOT NULL,
  `picklist_id` varchar(11) NOT NULL,
  `bill_amount` bigint(20) NOT NULL,
  `bill_date` date NOT NULL,
  `Bill_status` enum('NILL','ISSUED','INFILE') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_ledger`
--

CREATE TABLE `bill_ledger` (
  `ledger_id` int(11) NOT NULL,
  `ledger_date` date NOT NULL,
  `customer_id` text NOT NULL,
  `bill_id` text NOT NULL,
  `bill_amount` bigint(20) NOT NULL,
  `recived_amount` bigint(20) NOT NULL,
  `remaining_amount` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` varchar(12) NOT NULL,
  `customer_name` text DEFAULT NULL,
  `customer_route` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_issued`
--

CREATE TABLE `invoice_issued` (
  `issued_invoice_id` int(11) NOT NULL,
  `issued_invoice_date` date NOT NULL,
  `invoice_id` text NOT NULL,
  `customer_id` text NOT NULL,
  `invoice_amount` bigint(20) NOT NULL,
  `recived_amount` bigint(20) NOT NULL,
  `bill_status` enum('Nill','BF','Returned') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `picklist`
--

CREATE TABLE `picklist` (
  `picklist_id` varchar(11) NOT NULL,
  `picklist_date` date NOT NULL,
  `picklist_amount` bigint(20) NOT NULL,
  `picklist_recovery` bigint(20) NOT NULL,
  `picklist_credit` bigint(20) NOT NULL,
  `picklist_sceheme_amount` bigint(20) NOT NULL,
  `picklist_return` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_sheet`
--

CREATE TABLE `recovery_sheet` (
  `recovery_id` int(11) NOT NULL,
  `recovery_date` date NOT NULL,
  `recovery_sheet_saleman_id` int(11) NOT NULL,
  `recovery_sheet_amount` decimal(10,2) DEFAULT NULL,
  `recovery_sheet_recovery` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_sheet_detail`
--

CREATE TABLE `recovery_sheet_detail` (
  `recovery_detail_id` int(11) NOT NULL,
  `recovery_sheet_id` int(11) NOT NULL,
  `recovery_sheet_bill_id` int(11) NOT NULL,
  `recovery_sheet_bill_amount` decimal(10,2) NOT NULL,
  `recovery_sheet_bill_recovered` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `sector_id` int(11) NOT NULL,
  `saleman_id` int(11) DEFAULT NULL,
  `day` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `route_saleman_relation`
-- (See below for the actual view)
--
CREATE TABLE `route_saleman_relation` (
`route_id` int(11)
,`sector_id` int(11)
,`sector_name` text
,`saleman_name` text
);

-- --------------------------------------------------------

--
-- Table structure for table `salesman`
--

CREATE TABLE `salesman` (
  `saleman_id` int(11) NOT NULL,
  `saleman_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sector`
--

CREATE TABLE `sector` (
  `sector_id` int(11) NOT NULL,
  `sector_name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `route_saleman_relation`
--
DROP TABLE IF EXISTS `route_saleman_relation`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `route_saleman_relation`  AS SELECT `t`.`route_id` AS `route_id`, `t`.`sector_id` AS `sector_id`, `s`.`sector_name` AS `sector_name`, `m`.`saleman_name` AS `saleman_name` FROM ((`routes` `t` left join `sector` `s` on(`t`.`sector_id` = `s`.`sector_id`)) left join `salesman` `m` on(`t`.`saleman_id` = `m`.`saleman_id`))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `bill`
--
ALTER TABLE `bill`
  ADD PRIMARY KEY (`bill_id`);

--
-- Indexes for table `bill_ledger`
--
ALTER TABLE `bill_ledger`
  ADD PRIMARY KEY (`ledger_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `invoice_issued`
--
ALTER TABLE `invoice_issued`
  ADD PRIMARY KEY (`issued_invoice_id`);

--
-- Indexes for table `picklist`
--
ALTER TABLE `picklist`
  ADD PRIMARY KEY (`picklist_id`);

--
-- Indexes for table `recovery_sheet`
--
ALTER TABLE `recovery_sheet`
  ADD PRIMARY KEY (`recovery_id`);

--
-- Indexes for table `recovery_sheet_detail`
--
ALTER TABLE `recovery_sheet_detail`
  ADD PRIMARY KEY (`recovery_detail_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `salesman`
--
ALTER TABLE `salesman`
  ADD PRIMARY KEY (`saleman_id`);

--
-- Indexes for table `sector`
--
ALTER TABLE `sector`
  ADD PRIMARY KEY (`sector_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_ledger`
--
ALTER TABLE `bill_ledger`
  MODIFY `ledger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_issued`
--
ALTER TABLE `invoice_issued`
  MODIFY `issued_invoice_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_sheet`
--
ALTER TABLE `recovery_sheet`
  MODIFY `recovery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_sheet_detail`
--
ALTER TABLE `recovery_sheet_detail`
  MODIFY `recovery_detail_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salesman`
--
ALTER TABLE `salesman`
  MODIFY `saleman_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sector`
--
ALTER TABLE `sector`
  MODIFY `sector_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
