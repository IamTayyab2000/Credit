CREATE TABLE IF NOT EXISTS `recovery_correction_auth` (
    `auth_id` TINYINT UNSIGNED NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Configure auth_id=1 with a password_hash generated for your chosen correction password.