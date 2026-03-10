/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token name/description',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Static API token',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `allowed_ips` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated IPs (optional)',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_tokens_token_unique` (`token`),
  KEY `api_tokens_token_index` (`token`),
  KEY `api_tokens_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `approval_matrix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_matrix` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delegate_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_matrix_module_level_unique` (`module`,`level`),
  UNIQUE KEY `approval_matrix_uid_unique` (`uid`),
  CONSTRAINT `approval_matrix_chk_1` CHECK (json_valid(`delegate_roles`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `approval_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `level` int NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `approval_transactions_uid_unique` (`uid`),
  KEY `approval_transactions_module_reference_id_index` (`module`,`reference_id`),
  KEY `approval_transactions_approved_by_foreign` (`approved_by`),
  CONSTRAINT `approval_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL COMMENT 'Calculated from clock_out - clock_in',
  `import_source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original filename',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_logs_uid_unique` (`uid`),
  KEY `attendance_logs_employee_id_date_index` (`employee_id`,`date`),
  CONSTRAINT `attendance_logs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `late_time` time DEFAULT NULL,
  `recorded_time` time NOT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_employee_id_date_unique` (`employee_id`,`date`),
  KEY `attendances_recorded_by_foreign` (`recorded_by`),
  CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendances_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `url` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(1023) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audits_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exchange_rate` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `daily_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `late_minutes` int NOT NULL DEFAULT '0',
  `late_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `early_leave_minutes` int NOT NULL DEFAULT '0',
  `early_leave_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `overtime_minutes` int NOT NULL DEFAULT '0',
  `overtime_pay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('Present','Late','Excused','Sick Leave','Annual Leave','Alpha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Alpha',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_attendances_employee_id_date_unique` (`employee_id`,`date`),
  UNIQUE KEY `daily_attendances_uid_unique` (`uid`),
  KEY `daily_attendances_created_by_foreign` (`created_by`),
  KEY `daily_attendances_updated_by_foreign` (`updated_by`),
  CONSTRAINT `daily_attendances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `daily_attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_attendances_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dcm_costings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dcm_costings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `purchase_type` enum('restock','new_item') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `freight` decimal(15,2) NOT NULL DEFAULT '0.00',
  `invoice_total` decimal(15,2) NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_order` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tracking_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resi_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `item_status` enum('pending','received','not_received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `finance_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_at` datetime DEFAULT NULL,
  `purchase_id` bigint unsigned NOT NULL,
  `revision_at` datetime DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dcm_costings_uid_unique` (`uid`),
  KEY `dcm_costings_po_number_index` (`po_number`),
  KEY `dcm_costings_date_index` (`date`),
  KEY `dcm_costings_status_index` (`status`),
  KEY `dcm_costings_purchase_id_index` (`purchase_id`),
  KEY `dcm_costings_uid_index` (`uid`),
  KEY `dcm_costings_po_number_is_current_index` (`po_number`,`is_current`),
  CONSTRAINT `dcm_costings_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `indo_purchases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_job_order_type_grading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_job_order_type_grading` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `job_order_type_grading_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_jotg_unique` (`department_id`,`job_order_type_grading_id`),
  KEY `fk_dept_jotg_grading` (`job_order_type_grading_id`),
  CONSTRAINT `fk_dept_jotg_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dept_jotg_grading` FOREIGN KEY (`job_order_type_grading_id`) REFERENCES `job_order_type_gradings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `department_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department_project` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_project_department_id_project_id_unique` (`department_id`,`project_id`),
  KEY `department_project_project_id_foreign` (`project_id`),
  CONSTRAINT `department_project_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `department_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`),
  UNIQUE KEY `departments_uid_unique` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `document_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_documents_employee_id_document_type_index` (`employee_id`,`document_type`),
  CONSTRAINT `employee_documents_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_skillset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_skillset` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `skillset_id` bigint unsigned NOT NULL,
  `proficiency_level` enum('basic','intermediate','advanced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic',
  `acquired_date` date DEFAULT NULL,
  `last_used_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_skillset_employee_id_skillset_id_unique` (`employee_id`,`skillset_id`),
  KEY `employee_skillset_skillset_id_foreign` (`skillset_id`),
  CONSTRAINT `employee_skillset_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_skillset_skillset_id_foreign` FOREIGN KEY (`skillset_id`) REFERENCES `skillsets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_work_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_work_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `employee_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weekday_hours` decimal(5,2) NOT NULL DEFAULT '8.00',
  `weekday_start` time DEFAULT NULL,
  `weekday_end` time DEFAULT NULL,
  `saturday_hours` decimal(5,2) NOT NULL DEFAULT '5.00',
  `saturday_start` time DEFAULT NULL,
  `saturday_end` time DEFAULT NULL,
  `sunday_hours` decimal(5,2) DEFAULT NULL,
  `sunday_start` time DEFAULT NULL,
  `sunday_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_work_policies_uid_unique` (`uid`),
  KEY `employee_work_policies_employee_id_foreign` (`employee_id`),
  KEY `employee_work_policies_employee_no_index` (`employee_no`),
  CONSTRAINT `employee_work_policies_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employment_type` enum('PKWT','PKWTT','Daily Worker','Probation') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ktp_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `place_of_birth` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `rekening` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `saldo_cuti` decimal(5,2) DEFAULT '0.00',
  `status` enum('active','inactive','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_employee_no_unique` (`employee_no`),
  UNIQUE KEY `employees_uid_unique` (`uid`),
  UNIQUE KEY `employees_username_unique` (`username`),
  KEY `employees_department_id_foreign` (`department_id`),
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_announcement_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_announcement_reads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `announcement_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `read_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_announcement_reads_announcement_id_user_id_unique` (`announcement_id`,`user_id`),
  KEY `feature_announcement_reads_user_id_foreign` (`user_id`),
  CONSTRAINT `feature_announcement_reads_announcement_id_foreign` FOREIGN KEY (`announcement_id`) REFERENCES `feature_announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feature_announcement_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('info','important','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `target_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `target_user_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_from` timestamp NULL DEFAULT NULL,
  `show_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `feature_announcements_chk_1` CHECK (json_valid(`target_roles`)),
  CONSTRAINT `feature_announcements_chk_2` CHECK (json_valid(`target_user_ids`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_in` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_out_id` bigint unsigned DEFAULT NULL,
  `inventory_id` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `job_order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `returned_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `returned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_in_goods_out_id_foreign` (`goods_out_id`),
  KEY `goods_in_inventory_id_foreign` (`inventory_id`),
  KEY `goods_in_project_id_foreign` (`project_id`),
  KEY `goods_in_job_order_id_index` (`job_order_id`),
  CONSTRAINT `goods_in_goods_out_id_foreign` FOREIGN KEY (`goods_out_id`) REFERENCES `goods_out` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_in_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `goods_in_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goods_in_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_movement_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_movement_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_movement_id` bigint unsigned NOT NULL,
  `material_type` enum('Project','Goods Receive','Restock','New Material') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `goods_receive_id` bigint unsigned DEFAULT NULL,
  `inventory_id` bigint unsigned DEFAULT NULL,
  `new_material_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goods_receive_detail_id` bigint unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `transferred_to_inventory` tinyint(1) NOT NULL DEFAULT '0',
  `transferred_at` timestamp NULL DEFAULT NULL,
  `transferred_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_movement_items_goods_movement_id_foreign` (`goods_movement_id`),
  KEY `goods_movement_items_inventory_id_foreign` (`inventory_id`),
  KEY `goods_movement_items_goods_receive_detail_id_foreign` (`goods_receive_detail_id`),
  KEY `goods_movement_items_transferred_by_foreign` (`transferred_by`),
  CONSTRAINT `goods_movement_items_goods_movement_id_foreign` FOREIGN KEY (`goods_movement_id`) REFERENCES `goods_movements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_movement_items_goods_receive_detail_id_foreign` FOREIGN KEY (`goods_receive_detail_id`) REFERENCES `goods_receive_details` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goods_movement_items_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_movement_items_transferred_by_foreign` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint unsigned NOT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('Handcarry','Courier') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Handcarry',
  `movement_type_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin` enum('SG','BT','CN','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Other',
  `destination` enum('SG','BT','CN','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Other',
  `sender` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receiver` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `sender_status` enum('Pending','Prepared','Sent by Handcarry','Sent by Shipping','Checked','Received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `receiver_status` enum('Pending','Prepared','Sent by Handcarry','Sent by Shipping','Checked','Received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_movements_department_id_foreign` (`department_id`),
  KEY `goods_movements_created_by_foreign` (`created_by`),
  CONSTRAINT `goods_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goods_movements_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_out` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `material_request_id` bigint unsigned DEFAULT NULL,
  `inventory_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `job_order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_out_material_request_id_foreign` (`material_request_id`),
  KEY `goods_out_inventory_id_foreign` (`inventory_id`),
  KEY `goods_out_project_id_foreign` (`project_id`),
  KEY `goods_out_job_order_id_index` (`job_order_id`),
  CONSTRAINT `goods_out_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_out_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `goods_out_material_request_id_foreign` FOREIGN KEY (`material_request_id`) REFERENCES `material_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_out_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receive_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receive_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_receive_id` bigint unsigned NOT NULL,
  `shipping_detail_id` bigint unsigned NOT NULL,
  `purchase_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `extra_cost` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Extra cost copied from shipping detail',
  `extra_cost_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reason for extra cost',
  `domestic_waybill_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchased_qty` decimal(15,2) DEFAULT NULL,
  `received_qty` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination` enum('SG','BT','CN','MY','Other') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Destination copied from shipping',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receive_details_goods_receive_id_foreign` (`goods_receive_id`),
  KEY `goods_receive_details_shipping_detail_id_foreign` (`shipping_detail_id`),
  KEY `goods_receive_details_destination_index` (`destination`),
  CONSTRAINT `goods_receive_details_goods_receive_id_foreign` FOREIGN KEY (`goods_receive_id`) REFERENCES `goods_receives` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_receive_details_shipping_detail_id_foreign` FOREIGN KEY (`shipping_detail_id`) REFERENCES `shipping_details` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goods_receives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `goods_receives` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipping_id` bigint unsigned NOT NULL,
  `international_waybill_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `freight_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `freight_price` decimal(15,2) NOT NULL,
  `arrived_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receives_shipping_id_foreign` (`shipping_id`),
  CONSTRAINT `goods_receives_shipping_id_foreign` FOREIGN KEY (`shipping_id`) REFERENCES `shippings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `indo_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indo_purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `po_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `purchase_type` enum('restock','new_item') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'restock',
  `material_id` bigint unsigned DEFAULT NULL,
  `new_item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `actual_quantity` int DEFAULT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `project_type` enum('client','internal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `project_id` bigint unsigned DEFAULT NULL,
  `internal_project_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_order_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `is_offline_order` tinyint(1) NOT NULL DEFAULT '0',
  `pic_id` bigint unsigned DEFAULT NULL,
  `resi_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `freight` decimal(15,2) DEFAULT '0.00',
  `invoice_total` decimal(15,2) NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `item_status` enum('pending_check','matched','not_matched','pending','received','not_received') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending_check',
  `checked_at` timestamp NULL DEFAULT NULL,
  `checked_by` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `finance_notes` text COLLATE utf8mb4_unicode_ci,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `revision_at` timestamp NULL DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `indo_purchases_uid_unique` (`uid`),
  KEY `purchases_material_id_foreign` (`material_id`),
  KEY `purchases_department_id_foreign` (`department_id`),
  KEY `purchases_project_id_foreign` (`project_id`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_job_order_id_index` (`job_order_id`),
  KEY `purchases_category_id_foreign` (`category_id`),
  KEY `purchases_unit_id_foreign` (`unit_id`),
  KEY `purchases_internal_project_id_foreign` (`internal_project_id`),
  KEY `indo_purchases_is_current_index` (`is_current`),
  KEY `indo_purchases_revision_at_index` (`revision_at`),
  KEY `purchases_po_number_index` (`po_number`),
  KEY `indo_purchases_pic_id_foreign` (`pic_id`),
  KEY `indo_purchases_approved_by_foreign` (`approved_by`),
  KEY `indo_purchases_received_by_foreign` (`received_by`),
  CONSTRAINT `indo_purchases_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `indo_purchases_pic_id_foreign` FOREIGN KEY (`pic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `indo_purchases_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `purchases_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_internal_project_id_foreign` FOREIGN KEY (`internal_project_id`) REFERENCES `internal_projects` (`id`),
  CONSTRAINT `purchases_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `purchases_material_id_foreign` FOREIGN KEY (`material_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchases_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `internal_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `internal_projects` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project` enum('Office','Machine','Testing','Facilities','Store') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Office',
  `job` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned NOT NULL DEFAULT '24',
  `pic` bigint unsigned NOT NULL,
  `update_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internal_projects_uid_unique` (`uid`),
  KEY `internal_projects_pic_foreign` (`pic`),
  KEY `internal_projects_update_by_foreign` (`update_by`),
  CONSTRAINT `internal_projects_pic_foreign` FOREIGN KEY (`pic`) REFERENCES `users` (`id`),
  CONSTRAINT `internal_projects_update_by_foreign` FOREIGN KEY (`update_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  `unit_id` bigint unsigned DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `unit_domestic_freight_cost` decimal(15,2) DEFAULT NULL,
  `unit_international_freight_cost` decimal(15,2) DEFAULT NULL,
  `currency_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link Project dari Lark (staging data)',
  `supplier_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Supplier Name dari Lark (staging data)',
  `lark_record_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lark record ID untuk sync tracking',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Last sync timestamp dari Lark',
  `qrcode_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qrcode` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventories_lark_record_id_unique` (`lark_record_id`),
  KEY `inventories_currency_id_foreign` (`currency_id`),
  KEY `inventories_category_id_foreign` (`category_id`),
  KEY `inventories_supplier_id_foreign` (`supplier_id`),
  KEY `inventories_location_id_foreign` (`location_id`),
  KEY `inventories_unit_id_foreign` (`unit_id`),
  CONSTRAINT `inventories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `inventories_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventories_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `inventories_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `inventories_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_order_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_order_department` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_order_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_order_department_unique` (`job_order_id`,`department_id`),
  KEY `job_order_department_job_order_id_index` (`job_order_id`),
  KEY `job_order_department_department_id_index` (`department_id`),
  CONSTRAINT `job_order_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_order_department_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_order_type_gradings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_order_type_gradings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type_grade` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `score` decimal(8,2) NOT NULL DEFAULT '0.00',
  `grading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_sub_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_details` text COLLATE utf8mb4_unicode_ci,
  `category_id` bigint unsigned DEFAULT NULL,
  `parent_items` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lark_record_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_order_type_gradings_uid_unique` (`uid`),
  UNIQUE KEY `job_order_type_gradings_lark_record_id_unique` (`lark_record_id`),
  KEY `job_order_type_gradings_category_id_foreign` (`category_id`),
  CONSTRAINT `job_order_type_gradings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_orders` (
  `id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lark_record_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique record ID dari Lark API',
  `project_id` bigint unsigned DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Raw project name dari Lark "Project List"',
  `department_id` bigint unsigned DEFAULT NULL,
  `job_type_grade_id` bigint unsigned DEFAULT NULL,
  `department_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Raw department name dari Lark "Dept-in-charge"',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL COMMENT 'Delivery deadline date from Lark (YYYY-MM-DD format)',
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_standard_minutes` int unsigned DEFAULT NULL COMMENT 'Total standard minutes for the entire job order (for progress-based calculation)',
  `standard_time_per_unit` decimal(8,2) DEFAULT NULL COMMENT 'Standard time per unit in minutes (for qty-based calculation)',
  `source_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu terakhir sync dari Lark',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_orders_lark_record_id_unique` (`lark_record_id`),
  KEY `job_orders_project_id_foreign` (`project_id`),
  KEY `job_orders_department_id_foreign` (`department_id`),
  KEY `job_orders_created_by_foreign` (`source_by`),
  KEY `idx_total_standard_minutes` (`total_standard_minutes`),
  KEY `job_orders_job_type_grade_id_foreign` (`job_type_grade_id`),
  KEY `idx_delivery_date` (`delivery_date`),
  KEY `job_orders_status_index` (`status`),
  CONSTRAINT `job_orders_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `job_orders_job_type_grade_id_foreign` FOREIGN KEY (`job_type_grade_id`) REFERENCES `job_order_type_gradings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `job_orders_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lark_bt_sg_courier_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lark_bt_sg_courier_ids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lark_record_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_movement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transport_cost` decimal(15,2) DEFAULT NULL,
  `baggage_cost` decimal(15,2) DEFAULT NULL,
  `gst_cost` decimal(15,2) DEFAULT NULL,
  `qty_total` int DEFAULT NULL,
  `cost_per_item` decimal(15,2) DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lark_bt_sg_courier_ids_courier_id_index` (`name`),
  KEY `lark_bt_sg_courier_ids_date_index` (`date`),
  KEY `lark_bt_sg_courier_ids_project_lark_index` (`project_lark`),
  KEY `lark_bt_sg_courier_ids_lark_record_id_index` (`lark_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lark_bt_sg_item_trackings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lark_bt_sg_item_trackings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lark_record_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `sgd_cost` decimal(15,2) DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `courier_id` bigint unsigned DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lark_bt_sg_item_trackings_item_name_index` (`item_name`),
  KEY `lark_bt_sg_item_trackings_status_index` (`status`),
  KEY `lark_bt_sg_item_trackings_lark_record_id_index` (`lark_record_id`),
  KEY `lark_bt_sg_item_trackings_project_id_foreign` (`project_id`),
  KEY `lark_bt_sg_item_trackings_courier_id_foreign` (`courier_id`),
  CONSTRAINT `lark_bt_sg_item_trackings_courier_id_foreign` FOREIGN KEY (`courier_id`) REFERENCES `lark_bt_sg_courier_ids` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lark_bt_sg_item_trackings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lark_sg_bt_courier_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lark_sg_bt_courier_ids` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lark_record_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_movement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transport_cost` decimal(15,2) DEFAULT NULL,
  `baggage_cost` decimal(15,2) DEFAULT NULL,
  `gst_cost` decimal(15,2) DEFAULT NULL,
  `qty_total` int DEFAULT NULL,
  `cost_per_item` decimal(15,2) DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lark_sg_bt_courier_ids_courier_id_index` (`name`),
  KEY `lark_sg_bt_courier_ids_date_index` (`date`),
  KEY `lark_sg_bt_courier_ids_project_lark_index` (`project_lark`),
  KEY `lark_sg_bt_courier_ids_lark_record_id_index` (`lark_record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lark_sg_bt_item_trackings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lark_sg_bt_item_trackings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lark_record_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `sgd_cost` decimal(15,2) DEFAULT NULL,
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `courier_id` bigint unsigned DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lark_sg_bt_item_trackings_item_name_index` (`item_name`),
  KEY `lark_sg_bt_item_trackings_status_index` (`status`),
  KEY `lark_sg_bt_item_trackings_lark_record_id_index` (`lark_record_id`),
  KEY `lark_sg_bt_item_trackings_project_id_foreign` (`project_id`),
  KEY `lark_sg_bt_item_trackings_courier_id_foreign` (`courier_id`),
  CONSTRAINT `lark_sg_bt_item_trackings_courier_id_foreign` FOREIGN KEY (`courier_id`) REFERENCES `lark_sg_bt_courier_ids` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lark_sg_bt_item_trackings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lark_staging_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lark_staging_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lark_record_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Lark record ID (source)',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Item name dari Lark (Item Requested)',
  `project_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link Project dari Lark',
  `quantity` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Quantity dari Lark',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unit dari Lark',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Cost Amount Per Unit dari Lark (RMB)',
  `currency_id` bigint unsigned DEFAULT NULL COMMENT 'Currency ID (default RMB = 6)',
  `supplier_lark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Supplier Name dari Lark',
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL gambar item dari Lark',
  `destination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Destination dari Lark (e.g. BATAM)',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status dari Lark (e.g. Sent Out)',
  `dept_imported` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DEPT (IMPORTED) dari Lark',
  `source_record_ids` text COLLATE utf8mb4_unicode_ci COMMENT 'Comma-separated Lark record IDs (untuk aggregated items)',
  `source_record_count` int NOT NULL DEFAULT '1' COMMENT 'Jumlah source records yang diaggregasi',
  `review_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Status review sebelum masuk ke inventory',
  `review_note` text COLLATE utf8mb4_unicode_ci COMMENT 'Catatan review dari admin',
  `reviewed_by` bigint unsigned DEFAULT NULL COMMENT 'User yang mereview',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp saat sync dari Lark',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lark_staging_inventories_name_index` (`name`),
  KEY `lark_staging_inventories_review_status_index` (`review_status`),
  KEY `lark_staging_inventories_last_sync_at_index` (`last_sync_at`),
  KEY `lark_staging_inventories_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `lark_staging_inventories_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` decimal(5,2) NOT NULL DEFAULT '1.00',
  `type` enum('ANNUAL','MATERNITY','WEDDING','SONWED','BIRTHCHILD','UNPAID','DEATH','DEATH_2','BAPTISM') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ANNUAL',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `approval_1` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approval_2` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_employee_id_foreign` (`employee_id`),
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `location_supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `location_supplier` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location_supplier_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_plannings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_plannings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `order_type` enum('material_req','purchase_req') COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_needed` decimal(10,2) NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `eta_date` date NOT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_plannings_project_id_foreign` (`project_id`),
  KEY `material_plannings_unit_id_foreign` (`unit_id`),
  KEY `material_plannings_requested_by_foreign` (`requested_by`),
  CONSTRAINT `material_plannings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `material_plannings_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `material_plannings_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `project_type` enum('client','internal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client' COMMENT 'client = project_id, internal = internal_project_id',
  `internal_project_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL,
  `processed_qty` decimal(10,2) NOT NULL DEFAULT '0.00',
  `requested_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','delivered','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_requests_inventory_id_foreign` (`inventory_id`),
  KEY `material_requests_project_id_foreign` (`project_id`),
  KEY `material_requests_job_order_id_foreign` (`job_order_id`),
  KEY `material_requests_internal_project_id_foreign` (`internal_project_id`),
  KEY `material_requests_project_type_index` (`project_type`),
  KEY `material_requests_project_type_project_id_index` (`project_type`,`project_id`),
  KEY `material_requests_project_type_internal_project_id_index` (`project_type`,`internal_project_id`),
  CONSTRAINT `material_requests_internal_project_id_foreign` FOREIGN KEY (`internal_project_id`) REFERENCES `internal_projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_requests_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `material_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `material_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_id` bigint unsigned NOT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `job_order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `used_quantity` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `material_usages_inventory_id_foreign` (`inventory_id`),
  KEY `material_usages_project_id_foreign` (`project_id`),
  KEY `material_usages_job_order_id_index` (`job_order_id`),
  CONSTRAINT `material_usages_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`),
  CONSTRAINT `material_usages_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `material_usages_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `overtime_pay_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_pay_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `overtime_request_id` bigint unsigned NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `ot_code` enum('Normal Day','Sunday','Public Holiday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `net_hours` decimal(5,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `total_pay` decimal(12,2) NOT NULL,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `overtime_pay_details_uid_unique` (`uid`),
  KEY `overtime_pay_details_overtime_request_id_index` (`overtime_request_id`),
  KEY `overtime_pay_details_employee_id_index` (`employee_id`),
  KEY `overtime_pay_details_calculated_at_index` (`calculated_at`),
  CONSTRAINT `overtime_pay_details_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_pay_details_overtime_request_id_foreign` FOREIGN KEY (`overtime_request_id`) REFERENCES `overtime_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_pay_details_chk_1` CHECK (json_valid(`breakdown`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `overtime_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overtime_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `job_order_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ot_code` enum('Normal Day','Sunday','Public Holiday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `total_hours` decimal(5,2) NOT NULL,
  `break_deduction` decimal(5,2) NOT NULL DEFAULT '0.00',
  `net_hours` decimal(5,2) NOT NULL,
  `hr_approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `hr_approved_by` bigint unsigned DEFAULT NULL,
  `hr_approved_at` timestamp NULL DEFAULT NULL,
  `director_approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `director_approved_by` bigint unsigned DEFAULT NULL,
  `director_approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_passed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `overtime_requests_uid_unique` (`uid`),
  KEY `overtime_requests_hr_approved_by_foreign` (`hr_approved_by`),
  KEY `overtime_requests_director_approved_by_foreign` (`director_approved_by`),
  KEY `overtime_requests_employee_id_index` (`employee_id`),
  KEY `overtime_requests_department_id_index` (`department_id`),
  KEY `overtime_requests_job_order_id_index` (`job_order_id`),
  KEY `overtime_requests_ot_code_index` (`ot_code`),
  KEY `overtime_requests_status_index` (`status`),
  CONSTRAINT `overtime_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `overtime_requests_director_approved_by_foreign` FOREIGN KEY (`director_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `overtime_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `overtime_requests_hr_approved_by_foreign` FOREIGN KEY (`hr_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `overtime_requests_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pre_shippings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pre_shippings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_request_id` bigint unsigned NOT NULL,
  `group_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domestic_waybill_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `same_supplier_selection` tinyint(1) NOT NULL DEFAULT '0',
  `percentage_if_same_supplier` decimal(5,2) DEFAULT NULL,
  `domestic_cost` decimal(15,2) DEFAULT NULL,
  `cost_allocation_method` enum('quantity','percentage','value') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quantity',
  `allocation_percentage` decimal(5,2) DEFAULT NULL,
  `allocated_cost` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pre_shippings_external_request_id_unique` (`purchase_request_id`),
  KEY `pre_shippings_group_key_index` (`group_key`),
  CONSTRAINT `pre_shippings_external_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_costings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_costings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `grand_total` decimal(20,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_costings_project_id_foreign` (`project_id`),
  CONSTRAINT `project_costings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_parts_project_id_foreign` (`project_id`),
  CONSTRAINT `project_parts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_statuses_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lark_record_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Record ID dari Lark untuk tracking',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp sync terakhir dari Lark',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_dept` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sales person from Lark',
  `qty` int DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `project_status_id` bigint unsigned DEFAULT NULL,
  `stage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_status` text COLLATE utf8mb4_unicode_ci,
  `submission_form` text COLLATE utf8mb4_unicode_ci,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `finish_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projects_uid_unique` (`uid`),
  KEY `projects_department_id_foreign` (`department_id`),
  KEY `projects_project_status_id_foreign` (`project_status_id`),
  CONSTRAINT `projects_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `projects_project_status_id_foreign` FOREIGN KEY (`project_status_id`) REFERENCES `project_statuses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('new_material','restock') COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inventory_id` bigint unsigned DEFAULT NULL,
  `required_quantity` decimal(12,2) NOT NULL,
  `qty_to_buy` decimal(12,2) DEFAULT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_level` decimal(12,2) DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `original_supplier_id` bigint unsigned DEFAULT NULL,
  `supplier_change_reason` text COLLATE utf8mb4_unicode_ci,
  `price_per_unit` decimal(15,2) DEFAULT NULL,
  `currency_id` bigint unsigned DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Decline') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `delivery_date` date DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `external_requests_inventory_id_foreign` (`inventory_id`),
  KEY `external_requests_project_id_foreign` (`project_id`),
  KEY `external_requests_requested_by_foreign` (`requested_by`),
  KEY `purchase_requests_original_supplier_id_foreign` (`original_supplier_id`),
  CONSTRAINT `external_requests_inventory_id_foreign` FOREIGN KEY (`inventory_id`) REFERENCES `inventories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `external_requests_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `external_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requests_original_supplier_id_foreign` FOREIGN KEY (`original_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipping_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipping_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipping_id` bigint unsigned NOT NULL,
  `pre_shipping_id` bigint unsigned DEFAULT NULL,
  `shortage_item_id` bigint unsigned DEFAULT NULL COMMENT 'Direct link to shortage item (bypass PR creation)',
  `percentage` decimal(5,2) DEFAULT NULL,
  `int_cost` decimal(15,2) DEFAULT NULL,
  `extra_cost` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Extra cost for oversized/overweight items (Air Freight)',
  `extra_cost_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reason for extra cost (e.g., dimension, weight)',
  `destination` enum('SG','BT','CN','MY','Other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SG' COMMENT 'Final destination for this item',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_details_shipping_id_foreign` (`shipping_id`),
  KEY `shipping_details_pre_shipping_id_foreign` (`pre_shipping_id`),
  KEY `shipping_details_destination_index` (`destination`),
  KEY `shipping_details_extra_cost_index` (`extra_cost`),
  KEY `shipping_details_shortage_item_id_index` (`shortage_item_id`),
  CONSTRAINT `shipping_details_pre_shipping_id_foreign` FOREIGN KEY (`pre_shipping_id`) REFERENCES `pre_shippings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shipping_details_shipping_id_foreign` FOREIGN KEY (`shipping_id`) REFERENCES `shippings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shipping_details_shortage_item_id_foreign` FOREIGN KEY (`shortage_item_id`) REFERENCES `shortage_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shippings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shippings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `international_waybill_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `freight_company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `freight_method` enum('Sea Freight','Air Freight') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sea Freight' COMMENT 'Method of international shipping',
  `freight_price` decimal(15,2) DEFAULT NULL,
  `eta_to_arrived` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'On Process',
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shippings_freight_method_index` (`freight_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shortage_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shortage_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `goods_receive_detail_id` bigint unsigned NOT NULL,
  `purchase_request_id` bigint unsigned DEFAULT NULL,
  `material_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchased_qty` decimal(15,2) NOT NULL,
  `received_qty` decimal(15,2) NOT NULL,
  `shortage_qty` decimal(15,2) NOT NULL,
  `status` enum('pending','reshipped','partially_reshipped','fully_reshipped','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `resend_count` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `old_domestic_wbl` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shortage_items_goods_receive_detail_id_foreign` (`goods_receive_detail_id`),
  KEY `shortage_items_purchase_request_id_foreign` (`purchase_request_id`),
  KEY `idx_shortage_status` (`status`),
  KEY `idx_shortage_material` (`material_name`),
  KEY `idx_shortage_status_date` (`status`,`created_at`),
  CONSTRAINT `shortage_items_goods_receive_detail_id_foreign` FOREIGN KEY (`goods_receive_detail_id`) REFERENCES `goods_receive_details` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shortage_items_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `skillsets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `skillsets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `proficiency_required` enum('basic','intermediate','advanced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skillsets_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `referral_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_time_days` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','blacklisted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_name_unique` (`name`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`),
  KEY `suppliers_location_id_foreign` (`location_id`),
  CONSTRAINT `suppliers_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `location_supplier` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `timings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `project_id` bigint unsigned NOT NULL,
  `job_order_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `step` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parts` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_id` bigint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `duration_minutes` int unsigned NOT NULL DEFAULT '0' COMMENT 'Duration in minutes - standardized time storage',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `measurement_type` enum('qty','pcs','unit','piece','item','set','meter','cm','kg','gram','percentage') COLLATE utf8mb4_unicode_ci DEFAULT 'pcs',
  `measurement_value` decimal(10,2) DEFAULT NULL,
  `duration_hours` decimal(8,2) DEFAULT NULL,
  `status` enum('complete','on progress','pending') COLLATE utf8mb4_unicode_ci NOT NULL,
  `approval_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Approval status for timing session',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_specific_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_status_date` (`project_id`,`status`,`tanggal`),
  KEY `idx_joborder_status` (`job_order_id`,`status`),
  KEY `idx_employee_date` (`employee_id`,`tanggal`),
  KEY `idx_date_status` (`tanggal`,`status`),
  KEY `timings_status_index` (`status`),
  KEY `idx_employee_job_order` (`employee_id`,`job_order_id`),
  KEY `idx_measurement_type` (`measurement_type`),
  KEY `timings_approved_by_foreign` (`approved_by`),
  KEY `timings_approval_status_index` (`approval_status`),
  CONSTRAINT `timings_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timings_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `timings_job_order_id_foreign` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `timings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `timings_chk_1` CHECK (json_valid(`department_specific_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `units_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin_logistic','admin_mascot','admin_costume','admin_finance','admin_animatronic','admin_procurement','admin_hr','admin','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_department_id_foreign` (`department_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_05_06_061656_create_materials_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_05_06_061716_create_projects_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_05_06_061729_create_inventory_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_05_06_061741_create_material_requests_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_05_06_062109_add_role_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_05_06_062420_create_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_05_06_074612_create_inventories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_05_06_085036_add_qrcode_to_inventories_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_05_06_085916_add_qrcode_path_to_inventories_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_05_07_034921_create_projects_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_05_07_041628_create_material_requests',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_05_07_042921_create_material_requests_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_05_07_045301_create_material_requests',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_05_07_095349_add_remark_to_material_requests_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_05_08_023751_create_currencies_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_05_08_023823_add_currency_id_to_inventories_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_05_09_042038_create_units_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_05_14_063224_create_goods_out_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_05_15_060717_add_department_to_users_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_05_15_070514_create_goods_in_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_05_15_085008_add_inventory_and_project_to_goods_in_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_05_15_085058_create_material_usages_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_05_16_040419_alter_goods_in_goods_out_id_nullable',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_05_16_114925_create_categories_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_05_16_115000_add_category_id_to_inventories_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_05_19_081152_add_remark_to_goods_in_and_goods_out_tables',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_05_19_091706_add_soft_deletes_to_all_tables',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_05_24_110751_create_notifications_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_05_28_090444_create_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_05_28_114754_create_notifications_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_06_04_095402_create_notifications_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_06_05_093344_add_supplier_to_inventories_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_06_05_164030_add_start_date_to_projects_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_06_09_153310_add_remark_to_inventories_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_06_14_124912_add_created_by_to_projects_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_06_17_144806_add_processed_qty_to_material_requests_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_07_09_114509_create_employees_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_07_09_142656_create_timings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_07_09_154713_create_project_costings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_07_09_175705_add_finish_date_to_projects_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_07_09_214844_create_project_parts_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_07_11_100751_add_timestamps_to_project_parts_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_07_11_151124_alter_parts_nullable_on_timings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_07_12_105252_add_fields_to_employees_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_07_15_100141_add_soft_deletes_to_employees_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_07_16_085504_create_departments_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_07_16_095331_remove_department_column_from_projects_table_backup',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_07_17_092044_add_department_id_to_users_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_07_18_102520_add_approved_at_to_material_requests_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_07_24_092044_add_new_fields_to_employees_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_07_24_092053_create_employee_documents_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_07_25_083128_create_suppliers_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_07_25_083135_create_locations_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_07_25_083217_migrate_supplier_and_location_data_to_new_tables',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_09_03_141437_create_external_requests_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_09_12_083555_add_procurement_columns_to_external_requests_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_09_12_100322_create_pre_shippings_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_09_12_114705_create_shippings_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_09_12_114729_create_shipping_details_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_09_13_091316_add_status_and_remarks_to_shippings_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_09_15_091252_create_goods_receives_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_09_15_091400_create_goods_receive_details_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_09_17_163412_create_project_statuses_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_09_24_101228_add_img_to_external_requests_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_09_24_114516_alter_project_id_nullable_on_external_requests_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_02_084956_rename_external_requests_to_purchase_requests',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_10_02_090044_rename_external_request_id_to_purchase_request_id_in_pre_shippings',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_10_02_143209_add_delivery_date_to_purchase_requests_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_10_03_143411_update_pre_shippings_table_for_grouping',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_10_04_105626_add_freight_costs_to_inventories_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_10_06_110847_fix_cost_allocation_method_defaults',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_10_08_163037_create_audits_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_10_07_135751_create_leave_requests_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_10_08_083029_alter_type_enum_on_leave_requests_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_10_08_102857_add_duration_to_leave_requests_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_10_14_111228_alter_project_id_nullable_on_material_usages_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_10_07_142356_create_material_plannings_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_10_17_094455_add_admin_role_to_users_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_10_17_102054_add_admin_hr_role_to_users_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_10_17_132326_add_personal_info_to_employees_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_10_18_092700_add_employment_type_to_employees_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_10_20_093625_update_leave_request_type_to_enum',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_10_20_102806_change_saldo_cuti_to_decimal_in_employees_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_10_20_161731_add_contract_end_date_to_employees_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_10_20_141703_add_remark_to_purchase_requests_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_10_28_094543_create_location_supplier_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_10_28_094551_add_fields_to_suppliers_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_10_29_082844_add_qty_to_buy_and_remark_to_purchase_requests_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_10_29_104736_create_attendances',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_10_30_160000_add_late_time_to_attendances_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_11_04_082107_create_department_project_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_11_04_083135_migrate_project_department_data',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_10_30_133042_create_goods_movements_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_11_01_103848_add_goods_receive_detail_id_to_goods_movement_items_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_11_03_101329_add_lark_tracking_fields_to_projects_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_11_03_142707_add_sender_receiver_status_to_goods_movements_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_11_04_091408_add_new_fields_to_projects_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_11_04_091857_add_description_to_departments_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_11_04_101140_add_transferred_to_inventory_to_goods_movement_items',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_11_04_101831_add_columns_to_goods_movement_items_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_11_06_091252_update_qty_nullable_in_projects_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_11_10_101727_create_skillsets_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_11_10_101729_create_employee_skillset_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_11_11_141343_create_sessions_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_11_19_093252_add_supplier_tracking_fields_to_purchase_requests_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_11_21_093710_add_destination_to_shipping_tables',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_11_21_165923_add_unique_to_international_waybill_no',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_11_22_090516_add_freight_method_and_extra_cost_to_shippings',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_11_27_100000_create_shortage_items_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_11_28_120000_add_shortage_item_id_to_shipping_details',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_11_28_145150_remove_unique_constraint_from_international_waybill_no',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_11_28_150224_make_pre_shipping_id_nullable_in_shipping_details',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_11_29_092430_add_soft_deletes_to_purchase_requests',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_12_26_092445_create_api_tokens_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_01_27_163205_add_unit_id_to_inventories_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_01_28_162636_add_sales_to_projects_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_01_30_100000_add_department_and_project_status_to_projects_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_01_30_153720_add_department_and_project_status_to_projects_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_01_30_155126_create_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_01_31_120000_change_department_to_department_id_in_projects',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_02_02_112043_remove_assigned_to_from_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_02_03_095407_add_job_order_id_to_material_requests_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_02_03_095419_add_job_order_id_to_material_requests_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_02_03_114941_add_lark_fields_to_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_02_03_131418_make_project_id_nullable_in_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_02_03_140717_make_department_id_nullable_in_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_02_03_144713_rename_created_by_to_source_by_in_job_orders_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_02_04_093856_add_type_dept_to_projects_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_02_05_103637_add_job_order_id_to_material_usages_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_02_05_111451_add_job_order_id_to_goods_out_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_02_05_112047_add_job_order_id_to_goods_in_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_02_05_114847_sync_job_order_id_to_material_usages_from_goods_out',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_02_10_094525_add_lark_fields_to_inventories_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_02_03_130542_create_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_02_03_161803_alter_job_order_id_in_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_02_04_130557_add_purchase_type_and_receiving_columns_to_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_02_04_134839_update_purchases_table_remove_unused_columns',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_02_05_085254_add_category_unit_columns_to_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_02_05_101202_create_internal_projects_table_new',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_02_05_135541_add_description_to_internal_projects_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_02_05_142956_add_project_type_to_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_02_06_081456_rename_purchases_to_indo_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_02_06_081541_remove_approved_by_from_indo_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_02_06_091047_create_dcm_costings_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_02_06_091554_add_uid_to_dcm_costings_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_02_06_091821_remove_unnecessary_columns_from_dcm_costings',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_02_06_102341_clean_add_department_id_to_internal_projects',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_02_06_102410_clean_add_department_id_to_internal_projects',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_02_06_142040_remove_pic_from_dcm_costings_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_02_06_161024_alter_material_id_nullable_in_indo_purchases',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_02_09_111246_add_revision_fields_to_dcm_costings_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_02_09_154736_fix_indo_purchases_table_columns',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_02_10_112604_add_revision_columns_to_indo_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_02_10_133517_remove_unique_constraint_from_po_number_in_purchases_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_02_10_143340_fix_purchase_user_foreign_keys',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_02_11_153419_ensure_material_requests_project_type_integrity',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_02_12_083410_alter_material_requests_make_project_id_and_internal_project_id_nullable',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_02_12_083945_drop_foreign_key_job_order_id_from_material_requests',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_02_16_090138_add_store_to_project_enum_in_internal_projects',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_02_18_105733_create_employee_work_policies_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_02_16_102344_add_job_order_id_to_timings_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_02_16_102347_make_end_time_nullable_in_timings_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_02_16_120000_add_indexes_to_timings_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_02_18_140854_add_photo_to_timings_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_02_18_144106_create_attendance_logs_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_02_18_151424_add_measurement_type_to_timings_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_02_19_112823_update_measurement_type_enum_in_timings_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_02_19_140843_remove_output_qty_from_timings_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_02_19_155322_create_overtime_requests_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_02_20_090949_add_duration_minutes_to_timings_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_02_20_113427_add_productivity_fields_to_job_orders_and_timings_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_02_20_133531_add_is_passed_to_overtime_requests_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_02_20_150921_create_overtime_pay_details_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_02_21_083354_add_uid_to_overtime_pay_details_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_02_21_111830_add_time_columns_to_employee_work_policies_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_02_24_091450_add_department_specific_data_to_timings_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_02_23_163659_create_daily_attendances_table',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_02_26_114745_add_username_and_uid_to_employees_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_02_26_135442_make_department_id_nullable_in_indo_purchases',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_02_24_153246_add_approval_fields_to_timings_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_02_26_130953_create_lark_staging_tables',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_02_26_152848_add_last_sync_at_and_lark_record_id_to_lark_staging_tables',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_02_27_084604_add_project_columns_to_lark_bt_sg_item_trackings_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_02_27_092723_add_project_columns_to_lark_sg_bt_item_trackings_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_02_27_092732_modify_bt_sg_item_trackings_project_lark_to_string',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_02_27_100642_rename_courier_id_to_name_in_lark_courier_tables',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_02_27_100652_add_courier_id_to_lark_bt_sg_item_trackings',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_02_27_112156_add_courier_id_to_lark_sg_bt_item_trackings',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_02_27_111641_create_job_order_type_gradings_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_02_27_111718_create_department_job_order_type_grading_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_02_27_111732_add_job_type_grade_id_to_job_orders_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_02_27_160240_create_jobs_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_03_02_093759_create_feature_announcements_tables',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_03_03_000001_create_approval_matrix_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_03_03_000002_create_approval_transactions_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_03_03_000003_alter_daily_attendances_add_uid',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_03_03_000004_add_delegate_roles_to_approval_matrix_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_03_03_100000_create_job_order_department_pivot_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_03_03_100001_add_countdown_days_to_job_orders_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_03_03_100002_migrate_existing_department_lark_to_pivot',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_03_04_000001_add_uid_to_projects_departments_indo_purchases',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_03_04_000002_backfill_uid_employees',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_03_04_120129_add_delivery_date_to_job_orders_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_03_04_141255_add_status_to_job_orders_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_03_09_000001_create_lark_staging_inventories_table',80);
