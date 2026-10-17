ALTER TABLE `recovery_sheet`
    ADD COLUMN `creation_request_key` CHAR(32) DEFAULT NULL,
    ADD COLUMN `creation_payload_hash` CHAR(64) DEFAULT NULL,
    ADD UNIQUE KEY `uq_recovery_sheet_creation_request_key` (`creation_request_key`);
