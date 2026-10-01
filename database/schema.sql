-- ==============================================================================
-- DonTech PeopleSuite - Enterprise HR & Payroll System Database Schema
-- Standard: MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4_unicode_ci)
-- Enterprise Context: Tanzania (TZS Currency, Africa/Dar_es_Salaam Timezone)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `broadcasts`;
DROP TABLE IF EXISTS `employee_documents`;
DROP TABLE IF EXISTS `payroll_items`;
DROP TABLE IF EXISTS `payroll_periods`;
DROP TABLE IF EXISTS `leave_requests`;
DROP TABLE IF EXISTS `leave_balances`;
DROP TABLE IF EXISTS `leave_types`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `positions`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `company_settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Roles
CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Permissions
CREATE TABLE `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `module` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Role Permissions Pivot
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Users
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `status` ENUM('Active', 'Inactive', 'Suspended') NOT NULL DEFAULT 'Active',
  `last_login_at` DATETIME NULL,
  `remember_token` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Departments
CREATE TABLE `departments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `manager_id` INT UNSIGNED NULL,
  `budget` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Positions
CREATE TABLE `positions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `level` ENUM('Junior', 'Mid', 'Senior', 'Lead', 'Executive') NOT NULL DEFAULT 'Mid',
  `headcount` INT UNSIGNED NOT NULL DEFAULT 1,
  `open_roles` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pos_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Employees
CREATE TABLE `employees` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_code` VARCHAR(20) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `middle_name` VARCHAR(50) NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `gender` ENUM('Male', 'Female') NOT NULL,
  `dob` DATE NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `address` TEXT NOT NULL,
  `emergency_contact_name` VARCHAR(100) NOT NULL,
  `emergency_contact_phone` VARCHAR(30) NOT NULL,
  `emergency_contact_relation` VARCHAR(50) NOT NULL DEFAULT 'Spouse',
  `department_id` INT UNSIGNED NOT NULL,
  `position_id` INT UNSIGNED NOT NULL,
  `employment_type` ENUM('Full-time', 'Contract', 'Probation', 'Intern', 'Part-time') NOT NULL DEFAULT 'Full-time',
  `pay_cycle` ENUM('Monthly', 'Weekly', 'Bi-weekly', 'Daily', 'Any Day') NOT NULL DEFAULT 'Monthly',
  `date_joined` DATE NOT NULL,
  `manager_id` INT UNSIGNED NULL,
  `status` ENUM('Active', 'On Leave', 'Inactive', 'Terminated') NOT NULL DEFAULT 'Active',
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bank_name` VARCHAR(100) NULL,
  `bank_account_no` VARCHAR(50) NULL,
  `bank_branch` VARCHAR(100) NULL,
  `swift_code` VARCHAR(30) NULL,
  `tin_number` VARCHAR(30) NULL,
  `nida_number` VARCHAR(30) NULL,
  `nssf_number` VARCHAR(30) NULL,
  `avatar_url` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_emp_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_emp_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK Add for Department Manager (circular resolution)
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

-- 8. Attendance
CREATE TABLE `attendance` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `clock_in` TIME NULL,
  `clock_out` TIME NULL,
  `total_hours` DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Present', 'Late', 'Absent', 'Half Day', 'On Leave') NOT NULL DEFAULT 'Present',
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_employee_date` (`employee_id`, `date`),
  CONSTRAINT `fk_att_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Leave Types
CREATE TABLE `leave_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `default_days` INT UNSIGNED NOT NULL DEFAULT 28,
  `requires_proof` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Leave Balances
CREATE TABLE `leave_balances` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type_id` INT UNSIGNED NOT NULL,
  `year` YEAR NOT NULL,
  `allocated_days` INT UNSIGNED NOT NULL DEFAULT 28,
  `used_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `remaining_days` INT UNSIGNED NOT NULL DEFAULT 28,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_emp_leave_year` (`employee_id`, `leave_type_id`, `year`),
  CONSTRAINT `fk_lb_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lb_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Leave Requests
CREATE TABLE `leave_requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `leave_type_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Pending',
  `approved_by` INT UNSIGNED NULL,
  `approved_at` DATETIME NULL,
  `rejection_reason` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_lr_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lr_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_lr_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Payroll Periods
CREATE TABLE `payroll_periods` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `month` TINYINT UNSIGNED NOT NULL,
  `year` SMALLINT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('Draft', 'Processing', 'Processed', 'Closed') NOT NULL DEFAULT 'Draft',
  `processed_by` INT UNSIGNED NULL,
  `processed_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_month_year` (`month`, `year`),
  CONSTRAINT `fk_pp_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payroll Items
