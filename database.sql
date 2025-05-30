-- Disable foreign key checks temporarily to avoid order issues during creation
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `roles`
--
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--
INSERT INTO `roles` (`id`, `role_name`) VALUES
(1, 'Administrator'),
(2, 'Staff'),
(3, 'Supervisor'),
(4, 'Payroll Team');

--
-- Table structure for table `permissions`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions` (Example - expand as needed)
--
INSERT INTO `permissions` (`permission_name`, `description`) VALUES
('manage_users_full', 'Full CRUD operations on all user accounts.'),
('assign_user_roles', 'Ability to assign and change user roles.'),
('manage_companies_full', 'Full CRUD operations on client companies.'),
('manage_sites_full', 'Full CRUD operations on work sites.');
-- Add all other permissions from your detailed list...

--
-- Table structure for table `role_permissions`
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
  `role_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pay_rate` decimal(10,2) DEFAULT NULL, -- Staff's default/base pay rate
  `is_active` tinyint(1) DEFAULT 1,
  `profile_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_user_role_id` (`role_id`),
  KEY `idx_user_supervisor_id` (`supervisor_id`),
  KEY `idx_user_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `companies`
--
DROP TABLE IF EXISTS `companies`;
CREATE TABLE `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company_name` (`company_name`),
  KEY `idx_comp_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `sites`
--
DROP TABLE IF EXISTS `sites`;
CREATE TABLE `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_log_type` enum('hours','service_units') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hours',
  `service_unit_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., "rooms cleaned", "standard clean unit"',
  `default_staff_pay_rate_on_site` decimal(10,2) DEFAULT NULL COMMENT 'Default pay to staff per work_log_type unit',
  `default_client_charge_rate_on_site` decimal(10,2) DEFAULT NULL COMMENT 'Default charge to client per work_log_type unit',
  `budget_per_pay_period` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_company_id` (`company_id`),
  KEY `idx_site_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `staff_assigned_sites`
--
DROP TABLE IF EXISTS `staff_assigned_sites`;
CREATE TABLE `staff_assigned_sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `custom_staff_pay_rate` decimal(10,2) DEFAULT NULL COMMENT 'Overrides site default and user default for this specific assignment',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_site_assignment` (`staff_user_id`,`site_id`),
  KEY `idx_sas_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `work_logs` (Renamed from timesheets)
--
DROP TABLE IF EXISTS `work_logs`;
CREATE TABLE `work_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_user_id` int NOT NULL,
  `site_id` int NOT NULL,
  `shift_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL COMMENT 'Can be hours or service units based on site.work_log_type',
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
  `original_quantity` decimal(10,2) DEFAULT NULL COMMENT 'Original quantity before supervisor edit',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wl_staff_user_id` (`staff_user_id`),
  KEY `idx_wl_site_id` (`site_id`),
  KEY `idx_wl_approver_user_id` (`approver_user_id`),
  KEY `idx_wl_edited_by_supervisor_id` (`edited_by_supervisor_id`),
  KEY `idx_wl_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `expenses`
--
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `staff_user_id` int(11) NOT NULL,
  `document_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
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
  `supervisor_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `status` enum('Pending Payroll','Review','Approved','Processed','AddressingReview','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending Payroll',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_hours_amount` decimal(12,2) DEFAULT NULL COMMENT 'Should be total_calculated_pay_amount',
  `reviewed_by_payroll_id` int(11) DEFAULT NULL,
  `review_remarks` text COLLATE utf8mb4_unicode_ci,
  `approved_by_payroll_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  -- No generic created_at/updated_at in original schema for this table
  PRIMARY KEY (`id`),
  KEY `idx_ps_supervisor_user_id` (`supervisor_user_id`),
  KEY `idx_ps_reviewed_by_payroll_id` (`reviewed_by_payroll_id`),
  KEY `idx_ps_approved_by_payroll_id` (`approved_by_payroll_id`),
  KEY `idx_ps_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `paysheet_items`
--
DROP TABLE IF EXISTS `paysheet_items`;
CREATE TABLE `paysheet_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paysheet_id` int(11) NOT NULL,
  `work_log_id` int(11) NOT NULL COMMENT 'Renamed from timesheet_id',
  `work_log_type_snapshot` enum('hours','service_units') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Snapshot of site.work_log_type',
  `quantity_snapshot` decimal(10,2) NOT NULL COMMENT 'Snapshot of work_logs.quantity',
  `pay_rate_snapshot` decimal(10,2) NOT NULL COMMENT 'Actual pay rate used for calculation',
  `calculated_pay` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_psi_work_log_id` (`work_log_id`) COMMENT 'A work_log entry should only be on one paysheet_item',
  KEY `idx_psi_paysheet_id` (`paysheet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `audit_logs`
--
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_user_id` (`user_id`),
  KEY `idx_al_target` (`target_type`,`target_id`),
  KEY `idx_al_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `notifications`
--
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payroll_runs`
--
DROP TABLE IF EXISTS `payroll_runs`;
CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `run_by_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `total_wages_from_paysheets` decimal(15,2) DEFAULT 0.00,
  `total_commissions_paid` decimal(15,2) DEFAULT 0.00,
  `total_expenses_reimbursed` decimal(15,2) DEFAULT 0.00,
  `grand_total_payroll_amount` decimal(15,2) NOT NULL,
  `run_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `idx_prun_run_by_user_id` (`run_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `payroll_run_paysheets` (Link table)
--
DROP TABLE IF EXISTS `payroll_run_paysheets`;
CREATE TABLE `payroll_run_paysheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `paysheet_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_prp_paysheet_id` (`paysheet_id`) COMMENT 'A paysheet should only be in one payroll run',
  KEY `idx_prp_payroll_run_id` (`payroll_run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Constraints
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_permission_id_permissions` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rp_role_id_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_users_supervisor_id` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `sites`
  ADD CONSTRAINT `fk_sites_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

ALTER TABLE `staff_assigned_sites`
  ADD CONSTRAINT `fk_sas_staff_user_id` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sas_site_id` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

ALTER TABLE `work_logs`
  ADD CONSTRAINT `fk_wl_staff_user_id` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wl_site_id` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `fk_wl_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_wl_edited_by_supervisor_id` FOREIGN KEY (`edited_by_supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_expenses_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expenses_reconciled_by_payroll_id` FOREIGN KEY (`reconciled_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `commissions`
  ADD CONSTRAINT `fk_commissions_earning_user_id` FOREIGN KEY (`earning_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commissions_submitting_user_id` FOREIGN KEY (`submitting_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_commissions_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_commissions_paid_in_payroll_run_id` FOREIGN KEY (`paid_in_payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_documents`
  ADD CONSTRAINT `fk_ud_staff_user_id` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ud_approver_user_id` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `paysheets`
  ADD CONSTRAINT `fk_ps_supervisor_user_id` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_ps_reviewed_by_payroll_id` FOREIGN KEY (`reviewed_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ps_approved_by_payroll_id` FOREIGN KEY (`approved_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `paysheet_items`
  ADD CONSTRAINT `fk_psi_paysheet_id` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_psi_work_log_id` FOREIGN KEY (`work_log_id`) REFERENCES `work_logs` (`id`);

ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_al_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `fk_prun_run_by_user_id` FOREIGN KEY (`run_by_user_id`) REFERENCES `users` (`id`);

ALTER TABLE `payroll_run_paysheets`
  ADD CONSTRAINT `fk_prp_payroll_run_id_fk` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prp_paysheet_id_fk` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`);


-- Restore foreign key checks
SET FOREIGN_KEY_CHECKS=1;
COMMIT;
