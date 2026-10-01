<?php
// app/controllers/DashboardController.php

class DashboardController {
    public function index(): void {
        AuthMiddleware::handle();
        LeaveController::autoCheckResumedLeaves();

        // If logged in user is a regular Employee, redirect directly to Attendance portal
        if (AuthService::hasRole('employee')) {
            redirect('/attendance');
            return;
        }

        $db = Database::getInstance();

        // 1. Total Employees Count
        $totalEmp = $db->query("SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL AND status != 'Terminated'")->fetchColumn();

        // 2. Present Today Count
        $today = date('Y-m-d');
        $presentToday = $db->query("SELECT COUNT(*) FROM attendance WHERE date = '{$today}' AND status IN ('Present', 'Late')")->fetchColumn();

        // 3. Pending Leave Requests
        $pendingLeave = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();

        // 4. Monthly Payroll Amount
        $monthlyPayroll = $db->query("
            SELECT SUM(net_salary) FROM payroll_items pi
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pp.month = " . date('n') . " AND pp.year = " . date('Y')
        )->fetchColumn() ?: 0;

        $trendStmt = $db->prepare("
            SELECT date, status, COUNT(*) AS total
            FROM attendance
            WHERE date >= :month_start AND date <= :today
            GROUP BY date, status
            ORDER BY date ASC
        ");
        $trendStmt->execute([
            'month_start' => date('Y-m-01'),
            'today' => $today
        ]);
        $attendanceTrend = [];
        foreach ($trendStmt->fetchAll() as $row) {
            $dateKey = $row['date'];
            if (!isset($attendanceTrend[$dateKey])) {
                $attendanceTrend[$dateKey] = ['Present' => 0, 'Late' => 0, 'Absent' => 0];
            }
            if (array_key_exists($row['status'], $attendanceTrend[$dateKey])) {
                $attendanceTrend[$dateKey][$row['status']] = (int)$row['total'];
            }
        }

        // 5. Quick Pending Leave List
        $stmtLeave = $db->prepare("
            SELECT lr.*, e.first_name, e.last_name, lt.name as leave_type_name 
            FROM leave_requests lr
            JOIN employees e ON lr.employee_id = e.id
            JOIN leave_types lt ON lr.leave_type_id = lt.id
            WHERE lr.status = 'Pending'
            ORDER BY lr.created_at DESC LIMIT 5
        ");
        $stmtLeave->execute();
        $pendingLeaveList = $stmtLeave->fetchAll();

        // 6. Recent Audit Activities
        $stmtAudit = $db->prepare("
            SELECT a.*, u.username 
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC LIMIT 6
        ");
        $stmtAudit->execute();
        $recentActivities = $stmtAudit->fetchAll();

        view('dashboard.index', [
            'title'            => 'Dashboard — DonTech PeopleSuite',
            'totalEmployees'   => $totalEmp,
            'presentToday'     => $presentToday,
            'pendingLeave'     => $pendingLeave,
            'monthlyPayroll'   => $monthlyPayroll,
            'attendanceTrend'  => $attendanceTrend,
            'pendingLeaveList' => $pendingLeaveList,
            'recentActivities' => $recentActivities
        ]);
    }
}
