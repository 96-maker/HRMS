<?php
// app/controllers/PayrollController.php

class PayrollController {
    private function getExportablePeriod(PDO $db, int $month, int $year): array {
        $stmt = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
        $stmt->execute(['m' => $month, 'y' => $year]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$period || !in_array($period['status'] ?? '', ['Approved', 'Processed', 'Closed'], true)) {
            redirect('/payroll', 'Payroll export is available only for approved or locked payroll periods.', 'warning');
        }

        return $period;
    }

    public function index(): void {
        RoleMiddleware::hasPermission('payroll.view');
        if (AuthService::hasRole('employee')) {
            redirect('/payslips');
            return;
        }
        $db = Database::getInstance();

        $selectedMonth = (int)($_GET['month'] ?? date('n'));
        $selectedYear  = (int)($_GET['year'] ?? date('Y'));
        $selectedCycle = trim($_GET['cycle'] ?? 'all');

        // Fetch or create payroll period for selected Month and Year
        $stmtPeriod = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
        $stmtPeriod->execute(['m' => $selectedMonth, 'y' => $selectedYear]);
        $period = $stmtPeriod->fetch();

        if (!$period) {
            $periodName = date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) . ' Payroll';
            $startDate  = date('Y-m-01', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
            $endDate    = date('Y-m-t', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));

            $insP = $db->prepare("INSERT INTO payroll_periods (name, month, year, start_date, end_date, status) VALUES (:n, :m, :y, :sd, :ed, 'Draft')");
            $insP->execute([
                'n' => $periodName, 'm' => $selectedMonth, 'y' => $selectedYear,
                'sd' => $startDate, 'ed' => $endDate
            ]);
            $periodId = $db->lastInsertId();
            $period = ['id' => $periodId, 'name' => $periodName, 'status' => 'Draft', 'month' => $selectedMonth, 'year' => $selectedYear];
        }

        // Fetch all available periods for period selector dropdown
        $allPeriods = $db->query("SELECT * FROM payroll_periods ORDER BY year DESC, month DESC")->fetchAll();

        // Fetch items
        $stmtItems = $db->prepare("
            SELECT pi.id, pi.id as item_id, pi.payroll_period_id, pi.employee_id, pi.basic_salary, pi.allowances, pi.overtime, pi.gross_salary, pi.tax_deduction, pi.statutory_deduction, pi.other_deductions, pi.net_salary, pi.payment_status, pi.notes,
                   e.first_name, e.last_name, e.employee_code, e.pay_cycle, e.employment_type, p.title as position_title
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            LEFT JOIN positions p ON e.position_id = p.id
            WHERE pi.payroll_period_id = :pid
            ORDER BY pi.id DESC
        ");
        $stmtItems->execute(['pid' => $period['id']]);
        $allItems = $stmtItems->fetchAll();

        // Filter items by Pay Frequency if selected
        if ($selectedCycle !== 'all') {
            $items = array_values(array_filter($allItems, function($item) use ($selectedCycle) {
                return strtolower($item['pay_cycle'] ?? 'monthly') === strtolower($selectedCycle);
            }));
        } else {
            $items = $allItems;
        }

        // Available employees filtering based on frequency limits:
        // Monthly: Max 1 per month
        // Weekly: Max 4 per month
        // Daily: Max 30 per month
        // Any Day / On-Demand: Unlimited
        $stmtCounts = $db->prepare("SELECT employee_id, COUNT(*) as cnt FROM payroll_items WHERE payroll_period_id = :pid GROUP BY employee_id");
        $stmtCounts->execute(['pid' => $period['id']]);
        $empCounts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);

        $allEmps = $db->query("SELECT * FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL ORDER BY first_name ASC")->fetchAll();
        $availableEmployees = [];

        foreach ($allEmps as $e) {
            $cycle = strtolower($e['pay_cycle'] ?? 'monthly');
            if ($selectedCycle !== 'all' && $cycle !== strtolower($selectedCycle)) {
                continue;
            }

            $count = (int)($empCounts[$e['id']] ?? 0);
            if ($cycle === 'monthly' && $count >= 1) {
                continue;
            } elseif ($cycle === 'weekly' && $count >= 4) {
                continue;
            } elseif ($cycle === 'daily' && $count >= 30) {
                continue;
            }
            $availableEmployees[] = $e;
        }

