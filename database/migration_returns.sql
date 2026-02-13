-- Migration to add returns tracking to recovery sheets and create a separate returns log
ALTER TABLE `recovery_sheet` ADD `recovery_sheet_returned` DECIMAL(10,2) DEFAULT 0.00 AFTER `recovery_sheet_recovery`;
ALTER TABLE `recovery_sheet_detail` ADD `recovery_sheet_bill_returned` DECIMAL(10,2) DEFAULT 0.00 AFTER `recovery_sheet_bill_recovered`;

CREATE TABLE `bill_return` (
    `return_id` INT AUTO_INCREMENT PRIMARY KEY,
    `bill_id` VARCHAR(100) NOT NULL,
    `ref_id` INT NOT NULL, -- Recovery Sheet ID
    `return_amount` DECIMAL(10,2) NOT NULL,
    `return_date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
