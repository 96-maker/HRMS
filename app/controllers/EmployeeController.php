<?php
// app/controllers/EmployeeController.php

class EmployeeController {
    public function index(): void {
        RoleMiddleware::hasPermission('employees.view');
        LeaveController::autoCheckResumedLeaves();
        $db = Database::getInstance();

        $search = sanitize_string($_GET['search'] ?? '');
        $deptFilter = sanitize_string($_GET['dept'] ?? '');
        $statusFilter = sanitize_string($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $where = ["e.deleted_at IS NULL"];
        $params = [];

        if (AuthService::hasRole('employee')) {
            $viewer = AuthService::user();
            $where[] = "e.user_id = :viewer_user_id";
            $params['viewer_user_id'] = $viewer['id'];
        }

        if (!empty($search)) {
            $where[] = "(e.first_name LIKE :s1 OR e.last_name LIKE :s2 OR e.email LIKE :s3 OR e.employee_code LIKE :s4)";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
        }
        if (!empty($deptFilter) && $deptFilter !== 'all') {
            $where[] = "d.name = :dept";
            $params['dept'] = $deptFilter;
        }
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $where[] = "e.status = :st";
            $params['st'] = $statusFilter;
        }

        $whereClause = implode(' AND ', $where);

        // Count Total
        $countStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();
        $totalPages = ceil($totalRecords / $limit);

        // Fetch Paginated
        $stmt = $db->prepare("
            SELECT e.*, d.name as department_name, p.title as position_title, u.username, r.name as role_name 
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE {$whereClause}
            ORDER BY e.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        // Departments dropdown filter data
        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();

        view('employees.index', [
            'title'        => 'Employee Directory — DonTech PeopleSuite',
            'employees'    => $employees,
            'departments'  => $departments,
            'search'       => $search,
            'deptFilter'   => $deptFilter,
            'statusFilter' => $statusFilter,
            'page'         => $page,
            'totalPages'   => $totalPages,
            'totalRecords' => $totalRecords
        ]);
    }

    public function create(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();

        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();
        $positions   = $db->query("SELECT * FROM positions WHERE status='Active'")->fetchAll();
        $roles       = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        view('employees.create', [
            'title'       => 'Add New Employee — DonTech PeopleSuite',
            'departments' => $departments,
            'positions'   => $positions,
            'roles'       => $roles
        ]);
    }

    public function store(): void {
        RoleMiddleware::hasPermission('employees.create');
        $db = Database::getInstance();

        $firstName = sanitize_string($_POST['first_name'] ?? '');
        $lastName  = sanitize_string($_POST['last_name'] ?? '');
        $email     = sanitize_email($_POST['email'] ?? '');
        $phone     = sanitize_string($_POST['phone'] ?? '');
        $gender    = sanitize_string($_POST['gender'] ?? 'Male');
        $dob       = $_POST['dob'] ?? '1995-01-01';
        $address   = sanitize_string($_POST['address'] ?? '');
        $deptId    = (int)($_POST['department_id'] ?? 1);
        $posId     = (int)($_POST['position_id'] ?? 1);
        $type      = sanitize_string($_POST['employment_type'] ?? 'Full-time');
        $payCycle  = sanitize_string($_POST['pay_cycle'] ?? 'Monthly');
        $dateJoined= $_POST['date_joined'] ?? date('Y-m-d');
        $salary    = (float)($_POST['basic_salary'] ?? 0);
        $allowances= (float)($_POST['allowances'] ?? 0);
        $roleId    = (int)($_POST['role_id'] ?? 2); // Default to Employee role (ID 2)
        $password  = $_POST['initial_password'] ?? '';
        $emergencyName  = sanitize_string($_POST['emergency_contact_name'] ?? '');
        $emergencyPhone = sanitize_string($_POST['emergency_contact_phone'] ?? '');

        // Bank Payment Information
        $bankName    = sanitize_string($_POST['bank_name'] ?? '');
        $bankAccount = sanitize_string($_POST['bank_account_no'] ?? '');
        $bankBranch  = sanitize_string($_POST['bank_branch'] ?? '');
        $swiftCode   = sanitize_string($_POST['swift_code'] ?? '');

        // Statutory Identifiers
        $tinNumber  = sanitize_string($_POST['tin_number'] ?? '');
        $nidaNumber = sanitize_string($_POST['nida_number'] ?? '');
        $nssfNumber = sanitize_string($_POST['nssf_number'] ?? '');

        $userAccountMode = $_POST['create_user_account'] ?? 'pending';

        if ($userAccountMode === 'active' && strlen($password) < 12) {
            redirect('/employees/create', 'An initial password of at least 12 characters is required for an active user account.', 'danger');
            return;
        }

        // Manual Username processing & validation
        $rawUsername = sanitize_string($_POST['username'] ?? '');
        if (empty($rawUsername)) {
            $baseUsername = strtolower(explode('@', $email)[0] ?? ($firstName . '.' . $lastName));
            $username = preg_replace('/[^a-z0-9._-]/', '', $baseUsername);
        } else {
            $username = preg_replace('/[^a-z0-9._-]/', '', strtolower($rawUsername));
        }

        // Check duplicate email in users or employees table
        $chkEmail = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :e");
        $chkEmail->execute(['e' => $email]);
        if ($chkEmail->fetchColumn() > 0) {
            redirect('/employees/create', "An account with the email '{$email}' already exists. Please use a different email address.", 'danger');
            return;
        }

        // Ensure unique username if creating user account
        if ($userAccountMode === 'active') {
            $chkUser = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
            $chkUser->execute(['u' => $username]);
            if ($chkUser->fetchColumn() > 0) {
                redirect('/employees/create', "Username '{$username}' is already taken. Please enter a different username.", 'danger');
                return;
            }
        }

        try {
            $userId = null;

            // Create User Account if mode is Active
            if ($userAccountMode === 'active') {
                $passHash = password_hash($password, PASSWORD_BCRYPT);
                $userStmt = $db->prepare("
                    INSERT INTO users (username, email, password_hash, role_id, status)
                    VALUES (:u, :e, :p, :r, 'Active')
                ");
                $userStmt->execute([
                    'u' => $username,
                    'e' => $email,
                    'p' => $passHash,
                    'r' => $roleId
                ]);
                $userId = (int)$db->lastInsertId();
            }

            // Create Employee Record
            $code = 'KZ-' . rand(1000, 9999);

            $stmt = $db->prepare("
                INSERT INTO employees 
                (employee_code, user_id, first_name, last_name, gender, dob, phone, email, address, emergency_contact_name, emergency_contact_phone, department_id, position_id, employment_type, pay_cycle, date_joined, basic_salary, allowances, bank_name, bank_account_no, bank_branch, swift_code, tin_number, nida_number, nssf_number, status)
                VALUES
                (:code, :uid, :fn, :ln, :g, :dob, :phone, :email, :addr, :ename, :ephone, :did, :pid, :type, :pcycle, :dj, :sal, :alw, :bname, :bacct, :bbranch, :swift, :tin, :nida, :nssf, 'Active')
            ");
            $stmt->execute([
                'code' => $code, 'uid' => $userId, 'fn' => $firstName, 'ln' => $lastName, 'g' => $gender, 'dob' => $dob,
                'phone' => $phone, 'email' => $email, 'addr' => $address, 'ename' => $emergencyName,
                'ephone' => $emergencyPhone, 'did' => $deptId, 'pid' => $posId, 'type' => $type, 'pcycle' => $payCycle,
                'dj' => $dateJoined, 'sal' => $salary, 'alw' => $allowances,
                'bname' => $bankName, 'bacct' => $bankAccount, 'bbranch' => $bankBranch, 'swift' => $swiftCode,
                'tin' => $tinNumber, 'nida' => $nidaNumber, 'nssf' => $nssfNumber
            ]);

            $empId = $db->lastInsertId();

            // Populate default Leave Balance (28 days annual leave)
            $lbStmt = $db->prepare("
                INSERT INTO leave_balances (employee_id, leave_type_id, year, allocated_days, used_days, remaining_days)
                VALUES (:eid, 1, YEAR(NOW()), 28, 0, 28)
            ");
            $lbStmt->execute(['eid' => $empId]);

            AuditLogger::log('create_employee', 'Employees', (string)$empId, ['name' => "{$firstName} {$lastName}", 'username' => $username, 'status' => $userAccountMode]);
            
            $msg = ($userAccountMode === 'active') 
                ? "Employee {$firstName} {$lastName} created with Active User Account (Username: '{$username}')!"
                : "Employee {$firstName} {$lastName} registered successfully! System user account is set to Pending Activation.";
                
            redirect('/employees', $msg, 'success');
        } catch (PDOException $e) {
            error_log("Create Employee Exception: " . $e->getMessage());
            redirect('/employees/create', "Failed to create employee due to a database constraint error.", 'danger');
        }
    }

    public function show(): void {
        RoleMiddleware::hasPermission('employees.view');
        $db = Database::getInstance();
        $id = (int)($_GET['id'] ?? 0);
        $params = ['id' => $id];
        $employeeScope = '';

        if (AuthService::hasRole('employee')) {
            $viewer = AuthService::user();
            $employeeScope = ' AND e.user_id = :viewer_user_id';
            $params['viewer_user_id'] = $viewer['id'];
        }

        $stmt = $db->prepare("
            SELECT e.*, d.name as department_name, p.title as position_title,
                   m.first_name as manager_fn, m.last_name as manager_ln
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees m ON e.manager_id = m.id
            WHERE e.id = :id AND e.deleted_at IS NULL{$employeeScope}
        ");
        $stmt->execute($params);
        $employee = $stmt->fetch();

        if (!$employee) {
            redirect('/employees', 'Employee record not found.', 'danger');
        }

        // Attendance history
        $attStmt = $db->prepare("SELECT * FROM attendance WHERE employee_id = :id ORDER BY date DESC LIMIT 10");
        $attStmt->execute(['id' => $id]);
        $attendance = $attStmt->fetchAll();

        // Leave history
        $leaveStmt = $db->prepare("
            SELECT lr.*, lt.name as leave_type_name 
            FROM leave_requests lr 
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.employee_id = :id ORDER BY lr.created_at DESC
        ");
        $leaveStmt->execute(['id' => $id]);
        $leaves = $leaveStmt->fetchAll();

        // Documents
        $docStmt = $db->prepare("SELECT * FROM employee_documents WHERE employee_id = :id ORDER BY created_at DESC");
        $docStmt->execute(['id' => $id]);
        $documents = $docStmt->fetchAll();

        // Payslips
        $payStmt = $db->prepare("
            SELECT pi.*, pp.name as period_name 
            FROM payroll_items pi
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pi.employee_id = :id ORDER BY pi.created_at DESC
        ");
        $payStmt->execute(['id' => $id]);
        $payslips = $payStmt->fetchAll();

        view('employees.show', [
            'title'      => "Profile: {$employee['first_name']} {$employee['last_name']} — DonTech PeopleSuite",
            'employee'   => $employee,
            'attendance' => $attendance,
            'leaves'     => $leaves,
            'documents'  => $documents,
            'payslips'   => $payslips
        ]);
    }

    public function exportPdf(): void {
        RoleMiddleware::hasPermission('employees.view');
        $db = Database::getInstance();
        $id = (int)($_GET['id'] ?? 0);
        $params = ['id' => $id];
        $employeeScope = '';

        if (AuthService::hasRole('employee')) {
            $viewer = AuthService::user();
            $employeeScope = ' AND e.user_id = :viewer_user_id';
            $params['viewer_user_id'] = $viewer['id'];
        }

        $stmt = $db->prepare("
            SELECT e.*, d.name as department_name, p.title as position_title,
                   m.first_name as manager_fn, m.last_name as manager_ln
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees m ON e.manager_id = m.id
            WHERE e.id = :id AND e.deleted_at IS NULL{$employeeScope}
        ");
        $stmt->execute($params);
        $emp = $stmt->fetch();

        if (!$emp) {
            redirect('/employees', 'Employee record not found.', 'danger');
        }

        $fullName = $emp['first_name'] . ' ' . $emp['last_name'];
        $generatedAt = date('d M Y H:i:s');
        $filename = "profile_" . strtolower(str_replace(' ', '_', $fullName)) . ".pdf";

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Employee Profile Document — <?= htmlspecialchars($fullName) ?></title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; background: #f8fafc; }
                .loader-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }
                .spinner { width: 36px; height: 36px; border: 4px solid #e2e8f0; border-top: 4px solid #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                #pdfContent { background: white; padding: 35px; border-radius: 8px; width: 700px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; margin-bottom: 20px; }
                .title { font-size: 20px; font-weight: bold; color: #0f172a; }
                .subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
                .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #4f46e5; margin-top: 20px; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
                .grid { display: flex; flex-wrap: wrap; gap: 15px; }
                .col { flex: 1; min-width: 45%; }
                .field { margin-bottom: 10px; }
                .label { font-size: 10px; color: #64748b; font-weight: 500; }
                .value { font-size: 11px; font-weight: 600; color: #0f172a; margin-top: 2px; }
                .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 10px; color: #94a3b8; text-align: right; }
            </style>
        </head>
        <body>
            <div id="loader" class="loader-container">
                <div class="spinner"></div>
            </div>

            <div id="pdfContent">
                <div class="header">
                    <div>
                        <div class="title">DonTech PeopleSuite</div>
                        <div class="subtitle">Official Employee Dossier & Personnel Profile</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: bold; font-size: 12px; color: #0f172a;">DonTech Solutions Ltd</div>
                        <div class="subtitle">Generated: <?= $generatedAt ?></div>
                    </div>
                </div>

                <div class="section-title">1. Employee Primary Identification</div>
                <div class="grid">
                    <div class="col">
                        <div class="field"><div class="label">Full Name</div><div class="value"><?= htmlspecialchars($fullName) ?></div></div>
                        <div class="field"><div class="label">Employee System Code</div><div class="value" style="font-family: monospace; color: #4f46e5;"><?= htmlspecialchars($emp['employee_code']) ?></div></div>
                        <div class="field"><div class="label">Gender / DOB</div><div class="value"><?= htmlspecialchars($emp['gender']) ?> · <?= format_date($emp['dob']) ?></div></div>
                    </div>
                    <div class="col">
                        <div class="field"><div class="label">Current Status</div><div class="value" style="color: #047857;"><?= htmlspecialchars($emp['status']) ?></div></div>
                        <div class="field"><div class="label">Department</div><div class="value"><?= htmlspecialchars($emp['department_name'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">Job Position Title</div><div class="value"><?= htmlspecialchars($emp['position_title'] ?? 'N/A') ?></div></div>
                    </div>
                </div>

                <div class="section-title">2. Contact & Emergency Information</div>
                <div class="grid">
                    <div class="col">
                        <div class="field"><div class="label">Email Address</div><div class="value"><?= htmlspecialchars($emp['email']) ?></div></div>
                        <div class="field"><div class="label">Phone Number</div><div class="value"><?= htmlspecialchars($emp['phone']) ?></div></div>
                        <div class="field"><div class="label">Physical Address</div><div class="value"><?= htmlspecialchars($emp['address']) ?></div></div>
                    </div>
                    <div class="col">
                        <div class="field"><div class="label">Emergency Contact Person</div><div class="value"><?= htmlspecialchars($emp['emergency_contact_name'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">Emergency Phone</div><div class="value"><?= htmlspecialchars($emp['emergency_contact_phone'] ?? 'N/A') ?></div></div>
                    </div>
                </div>

                <div class="section-title">3. Bank & Statutory Identification</div>
                <div class="grid">
                    <div class="col">
                        <div class="field"><div class="label">Bank Name</div><div class="value"><?= htmlspecialchars($emp['bank_name'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">Account Number</div><div class="value" style="font-family: monospace;"><?= htmlspecialchars($emp['bank_account_no'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">Bank Branch / SWIFT</div><div class="value"><?= htmlspecialchars($emp['bank_branch'] ?? 'N/A') ?> (<?= htmlspecialchars($emp['swift_code'] ?? 'N/A') ?>)</div></div>
                    </div>
                    <div class="col">
                        <div class="field"><div class="label">TIN Number (TRA)</div><div class="value" style="font-family: monospace;"><?= htmlspecialchars($emp['tin_number'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">NIDA National ID</div><div class="value" style="font-family: monospace;"><?= htmlspecialchars($emp['nida_number'] ?? 'N/A') ?></div></div>
                        <div class="field"><div class="label">NSSF Number</div><div class="value" style="font-family: monospace;"><?= htmlspecialchars($emp['nssf_number'] ?? 'N/A') ?></div></div>
                    </div>
                </div>

                <div class="footer">
                    Page 1 of 1 — Confidential HR Record — Generated automatically by DonTech PeopleSuite System
                </div>
            </div>

            <script>
                window.onload = function() {
                    const element = document.getElementById('pdfContent');
                    const opt = {
                        margin:       0.3,
                        filename:     '<?= $filename ?>',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2 },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };

                    html2pdf().set(opt).from(element).save().then(function() {
                        window.close();
                    });
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function edit(): void {
        RoleMiddleware::hasPermission('employees.edit');
        $db = Database::getInstance();
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM employees WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch();

        if (!$employee) {
            redirect('/employees', 'Employee record not found.', 'danger');
        }

        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();
        $positions   = $db->query("SELECT * FROM positions WHERE status='Active'")->fetchAll();

        view('employees.edit', [
            'title'       => "Edit Profile: {$employee['first_name']} {$employee['last_name']} — DonTech PeopleSuite",
            'employee'    => $employee,
            'departments' => $departments,
            'positions'   => $positions
        ]);
    }

    public function update(): void {
        RoleMiddleware::hasPermission('employees.edit');
        $db = Database::getInstance();
        $id = (int)($_POST['id'] ?? 0);

        $firstName = sanitize_string($_POST['first_name'] ?? '');
        $lastName  = sanitize_string($_POST['last_name'] ?? '');
        $email     = sanitize_email($_POST['email'] ?? '');
        $phone     = sanitize_string($_POST['phone'] ?? '');
        $gender    = sanitize_string($_POST['gender'] ?? 'Male');
        $dob       = $_POST['dob'] ?? '1995-01-01';
        $address   = sanitize_string($_POST['address'] ?? '');
        $deptId    = (int)($_POST['department_id'] ?? 1);
        $posId     = (int)($_POST['position_id'] ?? 1);
        $type      = sanitize_string($_POST['employment_type'] ?? 'Full-time');
        $payCycle  = sanitize_string($_POST['pay_cycle'] ?? 'Monthly');
        $dateJoined= $_POST['date_joined'] ?? date('Y-m-d');
        $status    = sanitize_string($_POST['status'] ?? 'Active');
        $salary    = (float)($_POST['basic_salary'] ?? 0);
        $allowances= (float)($_POST['allowances'] ?? 0);
        $emergencyName  = sanitize_string($_POST['emergency_contact_name'] ?? '');
        $emergencyPhone = sanitize_string($_POST['emergency_contact_phone'] ?? '');

        // Bank Payment Information
        $bankName    = sanitize_string($_POST['bank_name'] ?? '');
        $bankAccount = sanitize_string($_POST['bank_account_no'] ?? '');
        $bankBranch  = sanitize_string($_POST['bank_branch'] ?? '');
        $swiftCode   = sanitize_string($_POST['swift_code'] ?? '');

        // Statutory Identifiers
        $tinNumber  = sanitize_string($_POST['tin_number'] ?? '');
        $nidaNumber = sanitize_string($_POST['nida_number'] ?? '');
        $nssfNumber = sanitize_string($_POST['nssf_number'] ?? '');

        $stmt = $db->prepare("
            UPDATE employees SET
                first_name = :fn, last_name = :ln, gender = :g, dob = :dob, phone = :phone,
                email = :email, address = :addr, emergency_contact_name = :ename, emergency_contact_phone = :ephone,
                department_id = :did, position_id = :pid, employment_type = :type, pay_cycle = :pcycle,
                date_joined = :dj, status = :status, basic_salary = :sal, allowances = :alw,
                bank_name = :bname, bank_account_no = :bacct, bank_branch = :bbranch, swift_code = :swift,
                tin_number = :tin, nida_number = :nida, nssf_number = :nssf
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([
            'fn' => $firstName, 'ln' => $lastName, 'g' => $gender, 'dob' => $dob, 'phone' => $phone,
            'email' => $email, 'addr' => $address, 'ename' => $emergencyName, 'ephone' => $emergencyPhone,
            'did' => $deptId, 'pid' => $posId, 'type' => $type, 'pcycle' => $payCycle, 'dj' => $dateJoined,
            'status' => $status, 'sal' => $salary, 'alw' => $allowances,
            'bname' => $bankName, 'bacct' => $bankAccount, 'bbranch' => $bankBranch, 'swift' => $swiftCode,
            'tin' => $tinNumber, 'nida' => $nidaNumber, 'nssf' => $nssfNumber, 'id' => $id
        ]);

        AuditLogger::log('update_employee', 'Employees', (string)$id, ['name' => "{$firstName} {$lastName}"]);
        redirect("/employees/view?id={$id}", "Employee profile for {$firstName} {$lastName} updated successfully!", 'success');
    }

    public function destroy(): void {
        RoleMiddleware::hasPermission('employees.delete');
        $db = Database::getInstance();
        $id = (int)($_POST['id'] ?? 0);

        // Soft deactivation: Set status to 'Inactive' without deleting historical record data
        $stmt = $db->prepare("UPDATE employees SET status = 'Inactive' WHERE id = :id");
        $stmt->execute(['id' => $id]);

        AuditLogger::log('delete_employee', 'Employees', (string)$id);
        redirect('/employees', 'Employee status updated to Inactive. All historical data retained successfully.', 'info');
    }
}
