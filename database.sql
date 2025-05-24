-- Database: your_project_name_db

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `audit_logs`
--
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--
CREATE TABLE `commissions` (
  `id` int(11) NOT NULL,
  `earning_user_id` int(11) NOT NULL,
  `submitting_user_id` int(11) NOT NULL,
  `commission_type` varchar(100) NOT NULL,
  `commission_date` date NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `source_reference_type` varchar(50) DEFAULT NULL,
  `source_reference_id` int(11) DEFAULT NULL,
  `calculation_details` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Paid') NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `paid_in_payroll_run_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--
CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Reconciled') NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `reconciled_by_payroll_id` int(11) DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--
CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL,
  `run_by_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `total_wages_from_paysheets` decimal(15,2) DEFAULT 0.00,
  `total_commissions_paid` decimal(15,2) DEFAULT 0.00,
  `total_expenses_reimbursed` decimal(15,2) DEFAULT 0.00,
  `grand_total_payroll_amount` decimal(15,2) NOT NULL,
  `run_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_run_paysheets`
--
CREATE TABLE `payroll_run_paysheets` (
  `id` int(11) NOT NULL,
  `payroll_run_id` int(11) NOT NULL,
  `paysheet_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paysheets`
--
CREATE TABLE `paysheets` (
  `id` int(11) NOT NULL,
  `supervisor_user_id` int(11) NOT NULL,
  `pay_period_start_date` date NOT NULL,
  `pay_period_end_date` date NOT NULL,
  `status` enum('Pending Payroll','Review','Approved','Processed') NOT NULL DEFAULT 'Pending Payroll',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `total_hours_amount` decimal(12,2) DEFAULT NULL,
  `reviewed_by_payroll_id` int(11) DEFAULT NULL,
  `review_remarks` text DEFAULT NULL,
  `approved_by_payroll_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paysheet_items`
--
CREATE TABLE `paysheet_items` (
  `id` int(11) NOT NULL,
  `paysheet_id` int(11) NOT NULL,
  `timesheet_id` int(11) NOT NULL,
  `calculated_pay` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- NEW: Table structure for table `permissions` (for Normalized Permissions)
--
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_name` (`permission_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions` (Example data)
--
INSERT INTO `permissions` (`permission_name`, `description`) VALUES
('admin_dashboard_access', 'Access to the main administrator dashboard.'),
('manage_users_full', 'Full CRUD operations on all user accounts.'),
('assign_user_roles', 'Ability to assign and change user roles.'),
('manage_user_supervisors', 'Ability to assign supervisors to staff.'),
('manage_companies_full', 'Full CRUD operations on client companies.'),
('manage_sites_full', 'Full CRUD operations on work sites, including budget.'),
('assign_staff_to_sites_full', 'Ability to assign any staff to any site.'),
('staff_dashboard_access', 'Access to staff member dashboard.'),
('submit_own_timesheets', 'Staff can submit their timesheets for assigned sites.'),
('submit_unscheduled_timesheets', 'Staff can submit timesheets for unscheduled sites.'),
('view_own_timesheet_history', 'Staff can view their timesheet submission history and status.'),
('submit_own_expenses', 'Staff can submit their expense claims with receipts.'),
('view_own_expense_history', 'Staff can view their expense submission history and status.'),
('upload_own_documents', 'Staff can upload their work-related documents/certificates.'),
('view_own_documents', 'Staff can view their uploaded documents and status.'),
('supervisor_dashboard_access', 'Access to supervisor dashboard.'),
('manage_assigned_staff_profiles', 'Supervisor can view basic profiles of their assigned staff.'),
('supervisor_create_staff', 'Supervisor can create basic staff profiles (to be finalized by Admin).'),
('supervisor_assign_sites_to_staff', 'Supervisor can assign their staff to sites.'),
('supervisor_change_staff_pay_rates', 'Supervisor can change pay rates for their staff.'),
('manage_team_timesheets', 'Supervisor can view, approve, reject, or edit timesheets for their staff.'),
('approve_staff_documents', 'Supervisor can approve/reject documents uploaded by their staff.'),
('submit_commissions', 'User (e.g. Supervisor) can submit commission claims.'),
('view_own_commission_history', 'User can view their submitted commission history.'),
('supervisor_create_paysheets', 'Supervisor can generate paysheets for their team.'),
('supervisor_edit_review_paysheets', 'Supervisor can edit and resubmit paysheets in review status.'),
('payroll_dashboard_access', 'Access to payroll team dashboard.'),
('payroll_review_paysheets', 'Payroll team can review submitted paysheets and compare with budgets.'),
('payroll_approve_paysheets', 'Payroll team can approve paysheets for payroll run.'),
('payroll_mark_paysheets_for_review', 'Payroll team can mark paysheets for review and add remarks.'),
('payroll_run_process', 'Payroll team can consolidate approved paysheets and run payroll.'),
('payroll_edit_records_audited', 'Admin/Payroll can make direct edits to payroll records (with audit).'),
('view_all_payroll_reports', 'Access to comprehensive payroll reports.'),
('system_settings_manage', 'Admin can manage system-wide settings and configurations.'),
('view_system_audit_logs', 'Admin can view system audit logs.');

-- --------------------------------------------------------
--
-- Table structure for table `roles`
-- MODIFIED: Removed 'permissions' column
--
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
  -- `permissions` text DEFAULT NULL, -- This column is now removed
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
-- Note: 'permissions' column removed. Actual permissions will be in 'role_permissions'.
--
INSERT INTO `roles` (`id`, `role_name`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', '2025-05-22 13:38:51', '2025-05-22 13:38:51'),
(2, 'Staff', NOW(), NOW()),
(3, 'Supervisor', NOW(), NOW()),
(4, 'Payroll Team', NOW(), NOW());


-- --------------------------------------------------------
--
-- NEW: Table structure for table `role_permissions` (for Normalized Permissions)
--
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions` (Example: Granting all defined permissions to Administrator role_id=1)
-- This assumes the permission IDs match the order of insertion above.
-- In a real scenario, you'd select permission IDs by name.
--
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, p.id FROM `permissions` p; -- Gives Admin (role_id 1) all permissions

-- Example permissions for Staff (role_id=2)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, (SELECT id from `permissions` WHERE permission_name = 'staff_dashboard_access')),
(2, (SELECT id from `permissions` WHERE permission_name = 'submit_own_timesheets')),
(2, (SELECT id from `permissions` WHERE permission_name = 'submit_unscheduled_timesheets')),
(2, (SELECT id from `permissions` WHERE permission_name = 'view_own_timesheet_history')),
(2, (SELECT id from `permissions` WHERE permission_name = 'submit_own_expenses')),
(2, (SELECT id from `permissions` WHERE permission_name = 'view_own_expense_history')),
(2, (SELECT id from `permissions` WHERE permission_name = 'upload_own_documents')),
(2, (SELECT id from `permissions` WHERE permission_name = 'view_own_documents'));

-- Example permissions for Supervisor (role_id=3) - (includes Staff permissions + more)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, rp.permission_id FROM `role_permissions` rp WHERE rp.role_id = 2; -- Inherit Staff permissions

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_dashboard_access')),
(3, (SELECT id from `permissions` WHERE permission_name = 'manage_assigned_staff_profiles')),
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_create_staff')),
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_assign_sites_to_staff')),
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_change_staff_pay_rates')),
(3, (SELECT id from `permissions` WHERE permission_name = 'manage_team_timesheets')),
(3, (SELECT id from `permissions` WHERE permission_name = 'approve_staff_documents')),
(3, (SELECT id from `permissions` WHERE permission_name = 'submit_commissions')),
(3, (SELECT id from `permissions` WHERE permission_name = 'view_own_commission_history')),
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_create_paysheets')),
(3, (SELECT id from `permissions` WHERE permission_name = 'supervisor_edit_review_paysheets'));

-- Example permissions for Payroll Team (role_id=4)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(4, (SELECT id from `permissions` WHERE permission_name = 'payroll_dashboard_access')),
(4, (SELECT id from `permissions` WHERE permission_name = 'payroll_review_paysheets')),
(4, (SELECT id from `permissions` WHERE permission_name = 'payroll_approve_paysheets')),
(4, (SELECT id from `permissions` WHERE permission_name = 'payroll_mark_paysheets_for_review')),
(4, (SELECT id from `permissions` WHERE permission_name = 'payroll_run_process')),
(4, (SELECT id from `permissions` WHERE permission_name = 'view_all_payroll_reports'));


-- --------------------------------------------------------

--
-- Table structure for table `sites`
--
CREATE TABLE `sites` (
  `id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_address` text NOT NULL,
  `budget_per_pay_period` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_assigned_sites`
--
CREATE TABLE `staff_assigned_sites` (
  `id` int(11) NOT NULL,
  `staff_user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--
CREATE TABLE `timesheets` (
  `id` int(11) NOT NULL,
  `staff_user_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `hours_worked` decimal(5,2) NOT NULL,
  `is_unscheduled_shift` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Edited') NOT NULL DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `edited_by_supervisor_id` int(11) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `original_hours_worked` decimal(5,2) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pay_rate` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_documents`
--
CREATE TABLE `user_documents` (
  `id` int(11) NOT NULL,
  `staff_user_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approver_user_id` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL -- Added for Soft Deletes
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `earning_user_id` (`earning_user_id`),
  ADD KEY `submitting_user_id` (`submitting_user_id`),
  ADD KEY `approver_user_id` (`approver_user_id`),
  ADD KEY `fk_commission_payroll_run` (`paid_in_payroll_run_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_name` (`company_name`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approver_user_id` (`approver_user_id`),
  ADD KEY `reconciled_by_payroll_id` (`reconciled_by_payroll_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `run_by_user_id` (`run_by_user_id`);

ALTER TABLE `payroll_run_paysheets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `paysheet_id_unique_in_run` (`paysheet_id`), -- Ensured unique name for clarity
  ADD KEY `payroll_run_id` (`payroll_run_id`);

ALTER TABLE `paysheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supervisor_user_id` (`supervisor_user_id`),
  ADD KEY `reviewed_by_payroll_id` (`reviewed_by_payroll_id`),
  ADD KEY `approved_by_payroll_id` (`approved_by_payroll_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `paysheet_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `timesheet_id_unique_in_item` (`timesheet_id`), -- Ensured unique name for clarity
  ADD KEY `paysheet_id` (`paysheet_id`);

ALTER TABLE `permissions` -- Index for new table already in CREATE TABLE
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

ALTER TABLE `role_permissions` -- Indexes for new table already in CREATE TABLE
  ADD PRIMARY KEY (`role_id`, `permission_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `permission_id` (`permission_id`);

ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `staff_assigned_sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_site` (`staff_user_id`,`site_id`),
  ADD KEY `site_id` (`site_id`);

ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_user_id` (`staff_user_id`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `approver_user_id` (`approver_user_id`),
  ADD KEY `edited_by_supervisor_id` (`edited_by_supervisor_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `supervisor_id` (`supervisor_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

ALTER TABLE `user_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_user_id` (`staff_user_id`),
  ADD KEY `approver_user_id` (`approver_user_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`); -- Index for soft delete

--
-- AUTO_INCREMENT for dumped tables
--
ALTER TABLE `audit_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `commissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `companies` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `expenses` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `notifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payroll_runs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `payroll_run_paysheets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `paysheets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `paysheet_items` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `permissions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1; -- Start permissions ID from 1
ALTER TABLE `roles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5; -- Updated due to new roles
ALTER TABLE `sites` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `staff_assigned_sites` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `timesheets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `user_documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `commissions`
  ADD CONSTRAINT `commissions_ibfk_1` FOREIGN KEY (`earning_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commissions_ibfk_2` FOREIGN KEY (`submitting_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commissions_ibfk_3` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_commission_payroll_run` FOREIGN KEY (`paid_in_payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE SET NULL;

ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`reconciled_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL; -- This FK user might be payroll team member, not necessarily a User who can be deleted

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `payroll_runs`
  ADD CONSTRAINT `payroll_runs_ibfk_1` FOREIGN KEY (`run_by_user_id`) REFERENCES `users` (`id`);

ALTER TABLE `payroll_run_paysheets`
  ADD CONSTRAINT `payroll_run_paysheets_ibfk_1` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_run_paysheets_ibfk_2` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`);

ALTER TABLE `paysheets`
  ADD CONSTRAINT `paysheets_ibfk_1` FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `paysheets_ibfk_2` FOREIGN KEY (`reviewed_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paysheets_ibfk_3` FOREIGN KEY (`approved_by_payroll_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `paysheet_items`
  ADD CONSTRAINT `paysheet_items_ibfk_1` FOREIGN KEY (`paysheet_id`) REFERENCES `paysheets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paysheet_items_ibfk_2` FOREIGN KEY (`timesheet_id`) REFERENCES `timesheets` (`id`);

-- NEW Constraints for role_permissions
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

ALTER TABLE `sites`
  ADD CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

ALTER TABLE `staff_assigned_sites`
  ADD CONSTRAINT `staff_assigned_sites_ibfk_1` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_assigned_sites_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

ALTER TABLE `timesheets`
  ADD CONSTRAINT `timesheets_ibfk_1` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timesheets_ibfk_2` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`),
  ADD CONSTRAINT `timesheets_ibfk_3` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `timesheets_ibfk_4` FOREIGN KEY (`edited_by_supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_documents`
  ADD CONSTRAINT `user_documents_ibfk_1` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_documents_ibfk_2` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

COMMIT;
