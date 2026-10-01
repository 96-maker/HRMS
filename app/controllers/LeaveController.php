<?php
// app/controllers/LeaveController.php

class LeaveController {
    public function index(): void {
        RoleMiddleware::hasPermission('leave.view');
        self::autoCheckResumedLeaves();
        $db = Database::getInstance();

        // Fetch current user employee ID
        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $where = [];
        $params = [];

        // If logged in as regular Employee (role_slug = 'employee'), restrict leave requests strictly to self
        if (AuthService::hasRole('employee')) {
            $where[] = "lr.employee_id = :my_eid";
            $params['my_eid'] = $myEmpId;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $stmt = $db->prepare("
            SELECT lr.*, e.first_name, e.last_name, e.employee_code, d.name as department_name, lt.name as leave_type_name, lb.remaining_days
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN leave_balances lb ON (lb.employee_id = e.id AND lb.leave_type_id = lt.id AND lb.year = YEAR(NOW()))
            {$whereClause}
            ORDER BY lr.created_at DESC
        ");
        $stmt->execute($params);
        $leaveRequests = $stmt->fetchAll();

        if (AuthService::hasRole('employee')) {
            $employeesStmt = $db->prepare("SELECT id, first_name, last_name, department_id FROM employees WHERE user_id = :uid AND status != 'Terminated' AND deleted_at IS NULL");
            $employeesStmt->execute(['uid' => $user['id']]);
            $employees = $employeesStmt->fetchAll();
        } else {
            $employees = $db->query("SELECT id, first_name, last_name, department_id FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL")->fetchAll();
        }
        $leaveTypes = $db->query("SELECT * FROM leave_types")->fetchAll();

        view('leave.index', [
            'title'         => 'Leave Management — DonTech PeopleSuite',
            'leaveRequests' => $leaveRequests,
            'employees'     => $employees,
            'leaveTypes'    => $leaveTypes,
            'myEmpId'       => $myEmpId
        ]);
    }

    public function applyForm(): void {
        RoleMiddleware::hasPermission('leave.apply');
        $db = Database::getInstance();

        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        if (AuthService::hasRole('employee')) {
            $employeesStmt = $db->prepare("SELECT id, first_name, last_name, department_id FROM employees WHERE user_id = :uid AND status != 'Terminated' AND deleted_at IS NULL");
            $employeesStmt->execute(['uid' => $user['id']]);
            $employees = $employeesStmt->fetchAll();
        } else {
            $employees = $db->query("SELECT id, first_name, last_name, department_id FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL")->fetchAll();
        }
        $leaveTypes = $db->query("SELECT * FROM leave_types")->fetchAll();

        view('leave.apply', [
            'title'      => 'Submit Leave Application — DonTech PeopleSuite',
            'employees'  => $employees,
            'leaveTypes' => $leaveTypes,
            'myEmpId'    => $myEmpId
        ]);
    }

    public function apply(): void {
        RoleMiddleware::hasPermission('leave.apply');
        $db = Database::getInstance();

        $user = AuthService::user();
        $empId   = (int)($_POST['employee_id'] ?? 0);
        $typeId  = (int)($_POST['leave_type_id'] ?? 1);
        $reason  = sanitize_string($_POST['reason'] ?? '');
        $unit    = $_POST['duration_unit'] ?? 'days';

        if (AuthService::hasRole('employee')) {
            $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
            $myEmpStmt->execute(['uid' => $user['id']]);
            $empId = (int)($myEmpStmt->fetchColumn() ?: 0);
        }

        if ($empId <= 0) {
            redirect('/leave', 'A valid employee profile is required to submit leave.', 'danger');
            return;
        }
        
        if ($unit === 'minutes') {
            $minutes = max(1, (int)($_POST['duration_minutes'] ?? 2));
            $start = date('Y-m-d H:i:s');
            $end = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
            $totalDays = round($minutes / 1440, 4); // Fractional representation of day for 2 mins
        } else {
            $start = $_POST['start_date'] ?? date('Y-m-d');
            $end = $_POST['end_date'] ?? date('Y-m-d');

            $startDate = DateTime::createFromFormat('!Y-m-d', $start);
            $endDate = DateTime::createFromFormat('!Y-m-d', $end);
            $validDates = $startDate && $endDate
                && $startDate->format('Y-m-d') === $start
                && $endDate->format('Y-m-d') === $end
                && $endDate >= $startDate;

            if (!$validDates) {
                redirect('/leave', 'Leave start and end dates must be valid, and the end date cannot be before the start date.', 'danger');
                return;
            }

            $diff = ($endDate->getTimestamp() - $startDate->getTimestamp()) / 86400 + 1;
            $totalDays = max(1, (int)$diff);
        }

        // Check overlapping requests
        $overlapStmt = $db->prepare("
            SELECT COUNT(*) FROM leave_requests 
            WHERE employee_id = :eid AND status IN ('Pending', 'Approved') 
            AND ((start_date <= :end AND end_date >= :start))
        ");
        $overlapStmt->execute(['eid' => $empId, 'start' => $start, 'end' => $end]);
        if ($overlapStmt->fetchColumn() > 0) {
            redirect('/leave', 'Leave request failed: Overlapping leave request exists for these dates.', 'danger');
        }

        $stmt = $db->prepare("
            INSERT INTO leave_requests (employee_id, leave_type_id, start_date, end_date, total_days, reason, status)
            VALUES (:eid, :ltid, :sd, :ed, :td, :reason, 'Pending')
        ");
        $stmt->execute([
            'eid' => $empId, 'ltid' => $typeId, 'sd' => $start, 'ed' => $end, 'td' => $totalDays, 'reason' => $reason
        ]);

        $leaveId = $db->lastInsertId();

        // 1. Trigger SMS Alert to Manager / HR
        $applicant = $db->query("SELECT first_name, last_name, manager_id FROM employees WHERE id = {$empId}")->fetch();
        $appName = $applicant ? ($applicant['first_name'] . ' ' . $applicant['last_name']) : 'Employee';
        
        $managerPhone = null;
        if (!empty($applicant['manager_id'])) {
            $managerPhone = $db->query("SELECT phone FROM employees WHERE id = {$applicant['manager_id']}")->fetchColumn();
        }
        if (!$managerPhone) {
            $managerPhone = $db->query("SELECT setting_value FROM company_settings WHERE setting_key = 'company_phone'")->fetchColumn();
        }

        if (!empty($managerPhone)) {
            $durationLabel = ($unit === 'minutes') ? "Masaa/Dakika {$minutes}" : "Siku {$totalDays}";
            $msgToManager = "Kuna ombi jipya la Likizo kutoka kwa {$appName} ({$durationLabel}). Ingia kwenye mfumo kutoa idhini.";
            SmsService::sendSms($managerPhone, $msgToManager);
        }

        // Trigger System In-App Notification to System Admins
        NotificationService::send(null, "New Leave Request", "{$appName} has submitted a new leave application.", "/leave");

        AuditLogger::log('apply_leave', 'Leave', (string)$leaveId);
        redirect('/leave', 'Leave application submitted successfully!', 'success');
    }

    public function approve(): void {
        RoleMiddleware::hasPermission('leave.approve');
        $db = Database::getInstance();
        $user = AuthService::user();
        $id = (int)($_POST['id'] ?? 0);

        // Fetch request details
        $reqStmt = $db->prepare("SELECT * FROM leave_requests WHERE id = :id");
        $reqStmt->execute(['id' => $id]);
        $req = $reqStmt->fetch();

        if ($req && $req['status'] === 'Pending') {
            $db->beginTransaction();
            try {
                // Update request
                $up = $db->prepare("UPDATE leave_requests SET status = 'Approved', approved_by = :uid, approved_at = NOW() WHERE id = :id AND status = 'Pending'");
                $up->execute(['uid' => $user['id'], 'id' => $id]);

                if ($up->rowCount() !== 1) {
                    throw new RuntimeException('Leave request is no longer pending.');
                }

                // Update employee status to 'On Leave'
                $empUp = $db->prepare("UPDATE employees SET status = 'On Leave' WHERE id = :eid");
                $empUp->execute(['eid' => $req['employee_id']]);

                // Subtract balance
                $subDays = max(1, (int)$req['total_days']);
                $balUp = $db->prepare("
                    UPDATE leave_balances 
                    SET used_days = used_days + :td1, remaining_days = remaining_days - :td2
                    WHERE employee_id = :eid AND leave_type_id = :ltid AND year = YEAR(NOW())
                ");
                $balUp->execute(['td1' => $subDays, 'td2' => $subDays, 'eid' => $req['employee_id'], 'ltid' => $req['leave_type_id']]);

                if ($balUp->rowCount() !== 1) {
                    throw new RuntimeException('Insufficient leave balance or leave balance record not found.');
                }

                $db->commit();

                // 2. Trigger SMS Alerts to Employee upon approval
                $emp = $db->query("SELECT first_name, user_id, phone FROM employees WHERE id = {$req['employee_id']}")->fetch();
                if ($emp) {
                    if (!empty($emp['user_id'])) {
                        NotificationService::send((int)$emp['user_id'], "Leave Approved", "Your leave application has been approved.", "/leave");
                    }
                    if (!empty($emp['phone'])) {
                        $firstName = $emp['first_name'] ?? 'Mtumishi';
                        $formattedStart = date('d/m/Y', strtotime($req['start_date']));
                        $formattedEnd   = date('d/m/Y', strtotime($req['end_date']));
                        $reportingDate  = date('d/m/Y', strtotime($req['end_date'] . ' +1 day'));

                        // SMS 1: Official Approval SMS
                        $msgApproval = "Habari {$firstName}, ombi lako la likizo kuanzia {$formattedStart} hadi {$formattedEnd} limeidhinishwa rasmi na Uongozi. Tunakutakia mapumziko mema.";
                        SmsService::sendSms($emp['phone'], $msgApproval);

                        // SMS 2: Official Leave Resumption Reminder SMS
                        $msgReminder = "KUMBUSHO: Likizo yako inafikia tamati tarehe {$formattedEnd}. Unatarajiwa kuripoti kazini tarehe {$reportingDate} kuanzia saa 02:00 asubuhi. Karibu tena!";
                        SmsService::sendSms($emp['phone'], $msgReminder);
                    }
                }

                AuditLogger::log('approve_leave', 'Leave', (string)$id);
                redirect('/leave', 'Leave request approved and notifications sent successfully!', 'success');
            } catch (Exception $e) {
                $db->rollBack();
                redirect('/leave', 'Failed to approve leave: ' . $e->getMessage(), 'danger');
            }
        }
        redirect('/leave', 'Invalid request.', 'warning');
    }

    public function reject(): void {
        RoleMiddleware::hasPermission('leave.approve');
        $db = Database::getInstance();
        $id = (int)($_POST['id'] ?? 0);

        $reqStmt = $db->prepare("SELECT * FROM leave_requests WHERE id = :id");
        $reqStmt->execute(['id' => $id]);
        $req = $reqStmt->fetch();

        $rejectionReason = !empty($_POST['rejection_reason'])
            ? sanitize_string($_POST['rejection_reason'])
            : null;
        $stmt = $db->prepare("UPDATE leave_requests SET status = 'Rejected', rejection_reason = :reason WHERE id = :id AND status = 'Pending'");
        $stmt->execute(['id' => $id, 'reason' => $rejectionReason]);

        if ($stmt->rowCount() !== 1) {
            redirect('/leave', 'Leave request is no longer pending.', 'warning');
            return;
        }

        if ($req) {
            $emp = $db->query("SELECT first_name, user_id, phone FROM employees WHERE id = {$req['employee_id']}")->fetch();
            if ($emp) {
                if (!empty($emp['user_id'])) {
                    NotificationService::send((int)$emp['user_id'], "Leave Rejected", "Your leave application was not approved.", "/leave");
                }
                if (!empty($emp['phone'])) {
                    $firstName = $emp['first_name'] ?? 'Mtumishi';
                    $formattedStart = date('d/m/Y', strtotime($req['start_date']));
                    $reason = $rejectionReason ?: 'sababu za kiofisi';

                    $msgToEmp = "Habari {$firstName}, ombi lako la likizo kuanzia tarehe {$formattedStart} halijapitishwa ({$reason}). Tafadhali wasiliana na ofisi ya HR kwa ufafanuzi zaidi.";
                    SmsService::sendSms($emp['phone'], $msgToEmp);
                }
            }
        }

        AuditLogger::log('reject_leave', 'Leave', (string)$id);
        redirect('/leave', 'Leave request rejected.', 'info');
    }

    public static function autoCheckResumedLeaves(): void {
        $db = Database::getInstance();

        // 1. Auto fix 0 days calculation for same-day requests
        $db->query("UPDATE leave_requests SET total_days = GREATEST(1, DATEDIFF(end_date, start_date) + 1) WHERE total_days <= 0 OR total_days IS NULL");

        // 2. Find and auto-complete expired leave requests (where end_date < CURRENT_DATE())
        $expiredLeaves = $db->query("
            SELECT lr.id, lr.employee_id 
            FROM leave_requests lr
            WHERE lr.status IN ('Approved', '') AND lr.end_date < CURRENT_DATE()
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($expiredLeaves)) {
            $db->beginTransaction();
            try {
                foreach ($expiredLeaves as $leave) {
                    $upReq = $db->prepare("UPDATE leave_requests SET status = 'Completed' WHERE id = :id");
                    $upReq->execute(['id' => $leave['id']]);

                    $upEmp = $db->prepare("UPDATE employees SET status = 'Active' WHERE id = :eid AND status = 'On Leave'");
                    $upEmp->execute(['eid' => $leave['employee_id']]);
                }
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
            }
        }
    }

    public function printForm(): void {
        if (!AuthService::hasPermission('leave.view') && !AuthService::hasPermission('leave.apply')) {
            RoleMiddleware::hasPermission('leave.view');
        }
        $db = Database::getInstance();
        $id = (int)($_GET['id'] ?? 0);

        // Fetch current user employee ID
        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT lr.*, e.employee_code, e.first_name, e.last_name, e.date_joined, e.phone as emp_phone, e.emergency_contact_name, e.emergency_contact_phone,
                   d.name as department_name, p.title as position_title,
                   lt.name as leave_type_name,
                   m.first_name as manager_fn, m.last_name as manager_ln,
                   lb.allocated_days, lb.used_days, lb.remaining_days
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN employees m ON e.manager_id = m.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            LEFT JOIN leave_balances lb ON (lb.employee_id = e.id AND lb.leave_type_id = lt.id AND lb.year = YEAR(NOW()))
            WHERE lr.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $leave = $stmt->fetch();

        if (!$leave) {
            redirect('/leave', 'Leave request form not found.', 'danger');
        }

        // Restrict regular employee to viewing strictly their own form
        if (AuthService::hasRole('employee') && (int)$leave['employee_id'] !== $myEmpId) {
            redirect('/leave', 'Hauwezi kutazama au kupakua fomu ya likizo ya mfanyakazi mwingine.', 'danger');
            return;
        }

        // Fetch company profile settings directly from company_settings table
        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $leave['company_name']    = !empty($companySettings['company_name']) ? $companySettings['company_name'] : '';
        $leave['company_address'] = !empty($companySettings['company_address']) ? $companySettings['company_address'] : '';
        $leave['company_phone']   = !empty($companySettings['company_phone']) ? $companySettings['company_phone'] : '';
        $leave['company_email']   = !empty($companySettings['company_email']) ? $companySettings['company_email'] : '';
        $leave['company_tin']     = !empty($companySettings['company_tin']) ? $companySettings['company_tin'] : '';

        $generatedAt = date('d M Y H:i:s');
        $refCode = "LV-FORM-" . str_pad($leave['id'], 5, '0', STR_PAD_LEFT);
        $empName = $leave['first_name'] . ' ' . $leave['last_name'];
        $resumptionDate = date('Y-m-d', strtotime($leave['end_date'] . ' +1 day'));

        $allocated = (int)($leave['allocated_days'] ?? 28);
        $usedPrior = (int)($leave['used_days'] ?? 0);
        $reqDays   = (int)$leave['total_days'];
        $newBalance = max(0, $allocated - $usedPrior - ($leave['status'] === 'Approved' ? 0 : $reqDays));

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <title>Official Leave Form - <?= htmlspecialchars($refCode) ?></title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5px; color: #0f172a; background: #f1f5f9; line-height: 1.35; margin: 0; padding: 20px; }
                
                .action-bar { max-width: 800px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; background: #1e293b; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .btn-download { background: #4f46e5; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 6px; transition: background 0.2s; }
                .btn-download:hover { background: #4338ca; }
                .btn-back { color: #cbd5e1; text-decoration: none; font-size: 12px; font-weight: 500; }
                .btn-back:hover { color: white; }

                .a4-page { width: 780px; padding: 25px; background: white; margin: 0 auto; border: 1px solid #cbd5e1; border-radius: 4px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); position: relative; }
                
                /* Header */
                .company-header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 12px; }
                .brand-title { font-size: 18px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
                .brand-subtitle { font-size: 10px; color: #475569; font-weight: 600; }
                .org-meta { text-align: right; font-size: 9px; color: #475569; }
                .doc-banner { background: #f1f5f9; border: 1px solid #cbd5e1; text-align: center; padding: 6px; margin-bottom: 14px; border-radius: 4px; }
                .doc-title { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; }
                .doc-ref { font-size: 9.5px; font-family: monospace; font-weight: 700; color: #4f46e5; margin-top: 2px; }

                /* Section Box */
                .sec-heading { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e293b; background: #e2e8f0; padding: 4px 8px; border: 1px solid #cbd5e1; border-bottom: none; border-radius: 3px 3px 0 0; margin-top: 10px; }
                .sec-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; border: 1px solid #cbd5e1; }
                .sec-table td, .sec-table th { padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 10px; }
                .sec-table th { background: #f8fafc; font-weight: 700; color: #475569; text-align: left; width: 22%; }
                .sec-table td { background: white; color: #0f172a; font-weight: 600; }

                .val-mono { font-family: monospace; font-size: 10.5px; }
                .status-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
                .st-approved { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
                .st-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
                .st-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

                /* Signatures 3 Columns */
                .signatures-grid { display: flex; gap: 10px; margin-top: 15px; }
                .sig-box { flex: 1; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; background: white; min-height: 180px; display: flex; flex-direction: column; justify-content: space-between; }
                .sig-title { font-size: 9.5px; font-weight: 800; text-transform: uppercase; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px; }
                .sig-line-item { margin-bottom: 6px; font-size: 9.5px; }
                .stamp-container { width: 1.5in; height: 1.5in; border: 2px dashed #94a3b8; border-radius: 4px; margin: 6px auto 0 auto; display: flex; align-items: center; justify-content: center; text-align: center; color: #94a3b8; font-size: 8px; font-weight: 700; text-transform: uppercase; background: #fafafa; }

                .footer-legal { text-align: center; font-size: 8.5px; color: #64748b; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 6px; }

                @media print {
                    .action-bar { display: none !important; }
                    body { background: white; padding: 0; }
                    .a4-page { border: none; box-shadow: none; padding: 0; width: 100%; }
                }
            </style>
        </head>
        <body>
            <div class="action-bar">
                <a href="javascript:history.back()" class="btn-back">← Back to Leave Management</a>
                <span style="font-weight: 600; font-size: 13px;">DonTech PeopleSuite — Official Leave Form Preview</span>
                <button class="btn-download" onclick="downloadLeavePDF()">
                    ⬇ Download PDF Form
                </button>
            </div>

            <div id="leaveFormPdf" class="a4-page">
                <!-- 1. Corporate Header (Client Brand Primary) -->
                <div class="company-header">
                    <div>
                        <div class="brand-title"><?= htmlspecialchars($leave['company_name']) ?></div>
                        <div class="brand-subtitle">Official Corporate Statutory Leave Application</div>
                    </div>
                    <div class="org-meta">
                        <div>Location: <?= htmlspecialchars($leave['company_address']) ?></div>
                        <div>Email: <?= htmlspecialchars($leave['company_email']) ?> | Phone: <?= htmlspecialchars($leave['company_phone']) ?></div>
                        <div>TIN: <?= htmlspecialchars($leave['company_tin']) ?> | Form Generation: <?= $generatedAt ?></div>
                    </div>
                </div>

                <div class="doc-banner">
                    <div class="doc-title">OFFICIAL LEAVE APPLICATION & APPROVAL FORM</div>
                    <div class="doc-ref">REF NO: <?= htmlspecialchars($refCode) ?></div>
                </div>

                <!-- 2. Employee Particulars -->
                <div class="sec-heading">1. Employee Particulars</div>
                <table class="sec-table">
                    <tr>
                        <th>Full Name</th>
                        <td><?= htmlspecialchars($empName) ?></td>
                        <th>Employee ID</th>
                        <td class="val-mono"><?= htmlspecialchars($leave['employee_code']) ?></td>
                    </tr>
                    <tr>
                        <th>Department</th>
                        <td><?= htmlspecialchars($leave['department_name'] ?? 'N/A') ?></td>
                        <th>Job Title / Position</th>
                        <td><?= htmlspecialchars($leave['position_title'] ?? 'N/A') ?></td>
                    </tr>
                    <tr>
                        <th>Date of Joining</th>
                        <td class="val-mono"><?= format_date($leave['date_joined']) ?></td>
                        <th>Contact Telephone</th>
                        <td class="val-mono"><?= format_phone($leave['emp_phone']) ?></td>
                    </tr>
                </table>

                <!-- 3. Leave Particulars -->
                <div class="sec-heading">2. Leave Particulars & Request Details</div>
                <table class="sec-table">
                    <tr>
                        <th>Leave Category</th>
                        <td><?= htmlspecialchars($leave['leave_type_name']) ?></td>
                        <th>Application Status</th>
                        <td>
                            <?php 
                                $st = $leave['status'];
                                $badgeClass = $st === 'Approved' ? 'st-approved' : ($st === 'Pending' ? 'st-pending' : 'st-rejected');
                            ?>
                            <span class="status-badge <?= $badgeClass ?>"><?= $st ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td class="val-mono"><?= format_date($leave['start_date']) ?></td>
                        <th>End Date</th>
                        <td class="val-mono"><?= format_date($leave['end_date']) ?></td>
                    </tr>
                    <tr>
                        <th>Total Days Requested</th>
                        <td class="val-mono" style="font-weight: 800; font-size: 11px;"><?= $leave['total_days'] ?> Days</td>
                        <th>Expected Work Resumption</th>
                        <td class="val-mono" style="color: #4f46e5; font-weight: 800;"><?= format_date($resumptionDate) ?></td>
                    </tr>
                    <tr>
                        <th>Reason / Justification</th>
                        <td colspan="3" style="font-style: italic;"><?= htmlspecialchars($leave['reason']) ?></td>
                    </tr>
                    <tr>
                        <th>Emergency Contact Person</th>
                        <td><?= htmlspecialchars($leave['emergency_contact_name'] ?? 'N/A') ?></td>
                        <th>Emergency Contact Phone</th>
                        <td class="val-mono"><?= format_phone($leave['emergency_contact_phone'] ?? '') ?></td>
                    </tr>
                </table>

                <!-- 4. HR Leave Balance Audit Summary -->
                <div class="sec-heading">3. HR Leave Balance Audit Summary (Annual Statutory Entitlement)</div>
                <table class="sec-table">
                    <tr>
                        <th>Annual Entitlement / Opening</th>
                        <td class="val-mono"><?= $allocated ?> Days</td>
                        <th>Days Taken Prior to Request</th>
                        <td class="val-mono"><?= $usedPrior ?> Days</td>
                    </tr>
                    <tr>
                        <th>Days Requested in Application</th>
                        <td class="val-mono" style="color: #dc2626;"><?= $reqDays ?> Days</td>
                        <th>New Balance Remaining</th>
                        <td class="val-mono" style="color: #16a34a; font-weight: 800; font-size: 11px;"><?= $newBalance ?> Days Remaining</td>
                    </tr>
                </table>

                <!-- 5. Dedicated Physical Signature & Official Stamp Blocks -->
                <div class="sec-heading">4. Authorization Signatures & Ink Rubber Stamp Blocks</div>
                <div class="signatures-grid">
                    <!-- SECTION 1: APPLICANT SIGNATURE -->
                    <div class="sig-box">
                        <div>
                            <div class="sig-title">[ SECTION 1: APPLICANT ]</div>
                            <div class="sig-line-item"><strong>Name:</strong> <?= htmlspecialchars($empName) ?></div>
                            <div class="sig-line-item" style="margin-top: 15px;"><strong>Signature:</strong> _____________________</div>
                            <div class="sig-line-item" style="margin-top: 12px;"><strong>Date:</strong> _____ / _____ / 20___</div>
                        </div>
                        <div style="font-size: 8.5px; color: #64748b; font-style: italic; margin-top: 10px;">
                            I hereby confirm the leave details provided above are accurate.
                        </div>
                    </div>

                    <!-- SECTION 2: HEAD OF DEPARTMENT / LINE MANAGER -->
                    <div class="sig-box">
                        <div>
                            <div class="sig-title">[ SECTION 2: LINE MANAGER ]</div>
                            <div class="sig-line-item"><strong>Manager Name:</strong> _________________</div>
                            <div class="sig-line-item" style="margin-top: 4px;">
                                <strong>Recommendation:</strong><br>
                                [ &nbsp; ] Recommended &nbsp;&nbsp;&nbsp; [ &nbsp; ] Not Recommended
                            </div>
                            <div class="sig-line-item" style="margin-top: 6px;"><strong>Signature:</strong> _____________________</div>
                            <div class="sig-line-item" style="margin-top: 4px;"><strong>Date:</strong> _____ / _____ / 20___</div>
                        </div>
                        <div class="stamp-container">
                            Departmental<br>Rubber Stamp Box<br>(Min 1.5" x 1.5")
                        </div>
                    </div>

                    <!-- SECTION 3: HUMAN RESOURCES & EXECUTIVE APPROVAL -->
                    <div class="sig-box">
                        <div>
                            <div class="sig-title">[ SECTION 3: HR APPROVAL ]</div>
                            <div class="sig-line-item"><strong>Authorizing Officer:</strong> _______________</div>
                            <div class="sig-line-item" style="margin-top: 4px;">
                                <strong>Final Determination:</strong><br>
                                [ &nbsp; ] Approved &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp; ] Rejected
                            </div>
                            <div class="sig-line-item" style="margin-top: 6px;"><strong>Signature:</strong> _____________________</div>
                            <div class="sig-line-item" style="margin-top: 4px;"><strong>Date:</strong> _____ / _____ / 20___</div>
                        </div>
                        <div class="stamp-container">
                            Official HR Seal /<br>Company Rubber Stamp<br>(Min 1.5" x 1.5")
                        </div>
                    </div>
                </div>

                <div class="footer-legal">
                    Generated securely via DonTech PeopleSuite HRMS | Technology Partner: DonTech Solutions Ltd
                </div>
            </div>

            <script>
                function downloadLeavePDF() {
                    const element = document.getElementById('leaveFormPdf');
                    const opt = {
                        margin:       [0.2, 0.2, 0.2, 0.2],
                        filename:     'leave_form_<?= strtolower(str_replace('-', '_', $refCode)) ?>.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true, logging: false },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };
                    html2pdf().set(opt).from(element).save();
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
