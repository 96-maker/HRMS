<?php
// app/controllers/ReportController.php

class ReportController {
    public function index(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();

        $departmentFilter = (int)($_GET['department_id'] ?? 0);
        $statusFilter = sanitize_string($_GET['status'] ?? 'all');
        $employeeWhere = ['e.deleted_at IS NULL', "e.status != 'Terminated'"];
        $employeeParams = [];

        if ($departmentFilter > 0) {
            $employeeWhere[] = 'e.department_id = :department_id';
            $employeeParams['department_id'] = $departmentFilter;
        }
        if ($statusFilter !== '' && $statusFilter !== 'all') {
            $employeeWhere[] = 'e.status = :employee_status';
            $employeeParams['employee_status'] = $statusFilter;
        }
        $employeeWhereSql = implode(' AND ', $employeeWhere);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM employees e WHERE {$employeeWhereSql}");
        $countStmt->execute($employeeParams);
        $totalEmp = $countStmt->fetchColumn();

        $genderStmt = $db->prepare("SELECT gender, COUNT(*) AS total FROM employees e WHERE {$employeeWhereSql} GROUP BY gender");
        $genderStmt->execute($employeeParams);
        $genderCounts = ['Male' => 0, 'Female' => 0];
        foreach ($genderStmt->fetchAll() as $gender) {
            $genderCounts[$gender['gender']] = (int)$gender['total'];
        }
        $maleEmp = $genderCounts['Male'];
        $femaleEmp = $genderCounts['Female'];

        $deptStmt = $db->prepare("
            SELECT d.name, SUM(e.basic_salary + e.allowances) as monthly_cost, COUNT(e.id) as staff_count
            FROM departments d
            LEFT JOIN employees e ON e.department_id = d.id AND {$employeeWhereSql}
            GROUP BY d.id
            ORDER BY monthly_cost DESC, d.name ASC
        ");
        $deptStmt->execute($employeeParams);
        $deptCosts = $deptStmt->fetchAll();

        $attendanceStmt = $db->query("SELECT status, COUNT(*) AS total FROM attendance WHERE date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') GROUP BY status");
        $attendanceSummary = [];
        foreach ($attendanceStmt->fetchAll() as $attendance) {
            $attendanceSummary[$attendance['status']] = (int)$attendance['total'];
        }

        $pendingLeave = (int)$db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
        $payrollStmt = $db->query("SELECT COALESCE(SUM(pi.gross_salary), 0) AS gross_total, COALESCE(SUM(pi.net_salary), 0) AS net_total FROM payroll_items pi JOIN payroll_periods pp ON pp.id = pi.payroll_period_id WHERE pp.month = MONTH(CURDATE()) AND pp.year = YEAR(CURDATE())");
        $payrollSummary = $payrollStmt->fetch(PDO::FETCH_ASSOC);
        $departments = $db->query("SELECT id, name FROM departments WHERE status = 'Active' ORDER BY name ASC")->fetchAll();

        view('reports.index', [
            'title'       => 'Reports & Workforce Analytics — DonTech PeopleSuite',
            'totalEmp'    => $totalEmp,
            'maleEmp'     => $maleEmp,
            'femaleEmp'   => $femaleEmp,
            'deptCosts'   => $deptCosts,
            'departments' => $departments,
            'departmentFilter' => $departmentFilter,
            'statusFilter' => $statusFilter,
            'attendanceSummary' => $attendanceSummary,
            'pendingLeave' => $pendingLeave,
            'payrollSummary' => $payrollSummary
        ]);
    }

    public function exportCsv(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();
        $type = sanitize_string($_GET['type'] ?? 'employees');

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$type}_export_" . date('Y-m-d') . ".csv");

        $output = fopen('php://output', 'w');

        if ($type === 'employees') {
            fputcsv($output, ['Code', 'First Name', 'Last Name', 'Gender', 'Email', 'Phone', 'Department', 'Position', 'Basic Salary (TZS)', 'Status']);
            $rows = $db->query("
                SELECT e.employee_code, e.first_name, e.last_name, e.gender, e.email, e.phone, COALESCE(d.name, 'N/A') as department_name, COALESCE(p.title, 'N/A') as position_title, e.basic_salary, e.status
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE e.deleted_at IS NULL
            ")->fetchAll();
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['employee_code'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['gender'],
                    $row['email'],
                    $row['phone'],
                    $row['department_name'],
                    $row['position_title'],
                    $row['basic_salary'],
                    $row['status']
                ]);
            }
        }
        fclose($output);
        exit;
    }

    public function exportExcel(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();
        $type = sanitize_string($_GET['type'] ?? 'employees');

        $company = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cName  = $company['company_name'] ?? 'DonTech Solutions Ltd';
        $cTin   = $company['company_tin'] ?? '109-482-771';
        $cAddr  = $company['company_address'] ?? 'Plot 45, Bagamoyo Rd, Dar es Salaam';

        // Clean output buffers to prevent corrupting binary/text output
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header("Content-Disposition: attachment; filename={$type}_export_" . date('Y-m-d') . ".csv");
        header('Cache-Control: max-age=0');

        // Output UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Output Corporate Header metadata rows in Excel
        fputcsv($output, [$cName]);
        fputcsv($output, ["System", "DonTech PeopleSuite — Enterprise HR & Payroll"]);
        fputcsv($output, ["Address & TIN", "{$cAddr} | TIN: {$cTin}"]);
        fputcsv($output, ["Exported Date", date('d M Y H:i:s')]);
        fputcsv($output, []); // Blank spacer line

        if ($type === 'employees') {
            fputcsv($output, ['Employee Code', 'First Name', 'Last Name', 'Gender', 'Email Address', 'Phone Number', 'Department', 'Job Position', 'Basic Salary (TZS)', 'Status']);
            $rows = $db->query("
                SELECT e.employee_code, e.first_name, e.last_name, e.gender, e.email, e.phone, COALESCE(d.name, 'N/A') as department_name, COALESCE(p.title, 'N/A') as position_title, e.basic_salary, e.status
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE e.deleted_at IS NULL
            ")->fetchAll();
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['employee_code'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['gender'],
                    $row['email'],
                    $row['phone'],
                    $row['department_name'],
                    $row['position_title'],
                    $row['basic_salary'],
                    $row['status']
                ]);
            }
        }
        fclose($output);
        exit;
    }

    public function exportPdf(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();
        $type = sanitize_string($_GET['type'] ?? 'employees');

        $company = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cName  = $company['company_name'] ?? 'DonTech Solutions Ltd';
        $cTin   = $company['company_tin'] ?? '109-482-771';
        $cAddr  = $company['company_address'] ?? 'Plot 45, Bagamoyo Rd, Dar es Salaam';
        $cPhone = $company['company_phone'] ?? '+255 22 211 4455';
        $cEmail = $company['company_email'] ?? 'info@dontech.co.tz';

        $rows = $db->query("
            SELECT e.employee_code, e.first_name, e.last_name, e.gender, e.email, e.phone, COALESCE(d.name, 'N/A') as department_name, COALESCE(p.title, 'N/A') as position_title, e.basic_salary, e.status
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE e.deleted_at IS NULL
        ")->fetchAll();

        $generatedAt = date('d M Y H:i:s');
        $filename = "{$type}_report_" . date('Y-m-d') . ".pdf";

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Downloading PDF Report — DonTech PeopleSuite</title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; background: #f8fafc; }
                .loader-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }
                .spinner { width: 36px; height: 36px; border: 4px solid #e2e8f0; border-top: 4px solid #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                #pdfContent { background: white; padding: 30px; border-radius: 8px; width: 800px; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; margin-bottom: 20px; }
                .title { font-size: 18px; font-weight: bold; color: #0f172a; }
                .subtitle { font-size: 10px; color: #64748b; margin-top: 2px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th { background-color: #f1f5f9; color: #334155; text-align: left; padding: 8px 10px; border-bottom: 2px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
                td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
                tr:nth-child(even) { background-color: #f8fafc; }
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
                        <div class="title"><?= htmlspecialchars($cName) ?></div>
                        <div class="subtitle">DonTech PeopleSuite — Enterprise Workforce & Employee Profile Report</div>
                        <div class="subtitle"><?= htmlspecialchars($cAddr) ?> | Phone: <?= htmlspecialchars($cPhone) ?> | Email: <?= htmlspecialchars($cEmail) ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: bold; font-size: 11px; color: #0f172a;">TIN: <?= htmlspecialchars($cTin) ?></div>
                        <div class="subtitle">Generated: <?= $generatedAt ?></div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th style="text-align: right;">Basic Salary (TZS)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: bold; color: #4f46e5;"><?= htmlspecialchars($r['employee_code']) ?></td>
                            <td style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td><?= htmlspecialchars($r['gender']) ?></td>
                            <td><?= htmlspecialchars($r['department_name']) ?></td>
                            <td><?= htmlspecialchars($r['position_title']) ?></td>
                            <td style="font-family: monospace; font-weight: bold; text-align: right; color: #0f172a;"><?= number_format($r['basic_salary'], 2) ?></td>
                            <td>
                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; background: #ecfdf5; color: #047857;">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="footer">
                    Generated securely via DonTech PeopleSuite HRMS | Technology Partner: DonTech Solutions Ltd
                </div>
            </div>

            <script>
                window.onload = function () {
                    const element = document.getElementById('pdfContent');
                    const opt = {
                        margin:       [0.4, 0.4, 0.4, 0.4],
                        filename:     '<?= $filename ?>',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2 },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
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
}