        view('payroll.index', [
            'title'              => 'Payroll Administration — DonTech PeopleSuite',
            'period'             => $period,
            'items'              => $items,
            'allItems'           => $allItems,
            'availableEmployees' => $availableEmployees,
            'allPeriods'         => $allPeriods,
            'selectedMonth'      => $selectedMonth,
            'selectedYear'       => $selectedYear,
            'selectedCycle'      => $selectedCycle
        ]);
    }

    public function populateAll(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();
        $periodId = (int)($_POST['period_id'] ?? 0);
        $month    = (int)($_POST['month'] ?? date('n'));
        $year     = (int)($_POST['year'] ?? date('Y'));
        $cycleParam = trim($_POST['cycle'] ?? 'all');

        if ($periodId) {
            // Check if period is locked
            $periodStmt = $db->prepare("SELECT status FROM payroll_periods WHERE id = :pid");
            $periodStmt->execute(['pid' => $periodId]);
            $pStatus = $periodStmt->fetchColumn();

            if ($pStatus === 'Processed') {
                redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Hauwezi kujaza au kubadilisha data kwenye Payroll iliyofungwa (Locked)!', 'danger');
                return;
            }

            // Fetch existing items, including manual override status
            $stmtCounts = $db->prepare("SELECT employee_id, is_manually_adjusted, COUNT(*) as cnt FROM payroll_items WHERE payroll_period_id = :pid GROUP BY employee_id, is_manually_adjusted");
            $stmtCounts->execute(['pid' => $periodId]);
            $existingRows = $stmtCounts->fetchAll();

            $empCounts = [];
            $manualEmpIds = [];
            foreach ($existingRows as $row) {
                $empId = (int)$row['employee_id'];
                $empCounts[$empId] = ($empCounts[$empId] ?? 0) + (int)$row['cnt'];
                if ((int)$row['is_manually_adjusted'] === 1) {
                    $manualEmpIds[$empId] = true;
                }
            }

            $allEmps = $db->query("SELECT * FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL")->fetchAll();
            $missingEmps = [];

            foreach ($allEmps as $e) {
                $empId = (int)$e['id'];

                // STRICT PROTECTION: If employee record has manual overrides in this period, NEVER overwrite or alter
                if (!empty($manualEmpIds[$empId])) {
                    continue;
                }

                $c = strtolower($e['pay_cycle'] ?? 'monthly');
                if ($cycleParam !== 'all' && $c !== strtolower($cycleParam)) {
                    continue;
                }

                $count = (int)($empCounts[$empId] ?? 0);
                if ($c === 'monthly' && $count >= 1) {
                    continue;
                } elseif ($c === 'weekly' && $count >= 4) {
                    continue;
                } elseif ($c === 'daily' && $count >= 30) {
                    continue;
                }
                $missingEmps[] = $e;
            }

            if (!empty($missingEmps)) {
                $ins = $db->prepare("
                    INSERT INTO payroll_items 
                    (payroll_period_id, employee_id, basic_salary, allowances, overtime, gross_salary, tax_deduction, statutory_deduction, other_deductions, net_salary, employer_nssf, employer_wcf, employer_sdl, payment_status, is_manually_adjusted)
                    VALUES
                    (:pid, :eid, :bs, :alw, 0.00, :gross, :paye, :nssf, 0.00, :net, :emp_nssf, :wcf, :sdl, 'Unpaid', 0)
                ");

                foreach ($missingEmps as $emp) {
                    $basicSalary = round($emp['basic_salary'], 2);
                    $allowances  = round($emp['allowances'], 2);

                    $calc = PayrollCalculationService::calculateFullPayroll($basicSalary, $allowances, 0.00, 0.00);

                    $ins->execute([
                        'pid'      => $periodId, 
                        'eid'      => $emp['id'], 
                        'bs'       => $calc['basic_salary'],
                        'alw'      => $calc['allowances'], 
                        'gross'    => $calc['gross_salary'], 
                        'paye'     => $calc['paye'],
                        'nssf'     => $calc['employee_nssf'], 
                        'net'      => $calc['net_salary'],
                        'emp_nssf' => $calc['employer_nssf'],
                        'wcf'      => $calc['employer_wcf'],
                        'sdl'      => $calc['employer_sdl']
                    ]);
                }
            }
        }

        redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'All eligible active employees populated into payroll successfully.', 'success');
    }

    public function createItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $selectedMonth = (int)($_GET['month'] ?? date('n'));
        $selectedYear  = (int)($_GET['year'] ?? date('Y'));
        $selectedCycle = trim($_GET['cycle'] ?? 'all');

        $stmtPeriod = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
        $stmtPeriod->execute(['m' => $selectedMonth, 'y' => $selectedYear]);
        $period = $stmtPeriod->fetch();

        if (!$period) {
            redirect('/payroll', 'Payroll period not found.', 'danger');
        }

        if (($period['status'] ?? '') === 'Processed') {
            redirect("/payroll?month={$selectedMonth}&year={$selectedYear}", 'Hauwezi kuongeza mfanyakazi kwenye Payroll iliyofungwa (Locked)!', 'danger');
            return;
        }

        $stmtCounts = $db->prepare("SELECT employee_id, COUNT(*) as cnt FROM payroll_items WHERE payroll_period_id = :pid GROUP BY employee_id");
        $stmtCounts->execute(['pid' => $period['id']]);
        $empCounts = $stmtCounts->fetchAll(PDO::FETCH_KEY_PAIR);

        $allEmps = $db->query("SELECT * FROM employees WHERE status != 'Terminated' AND deleted_at IS NULL ORDER BY first_name ASC")->fetchAll();
        $availableEmployees = [];

        foreach ($allEmps as $e) {
            $c = strtolower($e['pay_cycle'] ?? 'monthly');
            if ($selectedCycle !== 'all' && $c !== strtolower($selectedCycle)) {
                continue;
            }

            $count = (int)($empCounts[$e['id']] ?? 0);
            if ($c === 'monthly' && $count >= 1) {
                continue;
            } elseif ($c === 'weekly' && $count >= 4) {
                continue;
            } elseif ($c === 'daily' && $count >= 30) {
                continue;
            }
            $availableEmployees[] = $e;
        }

        view('payroll.add_item', [
            'title'              => 'Add Employee to Payroll — DonTech PeopleSuite',
            'period'             => $period,
            'availableEmployees' => $availableEmployees,
            'selectedMonth'      => $selectedMonth,
            'selectedYear'       => $selectedYear,
            'selectedCycle'      => $selectedCycle
        ]);
    }

    public function editItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id && isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
            $id = (int)($queryParams['id'] ?? 0);
        }

        $selectedMonth = (int)($_GET['month'] ?? date('n'));
        $selectedYear  = (int)($_GET['year'] ?? date('Y'));
        $selectedCycle = trim($_GET['cycle'] ?? 'all');

        if ($id <= 0) {
            redirect('/payroll', 'Invalid payroll item ID.', 'danger');
            return;
        }

        $stmt = $db->prepare("
            SELECT 
                pi.id AS item_id,
                pi.payroll_period_id,
                pi.employee_id,
                pi.basic_salary,
                pi.allowances,
                pi.overtime,
                pi.gross_salary,
                pi.tax_deduction,
                pi.statutory_deduction,
                pi.other_deductions,
                pi.net_salary,
                pi.employer_nssf,
                pi.employer_wcf,
                pi.employer_sdl,
                pi.is_manually_adjusted,
                pi.adjustment_reason,
                pi.notes,
                e.first_name, 
                e.middle_name, 
                e.last_name, 
                e.employee_code, 
                COALESCE(e.pay_cycle, 'Monthly') AS pay_frequency,
                d.name AS department_name,
                p.title AS position_title,
                pp.status AS period_status
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            LEFT JOIN positions p ON e.position_id = p.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pi.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/payroll', 'Payroll record not found in database.', 'danger');
            return;
        }

        if (($item['period_status'] ?? '') === 'Processed') {
            redirect("/payroll?month={$selectedMonth}&year={$selectedYear}", 'Hauwezi kufanya marekebisho kwenye Payroll iliyofungwa (Locked)! Tafadhali fungua (Unlock for Editing) kwanza.', 'danger');
            return;
        }

        view('payroll.edit_item', [
            'title'         => "Manual Payroll Override — DonTech PeopleSuite",
            'item'          => $item,
            'selectedMonth' => $selectedMonth,
            'selectedYear'  => $selectedYear,
            'selectedCycle' => $selectedCycle
        ]);
    }

    public function addItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $periodId = (int)($_POST['period_id'] ?? 0);
        $empId    = (int)($_POST['employee_id'] ?? 0);
        $month    = (int)($_POST['month'] ?? date('n'));
        $year     = (int)($_POST['year'] ?? date('Y'));
        $cycleParam = trim($_POST['cycle'] ?? 'all');

        if ($periodId && $empId) {
            $periodStmt = $db->prepare("SELECT status FROM payroll_periods WHERE id = :pid");
            $periodStmt->execute(['pid' => $periodId]);
            $pStatus = $periodStmt->fetchColumn();

            if ($pStatus === 'Processed') {
                redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Hauwezi kuongeza mfanyakazi kwenye Payroll iliyofungwa (Locked)!', 'danger');
                return;
            }

            $emp = $db->query("SELECT * FROM employees WHERE id = {$empId}")->fetch();
            if ($emp) {
                $cycle = strtolower($emp['pay_cycle'] ?? 'monthly');
                
                // Count existing entries for this employee in this period
                $cntStmt = $db->prepare("SELECT COUNT(*) FROM payroll_items WHERE payroll_period_id = :pid AND employee_id = :eid");
                $cntStmt->execute(['pid' => $periodId, 'eid' => $empId]);
                $count = (int)$cntStmt->fetchColumn();

                if ($cycle === 'monthly' && $count >= 1) {
                    redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), "Mfanyakazi wa Monthly anaingizwa mara 1 tu kwa mwezi!", 'warning');
                    return;
                } elseif ($cycle === 'weekly' && $count >= 4) {
                    redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), "Mfanyakazi wa Weekly ana kikomo cha kuingizwa mara 4 kwa mwezi!", 'warning');
                    return;
                } elseif ($cycle === 'daily' && $count >= 30) {
                    redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), "Mfanyakazi wa Daily ana kikomo cha siku 30 kwa mwezi!", 'warning');
                    return;
                }

                $basicSalary = round($emp['basic_salary'], 2);
                $allowances  = round($emp['allowances'], 2);

                $calc = PayrollCalculationService::calculateFullPayroll($basicSalary, $allowances, 0.00, 0.00);

                $ins = $db->prepare("
                    INSERT INTO payroll_items 
                    (payroll_period_id, employee_id, basic_salary, allowances, overtime, gross_salary, tax_deduction, statutory_deduction, other_deductions, net_salary, employer_nssf, employer_wcf, employer_sdl, payment_status, is_manually_adjusted)
                    VALUES
                    (:pid, :eid, :bs, :alw, 0.00, :gross, :paye, :nssf, 0.00, :net, :emp_nssf, :wcf, :sdl, 'Unpaid', 0)
                ");
                $ins->execute([
                    'pid'      => $periodId, 
                    'eid'      => $empId, 
                    'bs'       => $calc['basic_salary'],
                    'alw'      => $calc['allowances'], 
                    'gross'    => $calc['gross_salary'], 
                    'paye'     => $calc['paye'],
                    'nssf'     => $calc['employee_nssf'], 
                    'net'      => $calc['net_salary'],
                    'emp_nssf' => $calc['employer_nssf'],
                    'wcf'      => $calc['employer_wcf'],
                    'sdl'      => $calc['employer_sdl']
                ]);

                AuditLogger::log('add_payroll_item', 'Payroll', (string)$periodId, ['employee' => "{$emp['first_name']} {$emp['last_name']}"]);
                redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), "Employee {$emp['first_name']} {$emp['last_name']} added to payroll!", 'success');
            }
        }
        redirect('/payroll', 'Failed to add employee to payroll.', 'danger');
    }

    public function updateItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();
        $user = AuthService::user();
        $userId = $user['id'] ?? null;
        
        $itemId     = (int)($_POST['item_id'] ?? 0);
        $month      = (int)($_POST['month'] ?? date('n'));
        $year       = (int)($_POST['year'] ?? date('Y'));
        $cycleParam = trim($_POST['cycle'] ?? 'all');

        if ($itemId <= 0) {
            redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Invalid payroll item ID.', 'danger');
            return;
        }

        // Check if the associated payroll period is already processed/locked
        $stmtCheck = $db->prepare("
            SELECT pp.status 
            FROM payroll_items pi 
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id 
            WHERE pi.id = :id
        ");
        $stmtCheck->execute(['id' => $itemId]);
        $status = $stmtCheck->fetchColumn();

        if ($status === 'Processed') {
            redirect("/payroll?month={$month}&year={$year}", 'Hauwezi kufanya marekebisho kwenye Payroll iliyofungwa (Locked)! Tafadhali fungua (Unlock for Editing) kwanza.', 'danger');
            return;
        }

        $basic           = (float)($_POST['basic_salary'] ?? 0);
        $allowances      = (float)($_POST['allowances'] ?? 0);
        $overtime        = (float)($_POST['overtime'] ?? 0);
        $nssf            = (float)($_POST['statutory_deduction'] ?? 0);
        $paye            = (float)($_POST['tax_deduction'] ?? 0);
        $otherDeductions = (float)($_POST['other_deductions'] ?? 0);
        $notes           = sanitize_string($_POST['notes'] ?? '');
        $adjustReason    = sanitize_string($_POST['adjustment_reason'] ?? 'Manual Payroll Override');

        $gross = $basic + $allowances + $overtime;
        
        // Recalculate employer liabilities based on adjusted gross
        $empStat = PayrollCalculationService::calculateStatutoryEmployer($gross);
        $empNssf = round($gross * 0.10, 2);

        $net = max(0, round($gross - ($paye + $nssf + $otherDeductions), 2));

        $stmt = $db->prepare("
            UPDATE payroll_items SET 
                basic_salary = :bs, allowances = :alw, overtime = :ot, 
                gross_salary = :gross, statutory_deduction = :nssf, tax_deduction = :paye, 
                other_deductions = :other, net_salary = :net, employer_nssf = :emp_nssf,
                employer_wcf = :wcf, employer_sdl = :sdl,
                is_manually_adjusted = 1, adjusted_by = :ab, adjusted_at = NOW(), adjustment_reason = :ar,
                notes = :notes 
            WHERE id = :id
        ");
        $stmt->execute([
            'bs'       => $basic, 
            'alw'      => $allowances, 
            'ot'       => $overtime,
            'gross'    => $gross, 
            'nssf'     => $nssf, 
            'paye'     => $paye,
            'other'    => $otherDeductions, 
            'net'      => $net, 
            'emp_nssf' => $empNssf,
            'wcf'      => $empStat['wcf'],
            'sdl'      => $empStat['sdl'],
            'ab'       => $userId,
            'ar'       => $adjustReason,
            'notes'    => $notes, 
            'id'       => $itemId
        ]);

        AuditLogger::log('manual_payroll_adjust', 'Payroll', (string)$itemId, ['reason' => $adjustReason, 'gross' => $gross, 'net' => $net]);
        redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Manual payroll adjustments saved successfully with Manual Override Protection!', 'success');
    }

    public function resetItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $itemId     = (int)($_POST['item_id'] ?? 0);
        $month      = (int)($_POST['month'] ?? date('n'));
        $year       = (int)($_POST['year'] ?? date('Y'));
        $cycleParam = trim($_POST['cycle'] ?? 'all');

        if ($itemId <= 0) {
            redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Invalid payroll item ID.', 'danger');
            return;
        }

        // Check if period is locked
        $stmtCheck = $db->prepare("
            SELECT pp.status, pi.employee_id 
            FROM payroll_items pi 
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id 
            WHERE pi.id = :id
        ");
        $stmtCheck->execute(['id' => $itemId]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Payroll item not found.', 'danger');
            return;
        }

        if (($row['status'] ?? '') === 'Processed') {
            redirect("/payroll?month={$month}&year={$year}", 'Hauwezi kurudisha mahesabu kwenye Payroll iliyofungwa (Locked)!', 'danger');
            return;
        }

        $empId = (int)$row['employee_id'];
        $emp = $db->query("SELECT basic_salary, allowances FROM employees WHERE id = {$empId}")->fetch(PDO::FETCH_ASSOC);

        if ($emp) {
            $basicSalary = round($emp['basic_salary'], 2);
            $allowances  = round($emp['allowances'], 2);

            $calc = PayrollCalculationService::calculateFullPayroll($basicSalary, $allowances, 0.00, 0.00);

            $stmt = $db->prepare("
                UPDATE payroll_items SET 
                    basic_salary = :bs, allowances = :alw, overtime = 0.00, 
                    gross_salary = :gross, statutory_deduction = :nssf, tax_deduction = :paye, 
                    other_deductions = 0.00, net_salary = :net, employer_nssf = :emp_nssf,
                    employer_wcf = :wcf, employer_sdl = :sdl,
                    is_manually_adjusted = 0, adjusted_by = NULL, adjusted_at = NULL, adjustment_reason = NULL,
                    notes = NULL 
                WHERE id = :id
            ");
            $stmt->execute([
                'bs'       => $calc['basic_salary'],
                'alw'      => $calc['allowances'],
                'gross'    => $calc['gross_salary'],
                'nssf'     => $calc['employee_nssf'],
                'paye'     => $calc['paye'],
                'net'      => $calc['net_salary'],
                'emp_nssf' => $calc['employer_nssf'],
                'wcf'      => $calc['employer_wcf'],
                'sdl'      => $calc['employer_sdl'],
                'id'       => $itemId
            ]);

            AuditLogger::log('reset_payroll_item', 'Payroll', (string)$itemId);
            redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Payroll record reset to automatic statutory calculations successfully.', 'info');
        } else {
            redirect("/payroll?month={$month}&year={$year}&cycle=" . urlencode($cycleParam), 'Linked employee record not found.', 'danger');
        }
    }

    public function removeItem(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();
        $itemId = (int)($_POST['item_id'] ?? 0);
        $month  = (int)($_POST['month'] ?? date('n'));
        $year   = (int)($_POST['year'] ?? date('Y'));

        if ($itemId) {
            // Check period lock state
            $stmtCheck = $db->prepare("
                SELECT pp.status 
                FROM payroll_items pi 
                JOIN payroll_periods pp ON pi.payroll_period_id = pp.id 
                WHERE pi.id = :id
            ");
            $stmtCheck->execute(['id' => $itemId]);
            $status = $stmtCheck->fetchColumn();

            if ($status === 'Processed') {
                redirect("/payroll?month={$month}&year={$year}", 'Hauwezi kufuta mfanyakazi kutoka kwenye Payroll iliyofungwa (Locked)!', 'danger');
                return;
            }

            $stmt = $db->prepare("DELETE FROM payroll_items WHERE id = :id");
            $stmt->execute(['id' => $itemId]);
            AuditLogger::log('remove_payroll_item', 'Payroll', (string)$itemId);
            redirect("/payroll?month={$month}&year={$year}", 'Employee removed from payroll batch.', 'info');
        }
        redirect("/payroll?month={$month}&year={$year}", 'Invalid request.', 'danger');
    }

    public function process(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();
        $user = AuthService::user();
        $userId = $user['id'] ?? null;
        $periodId = (int)($_POST['period_id'] ?? 0);
        $month  = (int)($_POST['month'] ?? date('n'));
        $year   = (int)($_POST['year'] ?? date('Y'));

        // Check if there are any employees/items in this period
        $checkItems = $db->prepare("SELECT COUNT(*) FROM payroll_items WHERE payroll_period_id = :pid");
        $checkItems->execute(['pid' => $periodId]);
        if ($checkItems->fetchColumn() == 0) {
            redirect("/payroll?month={$month}&year={$year}", 'Hauwezi ku-Lock payroll iliyo tupu! Tafadhali ongeza wafanyakazi kwanza (+ Add Employee to Payroll).', 'warning');
            return;
        }

        $periodStatusStmt = $db->prepare("SELECT status FROM payroll_periods WHERE id = :pid LIMIT 1");
        $periodStatusStmt->execute(['pid' => $periodId]);
        if (!in_array($periodStatusStmt->fetchColumn(), ['Draft', 'Processing'], true)) {
            redirect("/payroll?month={$month}&year={$year}", 'Only draft or processing payroll periods can be locked.', 'warning');
            return;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE payroll_periods SET status = 'Processed', processed_by = :uid, processed_at = NOW() WHERE id = :pid AND status IN ('Draft', 'Processing')");
            $stmt->execute(['uid' => $userId, 'pid' => $periodId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Payroll period state changed before processing.');
            }

            $db->commit();
            AuditLogger::log('process_payroll', 'Payroll', (string)$periodId);
            redirect("/payroll?month={$month}&year={$year}", 'Payroll period processed and locked successfully!', 'success');
        } catch (Exception $e) {
            $db->rollBack();
            redirect("/payroll?month={$month}&year={$year}", 'Failed to process payroll: ' . $e->getMessage(), 'danger');
        }
    }

    public function unlock(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();
        $periodId = (int)($_POST['period_id'] ?? 0);
        $month  = (int)($_POST['month'] ?? date('n'));
        $year   = (int)($_POST['year'] ?? date('Y'));

        $periodStatusStmt = $db->prepare("SELECT status FROM payroll_periods WHERE id = :pid LIMIT 1");
        $periodStatusStmt->execute(['pid' => $periodId]);
        if ($periodStatusStmt->fetchColumn() !== 'Processed') {
            redirect("/payroll?month={$month}&year={$year}", 'Only processed payroll periods can be unlocked.', 'warning');
            return;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE payroll_periods SET status = 'Draft', processed_by = NULL, processed_at = NULL WHERE id = :pid AND status = 'Processed'");
            $stmt->execute(['pid' => $periodId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Payroll period state changed before unlocking.');
            }

            $db->commit();
            AuditLogger::log('unlock_payroll', 'Payroll', (string)$periodId);
            redirect("/payroll?month={$month}&year={$year}", 'Payroll unlocked! You can now edit/adjust salaries again.', 'warning');
        } catch (Exception $e) {
            $db->rollBack();
            redirect("/payroll?month={$month}&year={$year}", 'Failed to unlock payroll: ' . $e->getMessage(), 'danger');
        }
    }



    public function show(): void {
        RoleMiddleware::hasPermission('payroll.view');
        $db = Database::getInstance();
        $id = (int)($_GET['id'] ?? 0);

        // Fetch current user employee ID
        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $stmt = $db->prepare("
            SELECT pi.*, e.first_name, e.last_name, e.employee_code, e.department_id, e.bank_name, e.bank_account_no, e.tin_number, e.nida_number, e.nssf_number, d.name as department_name, p.title as position_title, pp.name as period_name
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pi.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            redirect('/payroll', 'Payslip record not found.', 'danger');
        }

        // Restrict regular employee to viewing strictly their own payslip
        if (AuthService::hasRole('employee') && (int)$item['employee_id'] !== $myEmpId) {
            redirect('/payslips', 'Hauwezi kutazama Payslip ya mfanyakazi mwingine.', 'danger');
            return;
        }

        $company = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

        view('payroll.show', [
            'title'   => "Payslip - {$item['first_name']} {$item['last_name']} — DonTech PeopleSuite",
            'item'    => $item,
            'company' => $company
        ]);
    }

    public function payslips(): void {
        RoleMiddleware::hasPermission('payroll.view');
        $db = Database::getInstance();

        // Fetch current user employee ID
        $user = AuthService::user();
        $myEmpStmt = $db->prepare("SELECT id FROM employees WHERE user_id = :uid LIMIT 1");
        $myEmpStmt->execute(['uid' => $user['id']]);
        $myEmpId = (int)($myEmpStmt->fetchColumn() ?: 0);

        $where = [];
        $params = [];

        // If logged in as regular Employee (role_slug = 'employee'), restrict payslips strictly to self
        if (AuthService::hasRole('employee')) {
            $where[] = "pi.employee_id = :my_eid";
            $params['my_eid'] = $myEmpId;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $stmt = $db->prepare("
            SELECT pi.*, e.first_name, e.last_name, e.employee_code, pp.name as period_name, pp.month, pp.year
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            {$whereClause}
            ORDER BY pp.year DESC, pp.month DESC, e.first_name ASC
        ");
        $stmt->execute($params);
        $payslips = $stmt->fetchAll();

        view('payroll.payslips', [
            'title'    => 'Payslips Directory — DonTech PeopleSuite',
            'payslips' => $payslips
        ]);
    }

    public function exportExcel(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year'] ?? date('Y'));
        $period = $this->getExportablePeriod($db, $month, $year);
        AuditLogger::log('export_payroll_excel', 'Payroll', (string)$period['id']);

        $stmt = $db->prepare("
            SELECT pi.*, e.first_name, e.last_name, e.employee_code, e.pay_cycle, e.phone, d.name as department_name, p.title as position_title, pp.name as period_name
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pp.month = :m AND pp.year = :y
            ORDER BY e.first_name ASC
        ");
        $stmt->execute(['m' => $month, 'y' => $year]);
        $items = $stmt->fetchAll();

        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $companyName     = $companySettings['company_name'] ?? 'Company';

        $periodName = date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' Payroll';

        if (ob_get_level()) {
            ob_end_clean();
        }

        $filename = "payroll_summary_" . strtolower(date('F_Y', mktime(0,0,0,$month,1,$year))) . ".xls";

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta charset=\"UTF-8\"></head>";
        echo "<body>";
        echo "<h2>" . htmlspecialchars($companyName) . " — Payroll Summary Sheet</h2>";
        echo "<h3>Period: " . htmlspecialchars($periodName) . " | Generated: " . date('d M Y H:i:s') . "</h3>";
        echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; font-family:sans-serif; font-size:12px;\">";
        echo "<tr style=\"background-color:#4f46e5; color:#ffffff; font-weight:bold;\">
                <th>S/N</th>
                <th>Employee Code</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Pay Frequency</th>
                <th>Basic Salary (TZS)</th>
                <th>Allowances (TZS)</th>
                <th>Overtime (TZS)</th>
                <th>Gross Pay (TZS)</th>
                <th>NSSF / Statutory (TZS)</th>
                <th>PAYE Tax (TZS)</th>
                <th>Other Deductions (TZS)</th>
                <th>Net Take-Home (TZS)</th>
                <th>Status</th>
              </tr>";

        $totalBasic = 0; $totalAlw = 0; $totalOT = 0; $totalGross = 0; $totalNssf = 0; $totalPaye = 0; $totalOther = 0; $totalNet = 0;
        $i = 1;

        foreach ($items as $item) {
            $fullName = $item['first_name'] . ' ' . $item['last_name'];
            $totalBasic += $item['basic_salary'];
            $totalAlw   += $item['allowances'];
            $totalOT    += $item['overtime'];
            $totalGross += $item['gross_salary'];
            $totalNssf  += $item['statutory_deduction'];
            $totalPaye  += $item['tax_deduction'];
            $totalOther += $item['other_deductions'];
            $totalNet   += $item['net_salary'];

            echo "<tr>
                    <td align=\"center\">{$i}</td>
                    <td>" . htmlspecialchars($item['employee_code']) . "</td>
                    <td>" . htmlspecialchars($fullName) . "</td>
                    <td>" . htmlspecialchars($item['department_name'] ?? 'N/A') . "</td>
                    <td>" . htmlspecialchars($item['position_title'] ?? 'N/A') . "</td>
                    <td>" . htmlspecialchars($item['pay_cycle'] ?? 'Monthly') . "</td>
                    <td align=\"right\">" . number_format($item['basic_salary'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['allowances'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['overtime'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['gross_salary'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['statutory_deduction'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['tax_deduction'], 2) . "</td>
                    <td align=\"right\">" . number_format($item['other_deductions'], 2) . "</td>
                    <td align=\"right\" style=\"font-weight:bold; color:#059669;\">" . number_format($item['net_salary'], 2) . "</td>
                    <td align=\"center\">" . htmlspecialchars($item['payment_status']) . "</td>
                  </tr>";
            $i++;
        }

        echo "<tr style=\"background-color:#f1f5f9; font-weight:bold;\">
                <td colspan=\"6\" align=\"right\">TOTAL:</td>
                <td align=\"right\">" . number_format($totalBasic, 2) . "</td>
                <td align=\"right\">" . number_format($totalAlw, 2) . "</td>
                <td align=\"right\">" . number_format($totalOT, 2) . "</td>
                <td align=\"right\">" . number_format($totalGross, 2) . "</td>
                <td align=\"right\">" . number_format($totalNssf, 2) . "</td>
                <td align=\"right\">" . number_format($totalPaye, 2) . "</td>
                <td align=\"right\">" . number_format($totalOther, 2) . "</td>
                <td align=\"right\" style=\"color:#059669;\">" . number_format($totalNet, 2) . "</td>
                <td></td>
              </tr>";
        echo "</table>";
        echo "</body></html>";
        exit;
    }

    public function exportPdf(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year'] ?? date('Y'));
        $period = $this->getExportablePeriod($db, $month, $year);
        AuditLogger::log('export_payroll_pdf', 'Payroll', (string)$period['id']);

        $stmt = $db->prepare("
            SELECT pi.*, e.first_name, e.last_name, e.employee_code, e.pay_cycle, d.name as department_name, p.title as position_title, pp.name as period_name
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN positions p ON e.position_id = p.id
            JOIN payroll_periods pp ON pi.payroll_period_id = pp.id
            WHERE pp.month = :m AND pp.year = :y
            ORDER BY e.first_name ASC
        ");
        $stmt->execute(['m' => $month, 'y' => $year]);
        $items = $stmt->fetchAll();

        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $companyName     = $companySettings['company_name'] ?? 'Company';

        $periodName = date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' Payroll Summary';
        $generatedAt = date('d M Y H:i:s');
        $filename = "payroll_summary_" . strtolower(date('F_Y', mktime(0,0,0,$month,1,$year))) . ".pdf";

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title><?= htmlspecialchars($periodName) ?> — <?= htmlspecialchars($companyName) ?></title>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10px; color: #0f172a; margin: 0; padding: 20px; background: #f8fafc; }
                .loader-container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 200px; }
                .spinner { width: 36px; height: 36px; border: 4px solid #e2e8f0; border-top: 4px solid #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                #pdfContent { background: white; padding: 25px; border-radius: 8px; width: 95%; margin: 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 15px; }
                .title { font-size: 18px; font-weight: bold; color: #0f172a; }
                .subtitle { font-size: 10px; color: #64748b; margin-top: 2px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9px; }
                th { background-color: #4f46e5; color: white; padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
                td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
                .total-row td { font-weight: bold; background-color: #f1f5f9; border-top: 2px solid #cbd5e1; }
                .net-salary { color: #059669; font-weight: bold; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <div id="loader" class="loader-container">
                <div class="spinner"></div>
                <p style="margin-top: 12px; font-size: 12px; font-weight: 600; color: #4f46e5;">Inatengeneza Payroll PDF... Tafadhali Subiri</p>
            </div>

            <div id="pdfContent">
                <div class="header">
                    <div>
                        <div class="title"><?= htmlspecialchars($companyName) ?></div>
                        <div class="subtitle"><?= htmlspecialchars($periodName) ?> | Official Summary Sheet</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10px; font-weight: bold; color: #4f46e5;">PAYROLL REPORT</div>
                        <div style="font-size: 9px; color: #64748b;">Tarehe: <?= $generatedAt ?></div>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Position</th>
                            <th class="text-right">Basic Pay</th>
                            <th class="text-right">Gross Pay</th>
                            <th class="text-right">NSSF (10%)</th>
                            <th class="text-right">PAYE Tax</th>
                            <th class="text-right">Net Take-Home</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $tBasic = 0; $tGross = 0; $tNssf = 0; $tPaye = 0; $tNet = 0; $idx = 1;
                        foreach ($items as $item): 
                            $tBasic += $item['basic_salary'];
                            $tGross += $item['gross_salary'];
                            $tNssf  += $item['statutory_deduction'];
                            $tPaye  += $item['tax_deduction'];
                            $tNet   += $item['net_salary'];
                        ?>
                            <tr>
                                <td class="text-center"><?= $idx++ ?></td>
                                <td><?= htmlspecialchars($item['employee_code']) ?></td>
                                <td><strong><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></strong></td>
                                <td><?= htmlspecialchars($item['position_title'] ?? 'N/A') ?></td>
                                <td class="text-right"><?= number_format($item['basic_salary'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['gross_salary'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['statutory_deduction'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['tax_deduction'], 2) ?></td>
                                <td class="text-right net-salary"><?= number_format($item['net_salary'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4" class="text-right">JUMLA KUU (TOTAL):</td>
                            <td class="text-right"><?= number_format($tBasic, 2) ?></td>
                            <td class="text-right"><?= number_format($tGross, 2) ?></td>
                            <td class="text-right"><?= number_format($tNssf, 2) ?></td>
                            <td class="text-right"><?= number_format($tPaye, 2) ?></td>
                            <td class="text-right net-salary"><?= number_format($tNet, 2) ?></td>
                        </tr>
                    </tbody>
                </table>

                <div style="margin-top: 30px; display: flex; justify-content: space-between; font-size: 9px; color: #475569;">
                    <div>
                        <p><strong>Prepared By:</strong> HR & Payroll Admin</p>
                        <p style="margin-top: 15px;">Sahihi: ______________________</p>
                    </div>
                    <div>
                        <p><strong>Approved By:</strong> Finance / Managing Director</p>
                        <p style="margin-top: 15px;">Sahihi: ______________________</p>
                    </div>
                </div>
            </div>

            <script>
                window.onload = function() {
                    const element = document.getElementById('pdfContent');
                    const opt = {
                        margin:       [0.3, 0.3, 0.3, 0.3],
                        filename:     '<?= $filename ?>',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
                    };

                    html2pdf().set(opt).from(element).save().then(function() {
                        document.getElementById('loader').style.display = 'none';
                        setTimeout(function() {
                            window.close();
                        }, 1000);
                    });
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function exportBankFile(): void {
        RoleMiddleware::hasPermission('payroll.process');
        $db = Database::getInstance();

        $payrollId = (int)($_GET['id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        if ($payrollId > 0) {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE id = :id LIMIT 1");
            $stmtP->execute(['id' => $payrollId]);
            $period = $stmtP->fetch();
        } else {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
            $stmtP->execute(['m' => $month, 'y' => $year]);
            $period = $stmtP->fetch();
        }

        if (!$period) {
            redirect('/payroll', 'Payroll period record not found.', 'danger');
            return;
        }

        // Restrict bank file export to 'Approved' or 'Processed' periods
        if (($period['status'] ?? '') !== 'Processed' && ($period['status'] ?? '') !== 'Approved') {
            redirect("/payroll?month={$period['month']}&year={$period['year']}", 'Bank Salary Disbursement export is restricted to Approved or Processed (Locked) payrolls only!', 'warning');
            return;
        }

        AuditLogger::log('export_bank_file', 'Payroll', (string)$period['id']);

        $stmt = $db->prepare("
            SELECT pi.net_salary, e.employee_code, e.first_name, e.middle_name, e.last_name, 
                   e.bank_name, e.bank_account_no, e.bank_branch, e.swift_code
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            WHERE pi.payroll_period_id = :pid
            ORDER BY e.first_name ASC
        ");
        $stmt->execute(['pid' => $period['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $periodName = date('F Y', mktime(0, 0, 0, (int)$period['month'], 1, (int)$period['year']));
        $filename = "Bank_Disbursement_" . str_replace(' ', '_', $periodName) . ".xls";

        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $companyName     = $companySettings['company_name'] ?? 'DonTech Solutions Ltd';

        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta charset=\"UTF-8\">";
        echo "<style>
                .text { mso-number-format:\"\\@\"; }
                .number { mso-number-format:\"0\\.00\"; }
              </style>";
        echo "</head><body>";
        echo "<h2>" . htmlspecialchars($companyName) . " — Bank Salary Disbursement Schedule</h2>";
        echo "<h3>Payroll Period: " . htmlspecialchars($periodName) . " | Generated: " . date('d M Y H:i:s') . "</h3>";
        echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; font-family:sans-serif; font-size:12px;\">";
        echo "<tr style=\"background-color:#4f46e5; color:#ffffff; font-weight:bold;\">
                <th>S/N</th>
                <th>Employee Code</th>
                <th>Employee Full Name</th>
                <th>Bank Name</th>
                <th>Bank Account Number</th>
                <th>Bank Branch</th>
                <th>SWIFT / Sort Code</th>
                <th>Net Salary (TZS)</th>
                <th>Payment Narrative</th>
                <th>Payment Method Status</th>
              </tr>";

        $i = 1;
        $totalNet = 0;

        foreach ($items as $item) {
            $fullName = trim($item['first_name'] . ' ' . ($item['middle_name'] ? $item['middle_name'] . ' ' : '') . $item['last_name']);
            $hasAccount = !empty($item['bank_account_no']);
            $bankName   = $hasAccount ? ($item['bank_name'] ?: 'CRDB Bank') : 'CASH / CHEQUE';
            $accountNo  = $hasAccount ? $item['bank_account_no'] : 'N/A (Cash Disbursement)';
            $branch     = $hasAccount ? ($item['bank_branch'] ?: 'Main Branch') : 'N/A';
            $swift      = $hasAccount ? ($item['swift_code'] ?: 'N/A') : 'N/A';
            $narrative  = "Salary - {$periodName}";
            $payStatus  = $hasAccount ? 'Bank Transfer' : 'Cash / Cheque (No Account)';
            $netAmount  = (float)$item['net_salary'];
            $totalNet  += $netAmount;

            $netFormatted = number_format($netAmount, 2, '.', '');

            echo "<tr>
                    <td align=\"center\">{$i}</td>
                    <td class=\"text\">" . htmlspecialchars($item['employee_code']) . "</td>
                    <td>" . htmlspecialchars($fullName) . "</td>
                    <td>" . htmlspecialchars($bankName) . "</td>
                    <td class=\"text\">" . htmlspecialchars($accountNo) . "</td>
                    <td>" . htmlspecialchars($branch) . "</td>
                    <td class=\"text\">" . htmlspecialchars($swift) . "</td>
                    <td align=\"right\" class=\"number\">{$netFormatted}</td>
                    <td>" . htmlspecialchars($narrative) . "</td>
                    <td align=\"center\">" . htmlspecialchars($payStatus) . "</td>
                  </tr>";
            $i++;
        }

        $totalNetFormatted = number_format($totalNet, 2, '.', '');
        echo "<tr style=\"background-color:#f1f5f9; font-weight:bold;\">
                <td colspan=\"7\" align=\"right\">TOTAL DISBURSEMENT:</td>
                <td align=\"right\" class=\"number\" style=\"color:#059669;\">{$totalNetFormatted}</td>
                <td colspan=\"2\"></td>
              </tr>";
        echo "</table></body></html>";
        exit;
    }

    public function statutoryReports(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();

        $payrollId = (int)($_GET['id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        if ($payrollId > 0) {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE id = :id LIMIT 1");
            $stmtP->execute(['id' => $payrollId]);
            $period = $stmtP->fetch();
        } else {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
            $stmtP->execute(['m' => $month, 'y' => $year]);
            $period = $stmtP->fetch();
        }

        if (!$period) {
            redirect('/payroll', 'Payroll period record not found.', 'danger');
            return;
        }

        if (!in_array($period['status'] ?? '', ['Approved', 'Processed', 'Closed'], true)) {
            redirect('/payroll', 'Statutory reporting is available only for approved or locked payroll periods.', 'warning');
            return;
        }

        AuditLogger::log('view_statutory_report', 'Payroll', (string)$period['id']);

        $stmt = $db->prepare("
            SELECT pi.*, 
                   e.first_name, e.middle_name, e.last_name, e.employee_code,
                   e.tin_number, e.nssf_number, e.nida_number
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            WHERE pi.payroll_period_id = :pid
            ORDER BY e.first_name ASC
        ");
        $stmt->execute(['pid' => $period['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

        view('payroll.statutory_report', [
            'title'   => "Statutory Returns & Compliance — " . date('F Y', mktime(0, 0, 0, (int)$period['month'], 1, (int)$period['year'])),
            'period'  => $period,
            'items'   => $items,
            'company' => $companySettings
        ]);
    }

    public function exportStatutoryExcel(): void {
        RoleMiddleware::hasPermission('reports.view');
        $db = Database::getInstance();

        $payrollId = (int)($_GET['id'] ?? 0);
        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year'] ?? date('Y'));

        if (!$payrollId) {
            $period = $this->getExportablePeriod($db, $month, $year);
            AuditLogger::log('export_statutory_excel', 'Payroll', (string)$period['id']);
        }

        if ($payrollId > 0) {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE id = :id LIMIT 1");
            $stmtP->execute(['id' => $payrollId]);
            $period = $stmtP->fetch();
        } else {
            $stmtP = $db->prepare("SELECT * FROM payroll_periods WHERE month = :m AND year = :y LIMIT 1");
            $stmtP->execute(['m' => $month, 'y' => $year]);
            $period = $stmtP->fetch();
        }

        if (!$period) {
            redirect('/payroll', 'Payroll period record not found.', 'danger');
            return;
        }

        if (!in_array($period['status'] ?? '', ['Approved', 'Processed', 'Closed'], true)) {
            redirect('/payroll', 'Statutory export is available only for approved or locked payroll periods.', 'warning');
            return;
        }

        AuditLogger::log('export_statutory_excel', 'Payroll', (string)$period['id']);

        $stmt = $db->prepare("
            SELECT pi.*, 
                   e.first_name, e.middle_name, e.last_name, e.employee_code,
                   e.tin_number, e.nssf_number, e.nida_number
            FROM payroll_items pi
            JOIN employees e ON pi.employee_id = e.id
            WHERE pi.payroll_period_id = :pid
            ORDER BY e.first_name ASC
        ");
        $stmt->execute(['pid' => $period['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $companySettings = $db->query("SELECT setting_key, setting_value FROM company_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $companyName     = $companySettings['company_name'] ?? 'DonTech Solutions Ltd';
        $companyTin      = $companySettings['company_tin'] ?? '109-482-771';

        $periodName = date('F Y', mktime(0, 0, 0, (int)$period['month'], 1, (int)$period['year']));
        $filename   = "Statutory_Compliance_Report_" . str_replace(' ', '_', $periodName) . ".xls";

        if (ob_get_level()) {
            ob_end_clean();
        }

        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: max-age=0");

        echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
        echo "<head><meta charset=\"UTF-8\">";
        echo "<style>
                .text { mso-number-format:\"\\@\"; }
                .number { mso-number-format:\"#,##0.00\"; }
                th { background-color: #4f46e5; color: #ffffff; font-weight: bold; }
              </style>";
        echo "</head><body>";

        echo "<h2>" . htmlspecialchars($companyName) . " — Statutory Returns & Compliance Summary</h2>";
        echo "<h3>Period: " . htmlspecialchars($periodName) . " | Employer TIN: " . htmlspecialchars($companyTin) . " | Generated: " . date('d M Y H:i:s') . "</h3>";

        // Section 1: TRA PAYE Schedule
        echo "<h3>1. TRA PAYE Monthly Return Schedule</h3>";
        echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; font-family:sans-serif; font-size:11px;\">";
        echo "<tr>
                <th>S/N</th>
                <th>Employee Code</th>
                <th>Employee Full Name</th>
                <th>TIN Number</th>
                <th>Basic Salary (TZS)</th>
                <th>Gross Salary (TZS)</th>
                <th>Employee NSSF (10%) (TZS)</th>
                <th>Taxable Income (TZS)</th>
                <th>PAYE Tax Amount (TZS)</th>
              </tr>";

        $sn = 1;
        $tBasic = 0; $tGross = 0; $tNssf = 0; $tTaxable = 0; $tPaye = 0; $tEmpNssf = 0; $tWcf = 0; $tSdl = 0;

        foreach ($items as $it) {
            $fullName = trim($it['first_name'] . ' ' . ($it['middle_name'] ? $it['middle_name'] . ' ' : '') . $it['last_name']);
            $bs  = (float)$it['basic_salary'];
            $gr  = (float)$it['gross_salary'];
            $ns  = (float)$it['statutory_deduction'];
            $tx  = max(0, $gr - $ns);
            $py  = (float)$it['tax_deduction'];
            $en  = (float)($it['employer_nssf'] ?? ($gr * 0.10));
            $wcf = (float)($it['employer_wcf'] ?? ($gr * 0.005));
            $sdl = (float)($it['employer_sdl'] ?? ($gr * 0.035));

            $tBasic   += $bs;
            $tGross   += $gr;
            $tNssf    += $ns;
            $tTaxable += $tx;
            $tPaye    += $py;
            $tEmpNssf += $en;
            $tWcf     += $wcf;
            $tSdl     += $sdl;

            echo "<tr>
                    <td align=\"center\">{$sn}</td>
                    <td class=\"text\">" . htmlspecialchars($it['employee_code']) . "</td>
                    <td>" . htmlspecialchars($fullName) . "</td>
                    <td class=\"text\">" . htmlspecialchars($it['tin_number'] ?: 'N/A') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($bs, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($gr, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($ns, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($tx, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\" style=\"font-weight:bold; color:#e11d48;\">" . number_format($py, 2, '.', '') . "</td>
                  </tr>";
            $sn++;
        }

        echo "<tr style=\"background-color:#f1f5f9; font-weight:bold;\">
                <td colspan=\"4\" align=\"right\">TOTAL TRA PAYE REMITTANCE:</td>
                <td align=\"right\" class=\"number\">" . number_format($tBasic, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\">" . number_format($tGross, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\">" . number_format($tNssf, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\">" . number_format($tTaxable, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\" style=\"color:#e11d48;\">" . number_format($tPaye, 2, '.', '') . "</td>
              </tr>";
        echo "</table><br><br>";

        // Section 2: NSSF Schedule
        echo "<h3>2. NSSF Monthly Contribution Schedule (20% Combined Remittance)</h3>";
        echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; font-family:sans-serif; font-size:11px;\">";
        echo "<tr>
                <th>S/N</th>
                <th>Employee Code</th>
                <th>Employee Full Name</th>
                <th>NSSF Pension Number</th>
                <th>Gross Salary (TZS)</th>
                <th>Employee Cont. (10%) (TZS)</th>
                <th>Employer Cont. (10%) (TZS)</th>
                <th>Total Remittance (20%) (TZS)</th>
              </tr>";

        $sn = 1;
        foreach ($items as $it) {
            $fullName = trim($it['first_name'] . ' ' . ($it['middle_name'] ? $it['middle_name'] . ' ' : '') . $it['last_name']);
            $gr  = (float)$it['gross_salary'];
            $ne  = (float)$it['statutory_deduction'];
            $er  = (float)($it['employer_nssf'] ?? ($gr * 0.10));
            $rem = $ne + $er;

            echo "<tr>
                    <td align=\"center\">{$sn}</td>
                    <td class=\"text\">" . htmlspecialchars($it['employee_code']) . "</td>
                    <td>" . htmlspecialchars($fullName) . "</td>
                    <td class=\"text\">" . htmlspecialchars($it['nssf_number'] ?: 'N/A') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($gr, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($ne, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\">" . number_format($er, 2, '.', '') . "</td>
                    <td align=\"right\" class=\"number\" style=\"font-weight:bold; color:#4338ca;\">" . number_format($rem, 2, '.', '') . "</td>
                  </tr>";
            $sn++;
        }

        $totNssfCombined = $tNssf + $tEmpNssf;
        echo "<tr style=\"background-color:#f1f5f9; font-weight:bold;\">
                <td colspan=\"4\" align=\"right\">TOTAL NSSF 20% REMITTANCE:</td>
                <td align=\"right\" class=\"number\">" . number_format($tGross, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\">" . number_format($tNssf, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\">" . number_format($tEmpNssf, 2, '.', '') . "</td>
                <td align=\"right\" class=\"number\" style=\"color:#4338ca;\">" . number_format($totNssfCombined, 2, '.', '') . "</td>
              </tr>";
        echo "</table><br><br>";

        // Section 3: Employer Liabilities & CTC Summary
        echo "<h3>3. Employer Statutory Liabilities & Cost to Company (CTC) Summary</h3>";
        echo "<table border=\"1\" cellpadding=\"5\" cellspacing=\"0\" style=\"border-collapse:collapse; font-family:sans-serif; font-size:11px;\">";
        echo "<tr><th>Statutory Obligation / Levy</th><th>Percentage / Rate</th><th>Total Amount (TZS)</th></tr>";
        echo "<tr><td>Gross Payroll Total</td><td>100%</td><td align=\"right\" class=\"number\">" . number_format($tGross, 2, '.', '') . "</td></tr>";
        echo "<tr><td>Employer NSSF Contribution</td><td>10.0%</td><td align=\"right\" class=\"number\">" . number_format($tEmpNssf, 2, '.', '') . "</td></tr>";
        echo "<tr><td>WCF Levy (Workers Compensation Fund)</td><td>0.5%</td><td align=\"right\" class=\"number\">" . number_format($tWcf, 2, '.', '') . "</td></tr>";
        echo "<tr><td>SDL Levy (Skills Development Levy)</td><td>3.5%</td><td align=\"right\" class=\"number\">" . number_format($tSdl, 2, '.', '') . "</td></tr>";
        $totCtc = $tGross + $tEmpNssf + $tWcf + $tSdl;
        echo "<tr style=\"background-color:#e0e7ff; font-weight:bold;\"><td>GRAND TOTAL EMPLOYER COST (CTC)</td><td>114.0%</td><td align=\"right\" class=\"number\" style=\"color:#1e1b4b;\">" . number_format($totCtc, 2, '.', '') . "</td></tr>";
        echo "</table>";

        echo "</body></html>";
        exit;
    }
}
