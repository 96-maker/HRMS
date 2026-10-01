<?php
// app/controllers/AttendanceController.php

class AttendanceController {
    public function index(): void {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        if (!AuthService::hasPermission('attendance.view') && !AuthService::hasPermission('attendance.clock')) {
            RoleMiddleware::hasPermission('attendance.view');
        }
        $db = Database::getInstance();

        $today = date('Y-m-d');
        $search = sanitize_string($_GET['search'] ?? '');
        $statusFilter = sanitize_string($_GET['status'] ?? '');

        $where = ["a.date = '{$today}'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(e.first_name LIKE :s OR e.last_name LIKE :s)";
            $params['s'] = "%{$search}%";
        }
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $where[] = "a.status = :st";
            $params['st'] = $statusFilter;
        }

        // Check current user employee record for Clock-In Widget and scoping
        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        // If logged in as regular Employee (role_slug = 'employee'), restrict attendance log strictly to self
        if (AuthService::hasRole('employee')) {
            $where = ["a.employee_id = :my_eid"];
            $params = ['my_eid' => $myEmpId];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT a.*, e.first_name, e.last_name, e.employee_code, d.name as department_name 
            FROM attendance a
            JOIN employees e ON a.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            WHERE {$whereClause}
            ORDER BY a.clock_in DESC
        ");
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        // Fetch Active Unfinished Shift (supports Day and Night shifts across midnight)
        $myRecordStmt = $db->prepare("
            SELECT * FROM attendance 
            WHERE employee_id = :eid 
              AND (date = '{$today}' OR (clock_in IS NOT NULL AND clock_out IS NULL))
            ORDER BY id DESC LIMIT 1
        ");
        $myRecordStmt->execute(['eid' => $myEmpId]);
        $myRecord = $myRecordStmt->fetch();

        // Fetch Geofence configuration for client-side geolocation validation
        $geoSettings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key LIKE 'geofence_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $devBypass = $this->isDevelopmentGeofenceBypassEnabled();

        $oLat = (float)($geoSettings['geofence_latitude'] ?? -6.823512);
        $oLon = (float)($geoSettings['geofence_longitude'] ?? 39.269504);

        if (!empty($geoSettings['geofence_plus_code'])) {
            $decoded = decode_plus_code($geoSettings['geofence_plus_code']);
            if ($decoded) {
                $oLat = $decoded['lat'];
                $oLon = $decoded['lon'];
            }
        }

        view('attendance.index', [
            'title'        => 'Time & Attendance — DonTech PeopleSuite',
            'records'      => $records,
            'myRecord'     => $myRecord,
            'myEmpId'      => $myEmpId,
            'search'       => $search,
            'statusFilter' => $statusFilter,
            'geofence'     => [
                'enabled'    => ($geoSettings['geofence_enabled'] ?? '1') === '1',
                'bypass_dev' => $devBypass,
                'latitude'   => $oLat,
                'longitude'  => $oLon,
                'radius'     => (float)($geoSettings['geofence_radius'] ?? 100)
            ]
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula (in meters)
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isDevelopmentGeofenceBypassEnabled(): bool {
        $environment = strtolower((string)process_env('APP_ENV', 'production'));
        $isDevelopment = in_array($environment, ['development', 'dev', 'local'], true);

        return $isDevelopment && filter_var(process_env('APP_GEOFENCE_BYPASS', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    private function validateGeofence($db): ?string {
        $settings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key LIKE 'geofence_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $enabled = ($settings['geofence_enabled'] ?? '1') === '1';
        $bypassDev = $this->isDevelopmentGeofenceBypassEnabled();

        if (!$enabled || $bypassDev) {
            return null; // Geofencing disabled or bypassed
        }

        $userLat = isset($_POST['user_latitude']) && $_POST['user_latitude'] !== '' ? (float)$_POST['user_latitude'] : null;
        $userLon = isset($_POST['user_longitude']) && $_POST['user_longitude'] !== '' ? (float)$_POST['user_longitude'] : null;

        if ($userLat === null || $userLon === null) {
            return "Ruhusa ya eneo (GPS/Location) inahitajika ili kupiga mahudhurio.";
        }

        $officeLat = (float)($settings['geofence_latitude'] ?? -6.823512);
        $officeLon = (float)($settings['geofence_longitude'] ?? 39.269504);

        // If Plus Code is set, dynamically resolve if lat/lon not set
        if (!empty($settings['geofence_plus_code'])) {
            $coords = decode_plus_code($settings['geofence_plus_code']);
            if ($coords) {
                $officeLat = $coords['lat'];
                $officeLon = $coords['lon'];
            }
        }

        $allowedRadius = (float)($settings['geofence_radius'] ?? 100);

        $distance = $this->calculateHaversineDistance($userLat, $userLon, $officeLat, $officeLon);

        if ($distance > $allowedRadius) {
            $formattedDist = round($distance);
            return "Uko mita {$formattedDist} kutoka ofisini. Huwezi kupiga mahudhurio ukiwa nje ya eneo la kazi (Max: {$allowedRadius}m).";
        }

        return null; // Within allowed boundary
    }

    public function override(): void {
        RoleMiddleware::hasPermission('attendance.override');
        $db = Database::getInstance();

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $clockIn = $_POST['clock_in'] ?? null;
        $clockOut = $_POST['clock_out'] ?? null;
        $status = sanitize_string($_POST['status'] ?? 'Present');
        $notes = sanitize_string($_POST['notes'] ?? 'Manual attendance override');
        $allowedStatuses = ['Present', 'Late', 'Absent', 'Half Day', 'On Leave'];

        $dateValue = DateTime::createFromFormat('!Y-m-d', $date);
        $validDate = $dateValue && $dateValue->format('Y-m-d') === $date;
        $validClockIn = $clockIn === null || $clockIn === '' || preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $clockIn);
        $validClockOut = $clockOut === null || $clockOut === '' || preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $clockOut);

        if (!$employeeId || !$validDate || !$validClockIn || !$validClockOut || !in_array($status, $allowedStatuses, true)) {
            redirect('/attendance', 'Invalid attendance override details.', 'danger');
            return;
        }

        $clockIn = $clockIn ? (strlen($clockIn) === 5 ? $clockIn . ':00' : $clockIn) : null;
        $clockOut = $clockOut ? (strlen($clockOut) === 5 ? $clockOut . ':00' : $clockOut) : null;
        $totalHours = 0.00;

        if ($clockIn && $clockOut) {
            $in = DateTime::createFromFormat('Y-m-d H:i:s', "{$date} {$clockIn}");
            $out = DateTime::createFromFormat('Y-m-d H:i:s', "{$date} {$clockOut}");
            if ($out < $in) {
                $out->modify('+1 day');
            }
            $totalHours = round(max(0, ($out->getTimestamp() - $in->getTimestamp()) / 3600), 2);
        }

        $stmt = $db->prepare("
            INSERT INTO attendance (employee_id, date, clock_in, clock_out, total_hours, status, notes)
            VALUES (:eid, :date_value, :clock_in, :clock_out, :hours, :status, :notes)
            ON DUPLICATE KEY UPDATE
                clock_in = :clock_in_update,
                clock_out = :clock_out_update,
                total_hours = :hours_update,
                status = :status_update,
                notes = :notes_update
        ");
        $stmt->execute([
            'eid' => $employeeId,
            'date_value' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'hours' => $totalHours,
            'status' => $status,
            'notes' => $notes,
            'clock_in_update' => $clockIn,
            'clock_out_update' => $clockOut,
            'hours_update' => $totalHours,
            'status_update' => $status,
            'notes_update' => $notes
        ]);

        AuditLogger::log('attendance_override', 'Attendance', (string)$employeeId, [
            'date' => $date,
            'status' => $status,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut
        ]);
        redirect('/attendance', 'Attendance record overridden successfully.', 'success');
    }

    public function clockIn(): void {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        RoleMiddleware::hasPermission('attendance.clock');
        $db = Database::getInstance();

        // Server-Side Geofence Validation
        $geoError = $this->validateGeofence($db);
        if ($geoError !== null) {
            redirect('/attendance', $geoError, 'error');
            return;
        }

        $user = AuthService::user();
        $userId = $user['id'] ?? ($_SESSION['user_id'] ?? null);

        // 1. Look up the employee linked to this user account
        $empStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :user_id LIMIT 1");
        $empStmt->execute(['user_id' => $userId]);
        $empRow = $empStmt->fetch();

        // 2. Fallback: if no direct user_id link, check explicit POST or session employee_id
        $empId = (int)($empRow['id'] ?? 0);

        // 3. Prevent foreign key crash if no employee record exists for this user
        if (!$empId) {
            redirect('/attendance', 'Attendance clock-in failed: No employee profile is linked to your user account. Please link an employee profile first.', 'error');
            return;
        }

        $today = date('Y-m-d');
        $nowTime = date('H:i:s');

        $notes = 'Geofenced Mobile Clock-In';

        // Check late threshold (08:30:00)
        $status = (strtotime($nowTime) > strtotime('08:30:00')) ? 'Late' : 'Present';

        try {
            $stmt = $db->prepare("
                INSERT INTO attendance (employee_id, date, clock_in, status, notes)
                VALUES (:eid, :d, :cin, :st, :notes)
                ON DUPLICATE KEY UPDATE clock_in = :cin2, status = :st2, notes = :notes2
            ");
            $stmt->execute([
                'eid'    => $empId,
                'd'      => $today,
                'cin'    => $nowTime,
                'st'     => $status,
                'notes'  => $notes,
                'cin2'   => $nowTime,
                'st2'    => $status,
                'notes2' => $notes
            ]);

            AuditLogger::log('clock_in', 'Attendance', (string)$empId);
            redirect('/attendance', "Clocked in successfully at {$nowTime} within office boundary!", 'success');
        } catch (PDOException $e) {
            error_log("Attendance Clock-In Exception: " . $e->getMessage());
            redirect('/attendance', 'Attendance clock-in failed due to a database error. Please ensure your employee account is properly configured.', 'error');
        }
    }

    public function clockOut(): void {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        RoleMiddleware::hasPermission('attendance.clock');
        $db = Database::getInstance();

        // Server-Side Geofence Validation
        $geoError = $this->validateGeofence($db);
        if ($geoError !== null) {
            redirect('/attendance', $geoError, 'error');
            return;
        }

        $user = AuthService::user();
        $userId = $user['id'] ?? ($_SESSION['user_id'] ?? null);

        // Look up linked employee
        $empStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :user_id LIMIT 1");
        $empStmt->execute(['user_id' => $userId]);
        $empRow = $empStmt->fetch();

        $empId = (int)($empRow['id'] ?? 0);

        if (!$empId) {
            redirect('/attendance', 'Attendance clock-out failed: No employee profile is linked to your user account.', 'error');
            return;
        }

        $nowDateTime = date('Y-m-d H:i:s');
        $nowTime = date('H:i:s');

        try {
            // Fetch active unclosed record (handles overnight shift across midnight)
            $stmtIn = $db->prepare("
                SELECT id, date, clock_in, notes 
                FROM attendance 
                WHERE employee_id = :eid AND clock_in IS NOT NULL AND clock_out IS NULL 
                ORDER BY id DESC LIMIT 1
            ");
            $stmtIn->execute(['eid' => $empId]);
            $activeShift = $stmtIn->fetch();

            $hours = 8.00;
            $recordId = 0;

            if ($activeShift) {
                $recordId = (int)$activeShift['id'];
                $clockInDateTime = $activeShift['date'] . ' ' . $activeShift['clock_in'];
                $diffSeconds = strtotime($nowDateTime) - strtotime($clockInDateTime);
                $diffMins = max(0, $diffSeconds / 60);
                
                // Calculate total hours worked (subtract 1 hour lunch if shift > 5 hours)
                $rawHours = $diffMins / 60;
                $hours = ($rawHours > 5) ? round($rawHours - 1, 2) : round($rawHours, 2);

                $notes = $activeShift['notes'] ?? '';

                $stmt = $db->prepare("
                    UPDATE attendance 
                    SET clock_out = :cout, total_hours = :h, notes = :notes 
                    WHERE id = :id
                ");
                $stmt->execute(['cout' => $nowTime, 'h' => $hours, 'notes' => $notes, 'id' => $recordId]);
            }

            AuditLogger::log('clock_out', 'Attendance', (string)$empId);
            redirect('/attendance', "Clocked out successfully at {$nowTime} ({$hours} hrs worked)!", 'success');
        } catch (PDOException $e) {
            error_log("Attendance Clock-Out Exception: " . $e->getMessage());
            redirect('/attendance', 'Attendance clock-out failed due to a database error.', 'error');
        }
    }
}