CREATE TABLE `payroll_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payroll_period_id` INT UNSIGNED NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `basic_salary` DECIMAL(12,2) NOT NULL,
  `allowances` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `overtime` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gross_salary` DECIMAL(12,2) NOT NULL,
  `tax_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- PAYE
  `statutory_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- NSSF (10%)
  `other_deductions` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(12,2) NOT NULL,
  `payment_status` ENUM('Unpaid', 'Paid', 'On Hold') NOT NULL DEFAULT 'Paid',
  `is_manually_adjusted` TINYINT(1) NOT NULL DEFAULT 0,
  `adjusted_by` INT UNSIGNED NULL,
  `adjusted_at` DATETIME NULL,
  `adjustment_reason` VARCHAR(255) NULL,
  `employer_nssf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `employer_wcf` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `employer_sdl` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_period_employee` (`payroll_period_id`, `employee_id`),
  CONSTRAINT `fk_pi_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Employee Documents
CREATE TABLE `employee_documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('Contracts', 'Identification', 'Certificates', 'HR Documents', 'Other') NOT NULL DEFAULT 'Contracts',
  `title` VARCHAR(150) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL UNIQUE,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL,
  `expiry_date` DATE NULL,
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ed_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ed_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Internal Broadcasts
CREATE TABLE `broadcasts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `target_audience` ENUM('All Employees', 'Department') NOT NULL DEFAULT 'All Employees',
  `department_id` INT UNSIGNED NULL,
  `sent_by` INT UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_broadcast_department` (`department_id`),
  KEY `idx_broadcast_sent_by` (`sent_by`),
  CONSTRAINT `fk_broadcast_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_broadcast_sender` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Notifications
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `url` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Audit Logs
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `record_id` VARCHAR(50) NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `details` JSON NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Company Settings
CREATE TABLE `company_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NOT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==============================================================================
-- SEED DATA SETUP
-- Default Password for all demo accounts: Password123!
-- Hash generated via BCRYPT (cost 10): $2y$10$E.a5w3B41R5c8D8tGgR2e.QkG70kR8O8xO4P3n5M6l7K8j9I0h1G2
-- ==============================================================================

-- 1. Roles (Strictly 2 System Roles: Admin & Employee)
INSERT INTO `roles` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Admin (HR Manager)', 'super-admin', 'Full administrative access to manage workforce, attendance, leave, payroll, and settings.'),
(2, 'Employee', 'employee', 'Self-service portal access to personal attendance, leave applications, and payslips.');

-- 2. Permissions
INSERT INTO `permissions` (`id`, `module`, `slug`, `description`) VALUES
(1, 'Employees', 'employees.view', 'View employee directory and profiles'),
(2, 'Employees', 'employees.create', 'Add new employees'),
(3, 'Employees', 'employees.edit', 'Update employee details'),
(4, 'Employees', 'employees.delete', 'Soft-delete or terminate employees'),
(5, 'Attendance', 'attendance.view', 'View attendance logs'),
(6, 'Attendance', 'attendance.clock', 'Clock in/out self'),
(7, 'Attendance', 'attendance.override', 'Manual attendance override'),
(8, 'Leave', 'leave.view', 'View leave requests'),
(9, 'Leave', 'leave.apply', 'Submit leave applications'),
(10, 'Leave', 'leave.approve', 'Approve or reject leave requests'),
(11, 'Payroll', 'payroll.view', 'View payroll summaries and payslips'),
(12, 'Payroll', 'payroll.process', 'Process and lock monthly payroll'),
(13, 'Documents', 'documents.view', 'View employee documents'),
(14, 'Documents', 'documents.upload', 'Upload employee contracts and certificates'),
(15, 'Documents', 'documents.delete', 'Remove employee documents'),
(16, 'Reports', 'reports.view', 'Generate demographic and payroll reports'),
(17, 'Settings', 'settings.manage', 'Configure system settings and role permissions');

-- 3. Role Permissions Mapping
-- Admin (All permissions)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Employee (Self Service)
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(2, 1), (2, 6), (2, 8), (2, 9), (2, 11), (2, 13);

