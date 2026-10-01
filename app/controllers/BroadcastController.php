<?php
// app/controllers/BroadcastController.php

class BroadcastController {
    public function index(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        $broadcasts = $db->query("
            SELECT b.*, u.username as sender_name, d.name as department_name
            FROM broadcasts b
            LEFT JOIN users u ON b.sent_by = u.id
            LEFT JOIN departments d ON b.department_id = d.id
            ORDER BY b.created_at DESC
        ")->fetchAll();

        $departments = $db->query("SELECT * FROM departments WHERE status='Active'")->fetchAll();

        view('broadcasts.index', [
            'title'       => 'Internal Broadcasts & Announcements — DonTech PeopleSuite',
            'broadcasts'  => $broadcasts,
            'departments' => $departments
        ]);
    }

    public function send(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();
        $user = AuthService::user();

        $title   = sanitize_string($_POST['title'] ?? '');
        $message = sanitize_string($_POST['message'] ?? '');
        $target  = sanitize_string($_POST['target_audience'] ?? 'All Employees');
        $deptId  = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;

        if (empty($title) || empty($message)) {
            redirect('/broadcasts', 'Title and message cannot be empty.', 'danger');
        }

        // Save broadcast record
        $stmt = $db->prepare("
            INSERT INTO broadcasts (title, message, target_audience, department_id, sent_by)
            VALUES (:title, :msg, :target, :dept, :uid)
        ");
        $stmt->execute([
            'title' => $title, 'msg' => $message, 'target' => $target,
            'dept' => $deptId, 'uid' => $user['id']
        ]);

        // Fetch target employees phone numbers
        if ($target === 'Department' && $deptId) {
            $empStmt = $db->prepare("SELECT phone FROM employees WHERE department_id = :did AND status != 'Terminated' AND deleted_at IS NULL");
            $empStmt->execute(['did' => $deptId]);
        } else {
            $empStmt = $db->query("SELECT phone FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL");
        }
        $phones = $empStmt->fetchAll(PDO::FETCH_COLUMN);

        $smsMessage = (strpos($message, 'TAARIFA:') === 0 || strpos($message, '[') === 0) ? $message : "TAARIFA: {$title} — {$message}";
        $sentCount = SmsService::sendBulkSms($phones, $smsMessage);

        AuditLogger::log('send_broadcast', 'Broadcasts', (string)$db->lastInsertId(), ['sent_count' => $sentCount]);
        redirect('/broadcasts', "Broadcast announcement sent successfully via SMS to {$sentCount} employees!", 'success');
    }

    public function birthdayWishes(): void {
        RoleMiddleware::hasPermission('settings.manage');
        $db = Database::getInstance();

        // Fetch employees having birthday today
        $todayMonth = date('m');
        $todayDay   = date('d');

        $stmt = $db->prepare("
            SELECT * FROM employees 
            WHERE MONTH(dob) = :m AND DAY(dob) = :d 
            AND status != 'Terminated' AND deleted_at IS NULL
        ");
        $stmt->execute(['m' => $todayMonth, 'd' => $todayDay]);
        $birthdayEmps = $stmt->fetchAll();

        $sentCount = 0;
        foreach ($birthdayEmps as $emp) {
            $msg = "Dear {$emp['first_name']}, DonTech Solutions Ltd wishes you a very Happy Birthday! 🎉 Wishing you continued success, happiness, and good health.";
            if (SmsService::sendSms($emp['phone'], $msg)) {
                $sentCount++;
            }
        }

        redirect('/broadcasts', "Automated Birthday Wishes sent to {$sentCount} employee(s) celebrating today!", 'success');
    }
}
