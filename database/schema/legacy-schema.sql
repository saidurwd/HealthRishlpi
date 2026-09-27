
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_acl` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `controller` varchar(150) DEFAULT NULL,
  `actions` varchar(150) DEFAULT NULL,
  `action_title` varchar(150) DEFAULT NULL,
  `access` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_acl_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `action` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_acl_controller` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `controller` varchar(150) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_audit_trail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT 'User',
  `login_time` timestamp NULL DEFAULT NULL COMMENT 'Login Time',
  `logout_time` timestamp NULL DEFAULT NULL COMMENT 'Logout Time',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_backup` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `attachment` varchar(250) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `checksum` varchar(32) DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'gzip',
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `duration` varchar(50) DEFAULT NULL,
  `tables_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `os_backup_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `os_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_batch` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Batch',
  `manufacturing` date DEFAULT NULL COMMENT 'Manufacturing',
  `expiry` date DEFAULT NULL COMMENT 'Expiry',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_cache` (
  `id` varchar(128) NOT NULL,
  `expire` int(11) NOT NULL,
  `value` longblob DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `country` int(11) NOT NULL COMMENT 'Country',
  `state` int(11) NOT NULL COMMENT 'State',
  `title` varchar(255) NOT NULL COMMENT 'City',
  `city_2_code` varchar(6) DEFAULT NULL COMMENT 'Code 2',
  `city_3_code` varchar(9) DEFAULT NULL COMMENT 'Code 3',
  `status` enum('Active','Inactive') DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) NOT NULL COMMENT 'Country',
  `country_2_code` char(2) DEFAULT NULL COMMENT 'Code 2',
  `country_3_code` char(3) DEFAULT NULL COMMENT 'Code 3',
  `status` enum('Active','Inactive') DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`),
  KEY `idx_country_3_code` (`country_3_code`),
  KEY `idx_country_2_code` (`country_2_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Country records';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `code` varchar(4) DEFAULT NULL COMMENT 'Code',
  `title` varchar(150) NOT NULL COMMENT 'Department',
  `alias` varchar(250) DEFAULT NULL COMMENT 'Alias',
  `description` text DEFAULT NULL COMMENT 'Description',
  `path` varchar(250) DEFAULT NULL COMMENT 'Path',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_disease` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(150) NOT NULL COMMENT 'Disease',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_district` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `country` int(11) NOT NULL DEFAULT 18 COMMENT 'Country',
  `state` int(11) NOT NULL COMMENT 'State',
  `city` int(11) NOT NULL COMMENT 'City',
  `title` varchar(150) NOT NULL COMMENT 'District',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_instruction` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(150) NOT NULL COMMENT 'Instruction',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL,
  `servicetype` enum('Medicine','Service') DEFAULT 'Medicine' COMMENT 'Service Type',
  `service` int(11) DEFAULT NULL COMMENT 'Service',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `discount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Discount',
  `amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `store` int(11) DEFAULT NULL COMMENT 'Store',
  `batch` int(11) DEFAULT NULL COMMENT 'Batch',
  `note` varchar(400) DEFAULT NULL COMMENT 'Note',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`),
  KEY `store` (`store`),
  KEY `idx_parent` (`parent`),
  KEY `idx_servicetype` (`servicetype`),
  KEY `idx_service` (`service`),
  KEY `idx_batch` (`batch`),
  KEY `idx_parent_created_on` (`parent`,`created_on`),
  KEY `idx_parent_item` (`parent`,`item`),
  KEY `idx_parent_servicetype` (`parent`,`servicetype`),
  KEY `idx_parent_created_by` (`parent`,`created_by`),
  CONSTRAINT `fk_invoice_batch` FOREIGN KEY (`batch`) REFERENCES `os_batch` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_service` FOREIGN KEY (`service`) REFERENCES `os_service` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_invoice_store` FOREIGN KEY (`store`) REFERENCES `os_store` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_invoice_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `patient` int(11) NOT NULL COMMENT 'Patient',
  `prescription` int(11) DEFAULT NULL COMMENT 'Prescription',
  `invoice_date` datetime NOT NULL COMMENT 'Date',
  `invoice_number` varchar(100) NOT NULL COMMENT 'Issue#',
  `invoice_by` int(11) NOT NULL COMMENT 'Issue By',
  `total_amount` decimal(18,6) DEFAULT NULL COMMENT 'Amount',
  `patient_category_new` int(11) DEFAULT NULL COMMENT 'Category',
  `patient_category` int(11) DEFAULT NULL COMMENT 'Sub Category',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Issue Status',
  `payment_status` enum('Paid','Unpaid') DEFAULT 'Unpaid' COMMENT 'Payment Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `idx_patient` (`patient`),
  KEY `idx_invoice_date` (`invoice_date`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_patient_category_new` (`patient_category_new`),
  KEY `idx_created_on` (`created_on`),
  KEY `idx_patient_status` (`patient`,`status`),
  KEY `idx_patient_status_date` (`patient`,`status`,`invoice_date` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_manufacturer` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Manufacturer',
  `email` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Email',
  `phone` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Phone',
  `mobile` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Mobile',
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Address',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(11) DEFAULT 0,
  `title` varchar(150) NOT NULL,
  `controller` varchar(50) DEFAULT NULL,
  `url` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `ordering` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `group` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category_new` int(11) NOT NULL COMMENT 'Category',
  `category` int(11) NOT NULL COMMENT 'Sub Category',
  `pat_id` varchar(50) DEFAULT NULL COMMENT 'Patient ID',
  `ref_no` varchar(50) DEFAULT NULL COMMENT 'Ref. No',
  `name` varchar(150) NOT NULL COMMENT 'Name',
  `age` int(11) DEFAULT NULL COMMENT 'Age',
  `age_type` enum('Year','Month') DEFAULT 'Year' COMMENT 'Age Type',
  `sex` enum('Male','Female') DEFAULT 'Male' COMMENT 'Sex',
  `birth_date` date DEFAULT NULL COMMENT 'Date of Birth',
  `blood_groop` enum('O−','O+','A−','A+','B−','B+','AB−','AB+') DEFAULT NULL COMMENT 'Blood Group',
  `marital_status` enum('Married','Unmarried','Others') DEFAULT 'Unmarried' COMMENT 'Marital Status',
  `email` varchar(150) DEFAULT NULL COMMENT 'Email',
  `national_id` varchar(50) DEFAULT NULL COMMENT 'National ID',
  `spouse` varchar(150) DEFAULT NULL COMMENT 'Spouse',
  `occupation` varchar(150) DEFAULT NULL COMMENT 'Occupation',
  `religion` varchar(150) DEFAULT NULL COMMENT 'Religion',
  `address` varchar(250) DEFAULT NULL COMMENT 'Address',
  `village` varchar(150) DEFAULT NULL COMMENT 'Village',
  `post` varchar(150) DEFAULT NULL COMMENT 'Post',
  `thana` int(11) DEFAULT NULL COMMENT 'Thana',
  `district` int(11) DEFAULT NULL COMMENT 'District',
  `country` int(11) DEFAULT NULL COMMENT 'Country',
  `mobile` varchar(150) DEFAULT NULL COMMENT 'Mobile',
  `emergency_name` varchar(150) DEFAULT NULL COMMENT 'Name',
  `emergency_relation` varchar(150) DEFAULT NULL COMMENT 'Relation',
  `emergency_contact` varchar(150) DEFAULT NULL COMMENT 'Contact',
  `patient_type` int(11) DEFAULT NULL COMMENT 'Patient Type',
  `patient_grade` int(11) DEFAULT NULL COMMENT 'Patient Grade',
  `problem` varchar(400) DEFAULT NULL COMMENT 'Problem',
  `referred` varchar(250) DEFAULT NULL COMMENT 'Referred',
  `guardian_occupation` varchar(150) DEFAULT NULL COMMENT 'Guardian Occupation',
  `no_of_family_member` varchar(50) DEFAULT NULL COMMENT 'Nr. of family member',
  `earning_member` varchar(50) DEFAULT NULL COMMENT 'Earning Member',
  `earning_source` varchar(150) DEFAULT NULL COMMENT 'Earning Source',
  `admission` enum('Yes','No') DEFAULT NULL COMMENT 'Admission',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Registration Date',
  `created_by` int(11) DEFAULT NULL COMMENT 'Registration By',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `idx_category_new` (`category_new`),
  KEY `idx_created_on` (`created_on`),
  KEY `idx_name` (`name`),
  KEY `idx_pat_id` (`pat_id`),
  CONSTRAINT `os_patient_ibfk_1` FOREIGN KEY (`category`) REFERENCES `os_patient_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `title` varchar(250) NOT NULL COMMENT 'Category',
  `alias` varchar(250) DEFAULT NULL COMMENT 'Alias',
  `path` varchar(250) DEFAULT NULL COMMENT 'Path',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient_category_new` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `title` varchar(250) NOT NULL COMMENT 'Category',
  `alias` varchar(250) DEFAULT NULL COMMENT 'Alias',
  `path` varchar(250) DEFAULT NULL COMMENT 'Path',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(250) NOT NULL COMMENT 'Patient Grade',
  `remarks` varchar(400) DEFAULT NULL COMMENT 'Remarks',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient_prescription` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `patient` int(11) DEFAULT NULL COMMENT 'Patient',
  `pre_number` varchar(20) DEFAULT NULL COMMENT 'Pre. No.',
  `diagnosis` int(11) DEFAULT NULL COMMENT 'Diagnosis',
  `cc` varchar(250) DEFAULT NULL COMMENT 'C/C',
  `oe` varchar(250) DEFAULT NULL COMMENT 'O/E',
  `bp` varchar(250) DEFAULT NULL COMMENT 'B/P',
  `pulse` varchar(250) DEFAULT NULL COMMENT 'Pulse',
  `temp` varchar(250) DEFAULT NULL COMMENT 'Temp',
  `advice` varchar(250) DEFAULT NULL COMMENT 'Advice',
  `rx` text DEFAULT NULL COMMENT 'Prescription',
  `admission` enum('No','Yes') DEFAULT 'No' COMMENT 'Admission',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created Date',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `idx_patient` (`patient`),
  KEY `idx_diagnosis` (`diagnosis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_patient_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(250) NOT NULL COMMENT 'Patient Type',
  `remarks` varchar(400) DEFAULT NULL COMMENT 'Remarks',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_prescription_medicine` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL COMMENT 'Parent',
  `servicetype` enum('Medicine','Service') NOT NULL DEFAULT 'Medicine' COMMENT 'Medicine',
  `product` varchar(250) DEFAULT NULL COMMENT 'Product',
  `instruction` varchar(250) DEFAULT NULL COMMENT 'Instruction',
  `no_of_days` int(11) DEFAULT NULL COMMENT 'No of Days',
  `created_by` int(11) NOT NULL COMMENT 'Created By',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Created On',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `category` int(11) NOT NULL COMMENT 'Category',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Product',
  `product_code` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Code',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Description',
  `unit` int(11) NOT NULL COMMENT 'Unit',
  `threshold_value` decimal(18,6) DEFAULT 0.000000 COMMENT 'Threshold Value',
  `minimum_storage_limit` decimal(18,6) DEFAULT 0.000000 COMMENT 'Minimum Storage Limit',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `unit` (`unit`),
  CONSTRAINT `os_product_ibfk_1` FOREIGN KEY (`category`) REFERENCES `os_product_category` (`id`),
  CONSTRAINT `os_product_ibfk_2` FOREIGN KEY (`unit`) REFERENCES `os_unit` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_product_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `title` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Category',
  `alias` varchar(250) DEFAULT NULL COMMENT 'Alias',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Description',
  `path` varchar(150) DEFAULT NULL COMMENT 'Path',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Parent',
  `reference` int(11) DEFAULT 0 COMMENT 'Reference',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `total_amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `project` int(11) DEFAULT NULL COMMENT 'Project',
  `assignment` int(11) DEFAULT NULL COMMENT 'Assignment',
  `converted` tinyint(1) DEFAULT 0 COMMENT 'Converted',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_order_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `po_number` int(11) NOT NULL DEFAULT 0 COMMENT 'Purchase Order #',
  `pr_number` int(11) NOT NULL DEFAULT 0 COMMENT 'Purchase Receive #',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL DEFAULT 0.000000 COMMENT 'Quantity',
  `converted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Converted',
  `created_by` int(11) NOT NULL COMMENT 'Created By',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`po_number`),
  KEY `item` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_order_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_date` datetime NOT NULL COMMENT 'Date',
  `order_number` varchar(100) NOT NULL COMMENT 'PO#',
  `order_by` int(11) NOT NULL COMMENT 'PO By',
  `supplier` int(11) NOT NULL COMMENT 'Supplier',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'PO Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `supplier` (`supplier`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_receive` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Parent',
  `reference` int(11) DEFAULT 0 COMMENT 'Reference',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `total_amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `buy_rate` decimal(18,6) DEFAULT NULL COMMENT 'Buy Rate',
  `buy_amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Buy Amount',
  `store` int(11) DEFAULT NULL COMMENT 'Store',
  `batch` int(11) DEFAULT NULL COMMENT 'Batch',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`),
  KEY `idx_item_store_batch` (`item`,`store`,`batch`),
  CONSTRAINT `os_purchase_receive_ibfk_2` FOREIGN KEY (`item`) REFERENCES `os_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_receive_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `receive_number` int(11) NOT NULL COMMENT 'Receive Number',
  `doc_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Title',
  `doc_file` varchar(400) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Document',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_purchase_receive_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `receive_date` datetime NOT NULL COMMENT 'Date',
  `receive_number` varchar(100) NOT NULL COMMENT 'PO#',
  `receive_by` int(11) NOT NULL COMMENT 'PO By',
  `supplier` int(11) NOT NULL COMMENT 'Supplier',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'PO Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `supplier` (`supplier`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `idx_status_id` (`status`,`id`),
  CONSTRAINT `os_purchase_receive_parent_ibfk_2` FOREIGN KEY (`supplier`) REFERENCES `os_vendor` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `title` varchar(250) NOT NULL COMMENT 'Service',
  `alias` varchar(250) DEFAULT NULL COMMENT 'Alias',
  `path` varchar(250) DEFAULT NULL COMMENT 'Path',
  `rate` decimal(12,2) NOT NULL COMMENT 'Rate',
  `discount` enum('No','Yes') NOT NULL COMMENT 'Discount',
  `rate_status` enum('Auto','Manual') NOT NULL DEFAULT 'Auto' COMMENT 'Rate Status',
  `service_type` enum('Consultation','Service') DEFAULT NULL COMMENT 'Service Type',
  `service_grade` int(11) DEFAULT NULL COMMENT 'Grade',
  `ordering` int(11) DEFAULT NULL COMMENT 'Ordering',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_state` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `country` int(11) NOT NULL COMMENT 'Country',
  `title` varchar(192) NOT NULL COMMENT 'State',
  `state_2_code` varchar(6) DEFAULT NULL COMMENT 'Code 2',
  `state_3_code` varchar(9) DEFAULT NULL COMMENT 'Code 3',
  `status` enum('Active','Inactive') DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_issue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Parent',
  `reference` int(11) DEFAULT 0 COMMENT 'Reference',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `store` int(11) DEFAULT NULL COMMENT 'Store',
  `batch` int(11) DEFAULT NULL COMMENT 'Batch',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`),
  KEY `store` (`store`),
  CONSTRAINT `os_stock_issue_ibfk_2` FOREIGN KEY (`item`) REFERENCES `os_product` (`id`),
  CONSTRAINT `os_stock_issue_ibfk_3` FOREIGN KEY (`store`) REFERENCES `os_store` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_issue_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `issue_date` datetime NOT NULL COMMENT 'Date',
  `issue_number` varchar(100) NOT NULL COMMENT 'Issue#',
  `issue_by` int(11) NOT NULL COMMENT 'Issue By',
  `total_amount` decimal(18,6) DEFAULT NULL COMMENT 'Amount',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Issue Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_requisition` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Parent',
  `reference` int(11) DEFAULT 0 COMMENT 'Reference',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `store` int(11) DEFAULT NULL COMMENT 'Store',
  `batch` int(11) DEFAULT NULL COMMENT 'Batch',
  `converted` tinyint(1) DEFAULT 0 COMMENT 'Converted',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`),
  CONSTRAINT `os_stock_requisition_ibfk_2` FOREIGN KEY (`item`) REFERENCES `os_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_requisition_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `requisition_number` int(11) NOT NULL DEFAULT 0 COMMENT 'Requisition #',
  `issue_number` int(11) NOT NULL DEFAULT 0 COMMENT 'Issue #',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL DEFAULT 0.000000 COMMENT 'Quantity',
  `converted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Converted',
  `created_by` int(11) NOT NULL COMMENT 'Created By',
  `created_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`requisition_number`),
  KEY `item` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_requisition_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `requisition_date` datetime NOT NULL COMMENT 'Date',
  `requisition_number` varchar(100) NOT NULL COMMENT 'PO#',
  `requisition_by` int(11) NOT NULL COMMENT 'PO By',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'PO Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `store` int(11) NOT NULL COMMENT 'Store',
  `item` int(11) NOT NULL COMMENT 'Item',
  `batch` int(11) NOT NULL COMMENT 'Batch',
  `quantity` decimal(18,6) DEFAULT 0.000000 COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `amount` decimal(18,2) DEFAULT NULL COMMENT 'Amount',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_store_item_batch` (`store`,`item`,`batch`),
  KEY `store` (`store`),
  KEY `item` (`item`),
  KEY `idx_store_item_batch_qty` (`store`,`item`,`batch`,`quantity`),
  CONSTRAINT `os_stock_summary_ibfk_2` FOREIGN KEY (`store`) REFERENCES `os_store` (`id`),
  CONSTRAINT `os_stock_summary_ibfk_3` FOREIGN KEY (`item`) REFERENCES `os_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) NOT NULL DEFAULT 0 COMMENT 'Parent',
  `reference` int(11) DEFAULT 0 COMMENT 'Reference',
  `item` int(11) NOT NULL COMMENT 'Item',
  `quantity` decimal(18,6) NOT NULL COMMENT 'Quantity',
  `rate` decimal(18,6) DEFAULT NULL COMMENT 'Rate',
  `total_amount` decimal(18,6) DEFAULT 0.000000 COMMENT 'Amount',
  `store_from` int(11) NOT NULL COMMENT 'From Store',
  `store_to` int(11) NOT NULL COMMENT 'To Store',
  `batch` int(11) DEFAULT NULL COMMENT 'Batch',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `item` (`item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_stock_transfer_parent` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `transfer_date` datetime NOT NULL COMMENT 'Date',
  `transfer_number` varchar(100) NOT NULL COMMENT 'ST#',
  `transfer_by` int(11) NOT NULL COMMENT 'ST By',
  `supplier` int(11) NOT NULL COMMENT 'Supplier',
  `comments` text DEFAULT NULL COMMENT 'Comments',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Status',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  PRIMARY KEY (`id`),
  KEY `supplier` (`supplier`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `parent` int(11) DEFAULT NULL COMMENT 'Parent',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Store',
  `alias` varchar(255) DEFAULT NULL COMMENT 'Alias',
  `location` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Location',
  `incharge` int(11) DEFAULT NULL COMMENT 'Incharge',
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Details',
  `path` varchar(252) DEFAULT NULL COMMENT 'Path',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_store_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `transection_type` tinyint(4) NOT NULL COMMENT 'Type',
  `transection_id` int(11) DEFAULT NULL COMMENT 'Transection ID',
  `doc_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Title',
  `doc_file` varchar(400) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Document',
  `created_by` int(11) DEFAULT NULL COMMENT 'Created By',
  `created_on` timestamp NULL DEFAULT NULL COMMENT 'Created On',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_thana` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `country` int(11) NOT NULL DEFAULT 18 COMMENT 'Country',
  `state` int(11) NOT NULL COMMENT 'State',
  `city` int(11) NOT NULL COMMENT 'City',
  `district` int(11) NOT NULL COMMENT 'District',
  `title` varchar(100) NOT NULL COMMENT 'Thana',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_transection_status` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `status_id` tinyint(1) DEFAULT 0 COMMENT 'Status ID',
  `status_title` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Status',
  `user_view` tinyint(1) DEFAULT 1 COMMENT 'User View',
  `transection_type` tinyint(1) DEFAULT NULL COMMENT 'Transaction Type',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `formal_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Formal Name',
  `full_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Full Name',
  `decimal_place` tinyint(4) NOT NULL DEFAULT 2 COMMENT 'Decimal Place',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `full_name` varchar(150) NOT NULL COMMENT 'Name',
  `username` varchar(100) NOT NULL COMMENT 'Username',
  `email` varchar(100) NOT NULL COMMENT 'Email',
  `password` varchar(100) NOT NULL COMMENT 'Password',
  `register_date` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Register Date',
  `lastvisit` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 'Last Visit',
  `activation` varchar(100) DEFAULT NULL COMMENT 'Activation',
  `group_id` int(11) DEFAULT NULL COMMENT 'Group',
  `department` int(11) DEFAULT NULL COMMENT 'Department',
  `status` int(11) DEFAULT 1 COMMENT 'Status',
  `picture` varchar(255) DEFAULT NULL COMMENT 'Picture',
  `photo` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_name` (`full_name`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_user_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(150) NOT NULL COMMENT 'Title',
  `details` text DEFAULT NULL COMMENT 'Details',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_usergroup_parent_title_lookup` (`title`),
  KEY `idx_usergroup_title_lookup` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_user_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL COMMENT 'Status',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_vendor` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Vendor',
  `email` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Email',
  `phone` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Phone',
  `mobile` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Mobile',
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Address',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_visitor` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) DEFAULT NULL COMMENT 'User',
  `user_name` varchar(100) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL COMMENT 'Username',
  `page_title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Page Title',
  `page_link` varchar(150) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Page Link',
  `server_time` timestamp NULL DEFAULT NULL COMMENT 'Server Time',
  `browser` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Browser',
  `visitor_ip` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'IP',
  PRIMARY KEY (`id`),
  KEY `idx_server_time` (`server_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `os_yiisession` (
  `id` char(32) NOT NULL,
  `expire` int(11) DEFAULT NULL,
  `data` longblob DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