-- 4. Users (Initial Accounts)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role_id`, `status`) VALUES
(1, 'admin', 'admin@dontech.co.tz', '$2y$10$8v5p6W1zH5xS9D2tF3g4e.9kG70kR8O8xO4P3n5M6l7K8j9I0h1G2', 1, 'Active'),
(2, 'neema', 'neema.shirima@dontech.co.tz', '$2y$10$8v5p6W1zH5xS9D2tF3g4e.9kG70kR8O8xO4P3n5M6l7K8j9I0h1G2', 1, 'Active'),
(3, 'juma', 'juma.mwakalinga@dontech.co.tz', '$2y$10$8v5p6W1zH5xS9D2tF3g4e.9kG70kR8O8xO4P3n5M6l7K8j9I0h1G2', 2, 'Active'),
(4, 'baraka', 'baraka.mushi@dontech.co.tz', '$2y$10$8v5p6W1zH5xS9D2tF3g4e.9kG70kR8O8xO4P3n5M6l7K8j9I0h1G2', 2, 'Active');

-- 5. Departments
INSERT INTO `departments` (`id`, `name`, `code`, `budget`, `status`) VALUES
(1, 'Human Resources', 'HR-01', 55000000.00, 'Active'),
(2, 'Information Technology', 'IT-02', 120000000.00, 'Active'),
(3, 'Finance & Accounting', 'FIN-03', 95000000.00, 'Active'),
(4, 'Sales & Marketing', 'SLS-04', 180000000.00, 'Active'),
(5, 'Operations & Logistics', 'OPS-05', 140000000.00, 'Active');

-- 6. Positions
INSERT INTO `positions` (`id`, `department_id`, `title`, `level`, `headcount`, `open_roles`, `status`) VALUES
(1, 1, 'HR Manager', 'Senior', 2, 0, 'Active'),
(2, 1, 'HR Officer', 'Mid', 4, 1, 'Active'),
(3, 2, 'IT Support Specialist', 'Junior', 5, 1, 'Active'),
(4, 2, 'Senior Software Engineer', 'Senior', 6, 2, 'Active'),
(5, 3, 'Chief Accountant', 'Senior', 2, 0, 'Active'),
(6, 3, 'Finance Analyst', 'Mid', 4, 1, 'Active'),
(7, 4, 'Sales Executive', 'Mid', 12, 3, 'Active'),
(8, 4, 'Marketing Specialist', 'Mid', 6, 1, 'Active'),
(9, 5, 'Operations Manager', 'Lead', 3, 0, 'Active'),
(10, 5, 'Logistics Officer', 'Junior', 8, 2, 'Active');

-- 7. Employees (Seed 20 Tanzanian Profiles)
INSERT INTO `employees` (`id`, `employee_code`, `user_id`, `first_name`, `middle_name`, `last_name`, `gender`, `dob`, `phone`, `email`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `department_id`, `position_id`, `employment_type`, `date_joined`, `manager_id`, `status`, `basic_salary`, `allowances`) VALUES
(1, 'KZ-0101', 2, 'Neema', 'Joseph', 'Shirima', 'Female', '1984-11-15', '+255716778899', 'neema.shirima@dontech.co.tz', 'Oysterbay, Dar es Salaam', 'Emmanuel Shirima', '+255758556677', 1, 1, 'Full-time', '2018-08-01', NULL, 'Active', 3200000.00, 800000.00),
(2, 'KZ-0102', 3, 'Juma', 'Peter', 'Mwakalinga', 'Male', '1986-04-12', '+255712345678', 'juma.mwakalinga@dontech.co.tz', 'Masaki, Dar es Salaam', 'Rose Mwakalinga', '+255754112233', 4, 7, 'Full-time', '2019-03-01', NULL, 'Active', 2800000.00, 700000.00),
(3, 'KZ-0103', 4, 'Baraka', 'Gideon', 'Mushi', 'Male', '1992-07-08', '+255715667788', 'baraka.mushi@dontech.co.tz', 'Sinza, Dar es Salaam', 'Joyce Mushi', '+255757445566', 2, 4, 'Full-time', '2020-02-11', 2, 'Active', 2900000.00, 600000.00),
(4, 'KZ-0104', NULL, 'Grace', 'Ally', 'Kimaro', 'Female', '1989-09-23', '+255713998877', 'grace.kimaro@dontech.co.tz', 'Mikocheni, Dar es Salaam', 'Peter Kimaro', '+255755223344', 5, 9, 'Full-time', '2019-06-15', 1, 'Active', 3100000.00, 750000.00),
(5, 'KZ-0105', NULL, 'Amina', 'Rashid', 'Hassan', 'Female', '1991-01-30', '+255714556677', 'amina.hassan@dontech.co.tz', 'Upanga, Dar es Salaam', 'Salim Hassan', '+255756334455', 3, 5, 'Full-time', '2020-01-20', 1, 'Active', 3400000.00, 850000.00),
(6, 'KZ-0106', NULL, 'Fatma', 'Said', 'Juma', 'Female', '1993-03-19', '+255717889900', 'fatma.juma@dontech.co.tz', 'Kariakoo, Dar es Salaam', 'Halima Juma', '+255759667788', 4, 8, 'Full-time', '2021-09-05', 2, 'On Leave', 2100000.00, 450000.00),
(7, 'KZ-0107', NULL, 'Emmanuel', 'Lucas', 'Kessy', 'Male', '1994-06-27', '+255718990011', 'emmanuel.kessy@dontech.co.tz', 'Tegeta, Dar es Salaam', 'Doris Kessy', '+255760778899', 4, 7, 'Full-time', '2022-04-18', 2, 'Active', 1600000.00, 350000.00),
(8, 'KZ-0108', NULL, 'Zawadi', 'Hussein', 'Mbwana', 'Female', '1996-12-02', '+255719001122', 'zawadi.mbwana@dontech.co.tz', 'Kinondoni, Dar es Salaam', 'Michael Mbwana', '+255761889900', 5, 10, 'Contract', '2023-01-09', 4, 'Active', 1750000.00, 300000.00),
(9, 'KZ-0109', NULL, 'Daniel', 'Elias', 'Massawe', 'Male', '1993-08-14', '+255620112233', 'daniel.massawe@dontech.co.tz', 'Mbezi Beach, Dar es Salaam', 'Anna Massawe', '+255762990011', 2, 3, 'Full-time', '2022-11-21', 3, 'Active', 1500000.00, 250000.00),
(10, 'KZ-0110', NULL, 'Rehema', 'Omary', 'Ally', 'Female', '1995-05-06', '+255621223344', 'rehema.ally@dontech.co.tz', 'Ilala, Dar es Salaam', 'Yusuf Ally', '+255763001122', 3, 6, 'Full-time', '2023-03-13', 5, 'Active', 2300000.00, 500000.00),
(11, 'KZ-0111', NULL, 'Peter', 'Julius', 'Nyerere', 'Male', '1989-02-28', '+255622334455', 'peter.nyerere@dontech.co.tz', 'Kimara, Dar es Salaam', 'Mary Nyerere', '+255764112233', 4, 7, 'Full-time', '2020-07-27', 2, 'Active', 1800000.00, 400000.00),
(12, 'KZ-0112', NULL, 'Joyce', 'Charles', 'Mrema', 'Female', '1997-10-11', '+255623445566', 'joyce.mrema@dontech.co.tz', 'Ubungo, Dar es Salaam', 'David Mrema', '+255765223344', 4, 8, 'Part-time', '2023-06-01', 6, 'Active', 1100000.00, 200000.00),
(13, 'KZ-0113', NULL, 'Hamisi', 'Bakari', 'Omary', 'Male', '1990-12-19', '+255624556677', 'hamisi.omary@dontech.co.tz', 'Temeke, Dar es Salaam', 'Zainab Omary', '+255766334455', 5, 10, 'Full-time', '2021-05-04', 4, 'Active', 1900000.00, 400000.00),
(14, 'KZ-0114', NULL, 'Sarah', 'Godfrey', 'Lyimo', 'Female', '1998-04-25', '+255625667788', 'sarah.lyimo@dontech.co.tz', 'Mwenge, Dar es Salaam', 'Godfrey Lyimo', '+255767445566', 2, 4, 'Intern', '2024-01-15', 3, 'Active', 850000.00, 150000.00),
(15, 'KZ-0115', NULL, 'Ibrahim', 'Kassim', 'Salum', 'Male', '1987-07-17', '+255626778899', 'ibrahim.salum@dontech.co.tz', 'Magomeni, Dar es Salaam', 'Asha Salum', '+255768556677', 3, 6, 'Full-time', '2019-10-08', 5, 'Inactive', 2200000.00, 450000.00),
(16, 'KZ-0116', NULL, 'Esther', 'Frank', 'Mollel', 'Female', '1993-11-29', '+255627889900', 'esther.mollel@dontech.co.tz', 'Kijitonyama, Dar es Salaam', 'Frank Mollel', '+255769667788', 4, 7, 'Full-time', '2022-08-22', 2, 'Active', 1650000.00, 350000.00),
(17, 'KZ-0117', NULL, 'Hassan', 'Juma', 'Kipanga', 'Male', '1991-05-14', '+255628990011', 'hassan.kipanga@dontech.co.tz', 'Mbagala, Dar es Salaam', 'Farida Kipanga', '+255770112233', 5, 10, 'Full-time', '2021-03-01', 4, 'Active', 1850000.00, 380000.00),
(18, 'KZ-0118', NULL, 'Doris', 'Wilfred', 'Swai', 'Female', '1995-09-09', '+255629001122', 'doris.swai@dontech.co.tz', 'Kawe, Dar es Salaam', 'Wilfred Swai', '+255771223344', 1, 2, 'Full-time', '2023-05-10', 1, 'Active', 1950000.00, 400000.00),
(19, 'KZ-0119', NULL, 'Victor', 'Samwel', 'Mbise', 'Male', '1994-01-18', '+255630112233', 'victor.mbise@dontech.co.tz', 'Tabata, Dar es Salaam', 'Gladys Mbise', '+255772334455', 2, 3, 'Full-time', '2022-09-01', 3, 'Active', 1600000.00, 300000.00),
(20, 'KZ-0120', NULL, 'Zaituni', 'Mohamed', 'Shehe', 'Female', '1996-06-30', '+255631223344', 'zaituni.shehe@dontech.co.tz', 'Zanzibar / DSM Office', 'Mohamed Shehe', '+255773445566', 4, 8, 'Full-time', '2023-08-15', 2, 'Active', 1700000.00, 350000.00);

-- Update Department Managers
UPDATE `departments` SET `manager_id` = 1 WHERE `id` = 1;
UPDATE `departments` SET `manager_id` = 3 WHERE `id` = 2;
UPDATE `departments` SET `manager_id` = 5 WHERE `id` = 3;
UPDATE `departments` SET `manager_id` = 2 WHERE `id` = 4;
UPDATE `departments` SET `manager_id` = 4 WHERE `id` = 5;

-- 8. Leave Types
INSERT INTO `leave_types` (`id`, `name`, `default_days`, `requires_proof`) VALUES
(1, 'Annual Leave', 28, 0),
(2, 'Sick Leave', 14, 1),
(3, 'Maternity Leave', 84, 1),
(4, 'Paternity Leave', 3, 0),
(5, 'Compassionate Leave', 7, 0),
(6, 'Unpaid Leave', 30, 0);

-- 9. Leave Balances Seed for 2026
INSERT INTO `leave_balances` (`employee_id`, `leave_type_id`, `year`, `allocated_days`, `used_days`, `remaining_days`)
SELECT id, 1, 2026, 28, 5, 23 FROM `employees`;

-- 10. Leave Requests Seed
INSERT INTO `leave_requests` (`id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `approved_by`, `approved_at`) VALUES
(1, 6, 3, '2026-09-01', '2026-11-24', 84, 'Maternity leave following childbirth.', 'Approved', 1, '2026-08-25 10:00:00'),
(2, 7, 1, '2026-09-15', '2026-09-19', 5, 'Family visit upcountry in Mbeya.', 'Pending', NULL, NULL),
(3, 11, 2, '2026-09-08', '2026-09-09', 2, 'Malaria treatment and doctor recommended rest.', 'Pending', NULL, NULL),
(4, 13, 1, '2026-09-22', '2026-09-26', 5, 'Personal errands and family rest.', 'Pending', NULL, NULL),
(5, 2, 5, '2026-08-25', '2026-08-27', 3, 'Bereavement in the family.', 'Approved', 1, '2026-08-24 14:20:00');

