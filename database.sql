-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `tenants`
--
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the tenant company',
  `subdomain` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., for tenant-specific URLs',
  `status` enum('active','suspended','trial','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `roles` (Global)
--
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system_role` tinyint(1) DEFAULT 0 COMMENT '1 for SuperAdmin, 0 for tenant-level',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `role_name`, `is_system_role`) VALUES
(1, 'SuperAdministrator', 1), (2, 'Administrator', 0), (3, 'Staff', 0), (4, 'Supervisor', 0), (5, 'PayrollTeam', 0);

--
-- Table structure for table `permissions` (Global)
--
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permission_name` (`permission_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `role_permissions` (Global)
--
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL COMMENT 'NULL for SuperAdministrators',
  `role_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pay_rate` decimal(10,2) DEFAULT NULL COMMENT 'User''s default/base pay rate',
  `is_active` tinyint(1) DEFAULT 1,
  `profile_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_user_tenant_id` (`tenant_id`),
  KEY `idx_user_role_id` (`role_id`),
  KEY `idx_user_supervisor_id` (`supervisor_id`),
  KEY `idx_user_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `companies` (Clients of a Tenant)
--
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_company_name` (`tenant_id`, `company_name`),
  KEY `idx_comp_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `sites`
--
DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_log_type` enum('hours','service_units') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hours',
  `service_unit_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `default_staff_pay_rate_on_site` decimal(10,2) DEFAULT NULL,
  `default_client_charge_rate_on_site` decimal(10,2) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geofence_radius_meters` int(11) DEFAULT NULL,
  `budget_per_pay_period` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_site_name` (`tenant_id`, `site_name`),
  KEY `idx_site_tenant_id` (`tenant_id`),
  KEY `idx_site_company_id` (`company_id`),
  KEY `idx_site_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `staff_assigned_sites`
--
DROP TABLE IF EXISTS `staff_assigned_sites`;
CREATE TABLE `staff_assigned_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `staff_user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `custom_staff_pay_rate` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added updated_at
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tenant_staff_site_assignment` (`tenant_id`,`staff_user_id`,`site_id`),
  KEY `idx_sas_tenant_id` (`tenant_id`),
  KEY `idx_sas_staff_user_id` (`staff_user_id`),
  KEY `idx_sas_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `work_logs` (Renamed from timesheets)
--
DROP TABLE IF EXISTS `work_logs`;
CREATE TABLE `work_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `staff_user_id` int NOT NULL,
  `site_id` int NOT NULL,
  `shift_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `submission_latitude` decimal(10,8) DEFAULT NULL,
  `submission_longitude` decimal(11,8) DEFAULT NULL,
  `geofence_check_status` enum('not_checked','within_radius','outside_radius','failed_to_get_location','not_applicable') COLLATE utf8mb4_unicode_ci DEFAULT 'not_applicable',
  `is_unscheduled_shift` tinyint(1) DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Pending','Approved','Rejected','EditedBySupervisor','PendingStaffConfirmation','DisputedByStaff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approver_user_id` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `staff_dispute_reason` text COLLATE utf8mb4_unicode_ci,
  `edited_by_supervisor_id` int DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `original_quantity` decimal(10,2) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added general updated_at
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wl_tenant_id` (`tenant_id`),
  KEY `idx_wl_staff_user_id` (`staff_user_id`),
  KEY `idx_wl_site_id` (`site_id`),
  KEY `idx_wl_status` (`status`),
  KEY `idx_wl_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `work_log_photos` (NEW)
--
DROP TABLE IF EXISTS `work_log_photos`;
CREATE TABLE `work_log_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `work_log_id` int NOT NULL,
  `photo_path` varchar(255) NOT NULL,
  `client_capture_timestamp` timestamp NULL DEFAULT NULL,
  `client_capture_latitude` decimal(10,8) DEFAULT NULL,
  `client_capture_longitude` decimal(11,8) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wlp_tenant_id` (`tenant_id`),
  KEY `idx_wlp_work_log_id` (`work_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `expenses`
--
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Reconciled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reconciled_by_payroll_id` int(11) DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_exp_tenant_id` (`tenant_id`),
  KEY `idx_exp_user_id` (`user_id`),
  KEY `idx_exp_approver_user_id` (`approver_user_id`),
  KEY `idx_exp_reconciled_by_payroll_id` (`reconciled_by_payroll_id`),
  KEY `idx_exp_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `commissions`
--
DROP TABLE IF EXISTS `commissions`;
CREATE TABLE `commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `earning_user_id` int(11) NOT NULL,
  `submitting_user_id` int(11) NOT NULL,
  `commission_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commission_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_reference_id` int(11) DEFAULT NULL,
  `calculation_details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_in_payroll_run_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comm_tenant_id` (`tenant_id`),
  KEY `idx_comm_earning_user_id` (`earning_user_id`),
  KEY `idx_comm_submitting_user_id` (`submitting_user_id`),
  KEY `idx_comm_approver_user_id` (`approver_user_id`),
  KEY `idx_comm_paid_in_payroll_run_id` (`paid_in_payroll_run_id`),
  KEY `idx_comm_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `user_documents`
--
DROP TABLE IF EXISTS `user_documents`;
CREATE TABLE `user_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `staff_user_id` int(11) NOT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, -- Acts as creation time for this record
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, -- Standard audit timestamp
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Standard audit timestamp
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ud_tenant_id` (`tenant_id`),
  KEY `idx_ud_staff_user_id` (`staff_user_id`),
  KEY `idx_ud_approver_user_id` (`approver_user_id`),
  KEY `idx_ud_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `paysheets`
--
DROP TABLE IF EXISTS `paysheets`;
CREATE TABLE `paysheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `supervisor_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `status` enum('Pending Payroll','Review','Approved','Processed','AddressingReview','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Payroll',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_calculated_pay_amount` decimal(12,2) DEFAULT NULL,
  `reviewed_by_payroll_id` int(11) DEFAULT NULL,
  `review_remarks` text COLLATE utf8mb4_unicode_ci,
  `approved_by_payroll_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added general updated_at
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ps_tenant_id` (`tenant_id`),
  KEY `idx_ps_supervisor_user_id` (`supervisor_user_id`),
  KEY `idx_ps_status` (`status`),
  KEY `idx_ps_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `paysheet_items`
--
DROP TABLE IF EXISTS `paysheet_items`;
CREATE TABLE `paysheet_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `paysheet_id` int(11) NOT NULL,
  `work_log_id` int(11) NOT NULL,
  `work_log_type_snapshot` enum('hours','service_units') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_snapshot` decimal(10,2) NOT NULL,
  `pay_rate_snapshot` decimal(10,2) NOT NULL,
  `calculated_pay` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_psi_tenant_work_log` (`tenant_id`, `work_log_id`), -- Work log unique per tenant on paysheets
  KEY `idx_psi_tenant_id` (`tenant_id`), -- Redundant if part of UK above, but can be kept
  KEY `idx_psi_paysheet_id` (`paysheet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `audit_logs`
--
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_tenant_id` (`tenant_id`),
  KEY `idx_al_user_id` (`user_id`),
  KEY `idx_al_target` (`target_type`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `notifications`
--
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added
  PRIMARY KEY (`id`),
  KEY `idx_notif_tenant_id` (`tenant_id`),
  KEY `idx_notif_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payroll_runs`
--
DROP TABLE IF EXISTS `payroll_runs`;
CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `run_by_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `total_wages_from_paysheets` decimal(15,2) DEFAULT 0.00,
  `total_commissions_paid` decimal(15,2) DEFAULT 0.00,
  `total_expenses_reimbursed` decimal(15,2) DEFAULT 0.00,
  `grand_total_payroll_amount` decimal(15,2) NOT NULL,
  `run_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, -- Added
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Added
  PRIMARY KEY (`id`),
  KEY `idx_prun_tenant_id` (`tenant_id`),
  KEY `idx_prun_run_by_user_id` (`run_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payroll_run_paysheets` (Link table)
--
DROP TABLE IF EXISTS `payroll_run_paysheets`;
CREATE TABLE `payroll_run_paysheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `payroll_run_id` int(11) NOT NULL,
  `paysheet_id` int(11) NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, -- Added
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prp_tenant_paysheet` (`tenant_id`, `paysheet_id`),
  KEY `idx_prp_tenant_id` (`tenant_id`),
  KEY `idx_prp_payroll_run_id` (`payroll_run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- REPORTING TABLES (NEW)
--
DROP TABLE IF EXISTS `reports`;
CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `site_id` int DEFAULT NULL,
  `created_by_user_id` int NOT NULL,
  `report_date` date NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','submitted','reviewed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rep_tenant_id` (`tenant_id`),
  KEY `idx_rep_site_id` (`site_id`),
  KEY `idx_rep_created_by_user_id` (`created_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `report_sections`;
CREATE TABLE `report_sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `report_id` int NOT NULL,
  `header_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks_text` text COLLATE utf8mb4_unicode_ci,
  `section_order` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rs_tenant_id` (`tenant_id`),
  KEY `idx_rs_report_id` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `report_photos`;
CREATE TABLE `report_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `report_id` int NOT NULL,
  `report_section_id` int DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rph_tenant_id` (`tenant_id`),
  KEY `idx_rph_report_id` (`report_id`),
  KEY `idx_rph_report_section_id` (`report_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `report_signatures`;
CREATE TABLE `report_signatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `report_id` int NOT NULL,
  `signature_data_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signed_by_user_id` int NOT NULL,
  `signed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rsig_tenant_id` (`tenant_id`),
  KEY `idx_rsig_report_id` (`report_id`),
  KEY `idx_rsig_signed_by_user_id` (`signed_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Foreign Key Constraints (Grouped at the end for clarity and execution order)
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `companies`
  ADD CONSTRAINT `fk_companies_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

ALTER TABLE `sites`
  ADD CONSTRAINT `fk_sites_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sites_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

ALTER TABLE `staff_assigned_sites`
  ADD CONSTRAINT `fk_sas_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sas_staff_user` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sas_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

ALTER TABLE `work_logs`
  ADD CONSTRAINT `fk_wl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wl_staff_user` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wl_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `fk_wl_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wl_edited_by_supervisor` FOREIGN KEY (`edited_by_supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `work_log_photos`
  ADD CONSTRAINT `fk_wlp_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wlp_work_log` FOREIGN KEY (`work_log_id`) REFERENCES `work_logs` (`id`) ON DELETE CASCADE;

ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_expenses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_expenses_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_reconciled_by_payroll` FOREIGN KEY (`reconciled_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `commissions`
  ADD CONSTRAINT `fk_commissions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commissions_earning_user` FOREIGN KEY (`earning_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commissions_submitting_user` FOREIGN KEY (`submitting_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commissions_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
  -- FK for paid_in_payroll_run_id will be added after payroll_runs table

ALTER TABLE `user_documents`
  ADD CONSTRAINT `fk_ud_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ud_staff_user` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ud_approver_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `paysheets`
  ADD CONSTRAINT `fk_ps_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ps_supervisor_user` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ps_reviewed_by_payroll` FOREIGN KEY (`reviewed_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ps_approved_by_payroll` FOREIGN KEY (`approved_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `paysheet_items`
  ADD CONSTRAINT `fk_psi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_psi_paysheet` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_psi_work_log` FOREIGN KEY (`work_log_id`) REFERENCES `work_logs` (`id`);

ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_al_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `fk_prun_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prun_run_by_user` FOREIGN KEY (`run_by_user_id`) REFERENCES `users` (`id`);

ALTER TABLE `payroll_run_paysheets`
  ADD CONSTRAINT `fk_prp_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prp_payroll_run` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prp_paysheet` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`);
  
ALTER TABLE `commissions` -- Add this FK now that payroll_runs exists
  ADD CONSTRAINT `fk_comm_paid_in_prun` FOREIGN KEY (`paid_in_payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE SET NULL;

ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

ALTER TABLE `report_sections`
  ADD CONSTRAINT `fk_rs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rs_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

ALTER TABLE `report_photos`
  ADD CONSTRAINT `fk_rph_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rph_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rph_report_section` FOREIGN KEY (`report_section_id`) REFERENCES `report_sections` (`id`) ON DELETE SET NULL;

ALTER TABLE `report_signatures`
  ADD CONSTRAINT `fk_rsig_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rsig_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rsig_signed_by` FOREIGN KEY (`signed_by_user_id`) REFERENCES `users` (`id`);

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
