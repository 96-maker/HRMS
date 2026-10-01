# DonTech PeopleSuite — Enterprise HR & Payroll System (PHP 8.x + MySQL)

A complete, standalone, production-ready HR Management System engineered specifically for Small & Medium Enterprises (SMEs) running on standard cPanel shared hosting environments.

---

## 🚀 ZERO-SSH CPANEL DEPLOYMENT GUIDE

### STEP 1: Upload Files via cPanel File Manager
1. Log in to your cPanel control panel.
2. Open **File Manager** and navigate to your domain's web directory (e.g., `public_html` or subfolder `public_html/HR`).
3. Upload the entire project directory structure:
   - Make sure `public/` directory contains `index.php` and `.htaccess`.
   - If deploying to `public_html`, point your main domain to `public_html/public` or move the contents of `public/` directly into `public_html` and keep `app/`, `routes/`, `database/` one folder above.

### STEP 2: Database Setup (phpMyAdmin)
1. Open **MySQL Database Wizard** in cPanel:
   - Create a new database named `hr_system` (or `username_hrsystem`).
   - Create a database user and assign **ALL PRIVILEGES**.
2. Open **phpMyAdmin**:
   - Select your new database.
   - Click on the **Import** tab.
   - Choose the `database/schema.sql` file provided in this project repository and click **Go**.

### STEP 3: Configure Database Connection
Edit `app/config/database.php` in File Manager with your database credentials:
```php
return [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'port'      => '3306',
    'database'  => 'your_cpanel_db_name',
    'username'  => 'your_cpanel_db_user',
    'password'  => 'your_cpanel_db_password',
];
```

---

## 🔑 INITIAL ACCESS

The application does not publish or assign default passwords. Create or activate users through a controlled administrative provisioning process, then require each user to use a unique password.

---

## 🏛️ CORE ARCHITECTURE HIGHLIGHTS

1. **Zero External Runtime Dependencies**: Pure PHP 8.x + MySQL 8.x / MariaDB 10.4+. No Node.js, Bun, Docker, or daemons required.
2. **Security Built-In**:
   - PDO Prepared Statements with emulation disabled (`ATTR_EMULATE_PREPARES => false`).
   - Automated CSRF token validation on every `POST`/`PUT`/`DELETE` request via `CsrfMiddleware`.
   - Strict file upload MIME checks (`finfo_file`) & `.htaccess` executable script prevention in `public/uploads/`.
   - Role-Based Access Control (RBAC) middleware verifying permissions on every route.
   - Audit logging tracking sensitive actions (Payroll locks, Leave approvals, User management).
3. **Tanzanian Business Context**:
   - Default currency formatted as `TZS 2,800,000`.
   - Default phone formatting (`+255 7XX XXX XXX`).
   - Timezone default `Africa/Dar_es_Salaam` (EAT +03:00).
   - Statutory deductions: PAYE Tax schedules, NSSF (10% pension contribution), and WCF (0.5% levy).
