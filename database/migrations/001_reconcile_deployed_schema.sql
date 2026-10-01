-- DonTech PeopleSuite schema reconciliation.
-- Review and run through the deployment process; this file is intentionally not auto-executed.

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(100) NOT NULL PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS pay_cycle ENUM('Monthly','Weekly','Bi-weekly','Daily','Any Day') NOT NULL DEFAULT 'Monthly' AFTER employment_type,
  ADD COLUMN IF NOT EXISTS bank_name VARCHAR(100) NULL AFTER allowances,
  ADD COLUMN IF NOT EXISTS bank_account_no VARCHAR(50) NULL AFTER bank_name,
  ADD COLUMN IF NOT EXISTS bank_branch VARCHAR(100) NULL AFTER bank_account_no,
  ADD COLUMN IF NOT EXISTS swift_code VARCHAR(30) NULL AFTER bank_branch,
  ADD COLUMN IF NOT EXISTS tin_number VARCHAR(30) NULL AFTER swift_code,
  ADD COLUMN IF NOT EXISTS nida_number VARCHAR(30) NULL AFTER tin_number,
  ADD COLUMN IF NOT EXISTS nssf_number VARCHAR(30) NULL AFTER nida_number;

ALTER TABLE payroll_items
  ADD COLUMN IF NOT EXISTS is_manually_adjusted TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_status,
  ADD COLUMN IF NOT EXISTS adjusted_by INT UNSIGNED NULL AFTER is_manually_adjusted,
  ADD COLUMN IF NOT EXISTS adjusted_at DATETIME NULL AFTER adjusted_by,
  ADD COLUMN IF NOT EXISTS adjustment_reason VARCHAR(255) NULL AFTER adjusted_at,
  ADD COLUMN IF NOT EXISTS employer_nssf DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER adjustment_reason,
  ADD COLUMN IF NOT EXISTS employer_wcf DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER employer_nssf,
  ADD COLUMN IF NOT EXISTS employer_sdl DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER employer_wcf,
  ADD COLUMN IF NOT EXISTS notes VARCHAR(255) NULL AFTER employer_sdl;

ALTER TABLE leave_requests
  MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'Pending';

CREATE TABLE IF NOT EXISTS broadcasts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  target_audience ENUM('All Employees','Department') NOT NULL DEFAULT 'All Employees',
  department_id INT UNSIGNED NULL,
  sent_by INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_broadcast_department (department_id),
  KEY idx_broadcast_sent_by (sent_by),
  CONSTRAINT fk_broadcast_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL,
  CONSTRAINT fk_broadcast_sender FOREIGN KEY (sent_by) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES ('001_reconcile_deployed_schema');