-- 11. Historical Attendance Seed for Today (2026-09-07)
INSERT INTO `attendance` (`employee_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `status`, `notes`) VALUES
(1, '2026-09-07', '07:48:00', '16:58:00', 8.00, 'Present', 'On time'),
(2, '2026-09-07', '08:02:00', '17:04:00', 8.00, 'Present', 'On time'),
(3, '2026-09-07', '08:00:00', '17:00:00', 8.00, 'Present', 'On time'),
(4, '2026-09-07', '07:55:00', '17:10:00', 8.00, 'Present', 'On time'),
(5, '2026-09-07', '08:05:00', '17:02:00', 8.00, 'Present', 'On time'),
(6, '2026-09-07', NULL, NULL, 0.00, 'On Leave', 'Approved Maternity Leave'),
(7, '2026-09-07', '09:18:00', '17:30:00', 7.20, 'Late', 'Traffic delay on Bagamoyo Rd'),
(8, '2026-09-07', '08:00:00', '12:30:00', 4.50, 'Half Day', 'Permission granted'),
(9, '2026-09-07', '08:12:00', '17:15:00', 8.00, 'Present', 'On time'),
(10, '2026-09-07', '09:32:00', '17:40:00', 7.00, 'Late', 'Late clock-in'),
(11, '2026-09-07', NULL, NULL, 0.00, 'Absent', 'Unexcused absence'),
(12, '2026-09-07', '08:03:00', '17:01:00', 8.00, 'Present', 'On time');

-- 12. Payroll Period (August & September 2026)
INSERT INTO `payroll_periods` (`id`, `name`, `month`, `year`, `start_date`, `end_date`, `status`, `processed_by`, `processed_at`) VALUES
(1, 'August 2026 Payroll', 8, 2026, '2026-08-01', '2026-08-31', 'Processed', 1, '2026-08-28 16:00:00'),
(2, 'September 2026 Payroll', 9, 2026, '2026-09-01', '2026-09-30', 'Draft', NULL, NULL);

-- 13. Payroll Items Seed (August 2026)
INSERT INTO `payroll_items` (`payroll_period_id`, `employee_id`, `basic_salary`, `allowances`, `overtime`, `gross_salary`, `tax_deduction`, `statutory_deduction`, `other_deductions`, `net_salary`, `payment_status`)
SELECT 
  1,
  id,
  basic_salary,
  allowances,
  0.00,
  (basic_salary + allowances) AS gross,
  ROUND((basic_salary + allowances) * 0.15, 2) AS paye,
  ROUND(basic_salary * 0.10, 2) AS nssf,
  0.00,
  (basic_salary + allowances) - ROUND((basic_salary + allowances) * 0.15, 2) - ROUND(basic_salary * 0.10, 2) AS net,
  'Paid'
FROM `employees`
WHERE `status` != 'Terminated';

-- 14. Documents Seed
INSERT INTO `employee_documents` (`id`, `employee_id`, `document_type`, `title`, `original_name`, `stored_name`, `mime_type`, `file_size`, `expiry_date`, `uploaded_by`) VALUES
(1, 1, 'Contracts', 'Employment Contract - Neema Shirima', 'Contract_Neema.pdf', 'doc_emp1_contract_2018.pdf', 'application/pdf', 345000, '2027-08-01', 1),
(2, 2, 'Identification', 'National ID Card - Juma Mwakalinga', 'NIDA_Juma.pdf', 'doc_emp2_nida_2019.pdf', 'application/pdf', 1200000, '2026-10-15', 1),
(3, 3, 'Certificates', 'BSc Software Engineering Degree', 'BSc_Degree.pdf', 'doc_emp3_degree_2020.pdf', 'application/pdf', 890000, NULL, 1),
(4, 8, 'Identification', 'Work Permit - Zawadi Mbwana', 'WorkPermit.pdf', 'doc_emp8_permit_2023.pdf', 'application/pdf', 540000, '2026-09-20', 1);

-- 15. Notifications Seed
INSERT INTO `notifications` (`user_id`, `title`, `message`, `url`, `is_read`) VALUES
(1, '3 Leave Requests Pending', 'New leave requests are awaiting your review and approval.', '/leave', 0),
(1, 'Expiring Contract Alert', 'Zawadi Mbwana work permit is expiring in under 30 days.', '/documents', 0),
(2, 'September Payroll Draft', 'September 2026 payroll batch is ready for processing.', '/payroll', 0);

-- 16. Company Settings (DonTech Solutions - Tanzania Defaults)
INSERT INTO `company_settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'DonTech Solutions Ltd'),
('company_tin', '109-482-771'),
('company_vrn', '400-881-229'),
('company_address', 'Plot 45, Bagamoyo Road, Victoria, Dar es Salaam, Tanzania'),
('company_phone', '+255 22 211 4455'),
('company_email', 'info@dontech.co.tz'),
('currency_symbol', 'TZS'),
('timezone', 'Africa/Dar_es_Salaam'),
('standard_shift_start', '08:00'),
('standard_shift_end', '17:00'),
('late_threshold_minutes', '30'),
('geofence_enabled', '1'),
('geofence_plus_code', '6756+9F Dar es Salaam'),
('geofence_latitude', '-6.823512'),
('geofence_longitude', '39.269504'),
('geofence_radius', '100');
