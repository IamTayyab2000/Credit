-- Store customer phone numbers for SMS sheet generation.
ALTER TABLE `customer`
    ADD COLUMN `phone` varchar(30) DEFAULT NULL AFTER `customer_name`;