ALTER TABLE `recovery_sheet`
    ADD COLUMN `recovery_sheet_source_id` INT NULL DEFAULT NULL,
    ADD COLUMN `correction_status` ENUM('ACTIVE', 'ROLLED_BACK') NOT NULL DEFAULT 'ACTIVE',
    ADD INDEX `idx_recovery_sheet_source_id` (`recovery_sheet_source_id`),
    ADD INDEX `idx_recovery_sheet_correction_status` (`correction_status`);

CREATE TABLE `recovery_sheet_correction` (
    `correction_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `recovery_sheet_id` INT NOT NULL,
    `bill_id` VARCHAR(100) DEFAULT NULL,
    `action_type` ENUM('ROLLBACK_SHEET', 'EDIT_BILL') NOT NULL,
    `admin_user` VARCHAR(255) NOT NULL,
    `reason` VARCHAR(500) NOT NULL,
    `before_state` LONGTEXT NOT NULL,
    `after_state` LONGTEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_recovery_correction_sheet` (`recovery_sheet_id`),
    INDEX `idx_recovery_correction_bill` (`bill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;