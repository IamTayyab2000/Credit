create table admin(
     admin_id int PRIMARY key AUTO_INCREMENT not null,
     admin_name text not null,
     admin_password text not null
);
create table salesman(
	saleman_id int primary key AUTO_INCREMENT not null,
    saleman_name text not null
);

create table routes(
     route_id int PRIMARY key AUTO_INCREMENT not null,
     route_name text not null,
     saleman_id int,
     day text
);
create table customer(
     customer_id int PRIMARY key NOT null,
     customer_name text,
     customer_route int not null
);
CREATE TABLE bill(
     bill_id text PRIMARY key not null,
     cutomer_id text not null,
     picklist_id text not null,
     bill_amount bigint not null,
     bill_date date not null
);
create TABLE bill_ledger(
     ledger_id int PRIMARY key AUTO_INCREMENT not null,
     customer_id int not null,
     bill_id int not null,
     recived_amount bigint not null,
     remaining_amount bigint not null
);
create table picklist(
   picklist_id int PRIMARY key not null,
   picklist_route int not null,
   picklist_date date not null,
   picklist_amount bigint not null,
   picklist_credit bigint not null,
   picklist_sceheme_amount bigint not null,
   picklist_recover bigint not null
);
create table sector(
	sector_id int PRIMARY key not null AUTO_INCREMENT,
    sector_name text not null
);
create table invoice_issued(
	issued_invoice_id int primary key AUTO_INCREMENT,
    issued_invoice_date date not null,
    invoice_id text not null,
    customer_id text not null,
    invoice_amount bigint not null,
    recived_amount bigint not null,
    bill_status enum('Nill','BF','Returned') not null
);
create table recovery_sheet(
		recovery_id int PRIMARY key not null AUTO_INCREMENT,
    	recovery_date date not null,
    	recovery_sheet_saleman_id int not null,
    	recovery_sheet_amount decimal(10,2),
    	recovery_sheet_recovery decimal(10,2)
);
CREATE TABLE recovery_sheet_detail (
    recovery_detail_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    recovery_sheet_id INT NOT NULL,
    recovery_sheet_bill_id INT NOT NULL,
    recovery_sheet_bill_amount DECIMAL(10, 2) NOT NULL,
    recovery_sheet_bill_recovered DECIMAL(10, 2) NOT NULL
);
ALTER TABLE `routes` CHANGE `route_name` `sector_id` INT NOT NULL;
ALTER TABLE `picklist` ADD `picklist_recovery` INT NOT NULL AFTER `picklist_amount`;
ALTER TABLE `picklist` CHANGE `picklist_id` `picklist_id` VARCHAR(11) NOT NULL;
ALTER TABLE `picklist` CHANGE `picklist_recovery` `picklist_recovery` BIGINT(20) NOT NULL;
ALTER TABLE `picklist` CHANGE `picklist_recover` `picklist_return` BIGINT(20) NOT NULL;
ALTER TABLE `picklist` DROP `picklist_route`;
ALTER TABLE `bill_ledger` CHANGE `customer_id` `customer_id` TEXT NOT NULL;
ALTER TABLE `bill_ledger` CHANGE `bill_id` `bill_id` TEXT NOT NULL;
ALTER TABLE `bill_ledger` ADD `bill_amount` BIGINT(20) NOT NULL AFTER `bill_id`;
ALTER TABLE `bill_ledger` ADD `ledger_date` DATE NOT NULL AFTER `ledger_id`;
ALTER TABLE `bill` CHANGE `Bill_status` `Bill_status` ENUM('NILL','ISSUED','INFILE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
