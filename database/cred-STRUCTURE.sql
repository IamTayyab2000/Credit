-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 12, 2026 at 11:41 AM
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
-- Database: `cred-bi`
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
-- Table structure for table `backup_settings`
--

CREATE TABLE `backup_settings` (
  `id` int(11) NOT NULL,
  `backup_path` varchar(255) NOT NULL DEFAULT 'G:\\My Drive\\Backups',
  `auto_backup` tinyint(1) NOT NULL DEFAULT 0,
  `backup_frequency_hours` int(11) NOT NULL DEFAULT 24,
  `last_backup_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill`
--

CREATE TABLE `bill` (
  `bill_id` varchar(100) NOT NULL,
  `cutomer_id` varchar(33) NOT NULL,
  `picklist_id` varchar(33) NOT NULL,
  `bill_amount` bigint(20) NOT NULL,
  `bill_date` date NOT NULL,
  `Bill_status` enum('NILL','ISSUED','INFILE') NOT NULL DEFAULT 'INFILE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `billsinfo`
-- (See below for the actual view)
--
CREATE TABLE `billsinfo` (
`bill_id` varchar(100)
,`customer_name` text
,`saleman_name` text
,`sector_name` text
,`bill_date` date
,`bill_amount` bigint(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `bill_ledger`
--

CREATE TABLE `bill_ledger` (
  `ledger_id` int(11) NOT NULL,
  `ref_id` int(11) NOT NULL,
  `ledger_date` date NOT NULL,
  `customer_id` text NOT NULL,
  `bill_id` text NOT NULL,
  `bill_amount` bigint(20) NOT NULL,
  `recived_amount` bigint(20) NOT NULL,
  `return_amount` bigint(20) NOT NULL DEFAULT 0,
  `remaining_amount` bigint(20) NOT NULL,
  `bill_status` enum('NILL','BF','INFILE') NOT NULL DEFAULT 'INFILE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bill_return`
--

CREATE TABLE `bill_return` (
  `return_id` int(11) NOT NULL,
  `bill_id` varchar(100) NOT NULL,
  `ref_id` int(11) NOT NULL,
  `return_amount` decimal(10,2) NOT NULL,
  `return_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` varchar(50) NOT NULL,
  `customer_name` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
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
  `picklist_id` varchar(36) NOT NULL,
  `picklist_saleman` int(11) NOT NULL,
  `picklist_date` date NOT NULL,
  `picklist_amount` bigint(20) NOT NULL,
  `picklist_recovery` bigint(20) NOT NULL,
  `picklist_credit` bigint(20) NOT NULL,
  `picklist_sceheme_amount` bigint(20) NOT NULL,
  `picklist_return` bigint(20) NOT NULL,
  `picklist_date_processed` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_correction_auth`
--

CREATE TABLE `recovery_correction_auth` (
  `auth_id` tinyint(3) UNSIGNED NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `recovery_sheet_recovery` decimal(10,2) DEFAULT NULL,
  `recovery_sheet_returned` decimal(10,2) DEFAULT 0.00,
  `sheet_status` enum('0','1') NOT NULL,
  `recovery_sheet_source_id` int(11) DEFAULT NULL,
  `correction_status` enum('ACTIVE','ROLLED_BACK') NOT NULL DEFAULT 'ACTIVE',
  `creation_request_key` char(32) DEFAULT NULL,
  `creation_payload_hash` char(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_sheet_correction`
--

CREATE TABLE `recovery_sheet_correction` (
  `correction_id` int(11) NOT NULL,
  `recovery_sheet_id` int(11) NOT NULL,
  `bill_id` varchar(100) DEFAULT NULL,
  `action_type` enum('ROLLBACK_SHEET','EDIT_BILL') NOT NULL,
  `admin_user` varchar(255) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `before_state` longtext NOT NULL,
  `after_state` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_sheet_detail`
--

CREATE TABLE `recovery_sheet_detail` (
  `recovery_detail_id` int(33) NOT NULL,
  `recovery_sheet_id` int(33) NOT NULL,
  `recovery_sheet_bill_id` varchar(100) NOT NULL,
  `recovery_sheet_bill_amount` decimal(10,2) NOT NULL,
  `recovery_sheet_bill_recovered` decimal(10,2) NOT NULL,
  `recovery_sheet_bill_returned` decimal(10,2) DEFAULT 0.00
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
-- Stand-in structure for view `view_recovery_sheet_detail`
-- (See below for the actual view)
--
CREATE TABLE `view_recovery_sheet_detail` (
`ID` varchar(100)
,`Date` date
,`Days` int(7)
,`Shop` text
,`Amount` bigint(20)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_customer_bill_ledger`
-- (See below for the actual view)
--
CREATE TABLE `vw_customer_bill_ledger` (
`customer_id` mediumtext
,`bill_id` mediumtext
,`event_date` date
,`src_ledger_id` int(11)
,`ref_id` int(11)
,`debit` decimal(20,1)
,`credit` decimal(20,1)
,`return_amount` decimal(20,1)
,`source_type` varchar(8)
,`bill_status` varchar(6)
,`running_balance` decimal(2,1)
);

-- --------------------------------------------------------

--
-- Structure for view `billsinfo`
--
DROP TABLE IF EXISTS `billsinfo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `billsinfo`  AS SELECT `a`.`bill_id` AS `bill_id`, `b`.`customer_name` AS `customer_name`, `d`.`saleman_name` AS `saleman_name`, `e`.`sector_name` AS `sector_name`, `a`.`bill_date` AS `bill_date`, `a`.`bill_amount` AS `bill_amount` FROM ((((`bill` `a` join `customer` `b` on(`a`.`cutomer_id` = `b`.`customer_id`)) join `picklist` `c` on(`a`.`picklist_id` = `c`.`picklist_id`)) join `salesman` `d` on(`c`.`picklist_saleman` = `d`.`saleman_id`)) join `route_saleman_relation` `e` on(`b`.`customer_route` = `e`.`route_id`))  ;

-- --------------------------------------------------------

--
-- Structure for view `route_saleman_relation`
--
DROP TABLE IF EXISTS `route_saleman_relation`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `route_saleman_relation`  AS SELECT `t`.`route_id` AS `route_id`, `t`.`sector_id` AS `sector_id`, `s`.`sector_name` AS `sector_name`, `m`.`saleman_name` AS `saleman_name` FROM ((`routes` `t` left join `sector` `s` on(`t`.`sector_id` = `s`.`sector_id`)) left join `salesman` `m` on(`t`.`saleman_id` = `m`.`saleman_id`))  ;

-- --------------------------------------------------------

--
-- Structure for view `view_recovery_sheet_detail`
--
DROP TABLE IF EXISTS `view_recovery_sheet_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_recovery_sheet_detail`  AS SELECT `a`.`bill_id` AS `ID`, `a`.`bill_date` AS `Date`, to_days(current_timestamp()) - to_days(`a`.`bill_date`) AS `Days`, `b`.`customer_name` AS `Shop`, `a`.`bill_amount` AS `Amount` FROM (`bill` `a` join `customer` `b` on(`a`.`cutomer_id` = `b`.`customer_id`)) WHERE `a`.`bill_id` in (select `recovery_sheet_detail`.`recovery_sheet_bill_id` from `recovery_sheet_detail`)  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_customer_bill_ledger`
--
DROP TABLE IF EXISTS `vw_customer_bill_ledger`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_customer_bill_ledger`  AS SELECT `b`.`cutomer_id` AS `customer_id`, `b`.`bill_id` AS `bill_id`, `b`.`bill_date` AS `event_date`, 0 AS `src_ledger_id`, 0 AS `ref_id`, cast(`b`.`bill_amount` + ifnull((select sum(`bill_ledger`.`recived_amount` + `bill_ledger`.`return_amount`) from `bill_ledger` where `bill_ledger`.`bill_id` = `b`.`bill_id`),0) as decimal(20,1)) AS `debit`, 0.0 AS `credit`, 0.0 AS `return_amount`, 'issuance' AS `source_type`, `b`.`Bill_status` AS `bill_status`, 0.0 AS `running_balance` FROM `bill` AS `b` union all select `bl`.`customer_id` AS `customer_id`,`bl`.`bill_id` AS `bill_id`,`bl`.`ledger_date` AS `event_date`,`bl`.`ledger_id` AS `src_ledger_id`,`bl`.`ref_id` AS `ref_id`,0.0 AS `debit`,cast(`bl`.`recived_amount` as decimal(20,1)) AS `credit`,cast(coalesce(`bl`.`return_amount`,0) as decimal(20,1)) AS `return_amount`,'ledger' AS `source_type`,`bl`.`bill_status` AS `bill_status`,0.0 AS `running_balance` from `bill_ledger` `bl`  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `backup_settings`
--
ALTER TABLE `backup_settings`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `bill_return`
--
ALTER TABLE `bill_return`
  ADD PRIMARY KEY (`return_id`);

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
-- Indexes for table `recovery_correction_auth`
--
ALTER TABLE `recovery_correction_auth`
  ADD PRIMARY KEY (`auth_id`);

--
-- Indexes for table `recovery_sheet`
--
ALTER TABLE `recovery_sheet`
  ADD PRIMARY KEY (`recovery_id`),
  ADD KEY `idx_recovery_sheet_source_id` (`recovery_sheet_source_id`),
  ADD KEY `idx_recovery_sheet_correction_status` (`correction_status`),
  ADD UNIQUE KEY `uq_recovery_sheet_creation_request_key` (`creation_request_key`);

--
-- Indexes for table `recovery_sheet_correction`
--
ALTER TABLE `recovery_sheet_correction`
  ADD PRIMARY KEY (`correction_id`),
  ADD KEY `idx_recovery_correction_sheet` (`recovery_sheet_id`),
  ADD KEY `idx_recovery_correction_bill` (`bill_id`);

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
-- AUTO_INCREMENT for table `backup_settings`
--
ALTER TABLE `backup_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_ledger`
--
ALTER TABLE `bill_ledger`
  MODIFY `ledger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bill_return`
--
ALTER TABLE `bill_return`
  MODIFY `return_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `recovery_sheet_correction`
--
ALTER TABLE `recovery_sheet_correction`
  MODIFY `correction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_sheet_detail`
--
ALTER TABLE `recovery_sheet_detail`
  MODIFY `recovery_detail_id` int(33) NOT NULL AUTO_INCREMENT;

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
