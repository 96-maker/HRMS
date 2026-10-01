<?php
// app/views/payroll/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();

$totalGross = array_sum(array_column($items, 'gross_salary'));
$totalPAYE  = array_sum(array_column($items, 'tax_deduction'));
$totalNSSF  = array_sum(array_column($items, 'statutory_deduction'));
$totalNet   = array_sum(array_column($items, 'net_salary'));
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Payroll Processing</h1>
        <p class="text-xs text-slate-500">Compute statutory deductions (PAYE, NSSF 10%, WCF 0.5%) and disburse monthly wages.</p>
    </div>
    <div class="flex items-center gap-2">
        <?php if ($period['status'] !== 'Processed' && AuthService::hasPermission('payroll.process')): ?>
            <a href="<?= $baseUrl ?>/payroll/add-item?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="px-3.5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="user-plus" class="size-4"></i> Add Employee to Payroll
            </a>
        <?php endif; ?>

        <?php if ($period['status'] === 'Processed'): ?>
            <span class="px-3 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-1.5 shadow-2xs">
                <i data-lucide="check-circle-2" class="size-4 text-emerald-600"></i> Locked (Processed)
            </span>
            <?php if (AuthService::hasPermission('payroll.process')): ?>
                <form method="POST" action="<?= $baseUrl ?>/payroll/unlock">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                    <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <button type="submit" class="px-3 py-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                        <i data-lucide="lock-keyhole-open" class="size-4 text-amber-600"></i> Unlock for Editing
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" action="<?= $baseUrl ?>/payroll/process">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                <input type="hidden" name="year" value="<?= $selectedYear ?>">
                <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-800 bg-slate-100 border border-slate-300 rounded-lg hover:bg-slate-200 shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
                    <i data-lucide="lock" class="size-4 text-indigo-600"></i> Process & Lock Payroll
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Financial Summary Cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Total Gross Payroll</span>
        <span class="text-xl font-bold font-mono text-slate-900 mt-2 block"><?= format_currency($totalGross) ?></span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">PAYE Tax Withheld</span>
        <span class="text-xl font-bold font-mono text-slate-900 mt-2 block"><?= format_currency($totalPAYE) ?></span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">NSSF Contribution (10%)</span>
        <span class="text-xl font-bold font-mono text-slate-900 mt-2 block"><?= format_currency($totalNSSF) ?></span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Total Net Take-Home</span>
        <span class="text-xl font-bold font-mono text-emerald-600 mt-2 block"><?= format_currency($totalNet) ?></span>
    </div>
</div>

<!-- Pay Frequency Filter Tabs -->
<div class="flex items-center justify-between bg-white p-3 rounded-xl border border-slate-200 shadow-xs mb-4 overflow-x-auto">
    <div class="flex items-center gap-1">
        <?php 
        $cycles = [
            'all'      => 'All Employees (' . count($allItems) . ')',
            'Monthly'  => 'Monthly (' . count(array_filter($allItems, fn($i) => strtolower($i['pay_cycle'] ?? 'monthly') === 'monthly')) . ')',
            'Weekly'   => 'Weekly (' . count(array_filter($allItems, fn($i) => strtolower($i['pay_cycle'] ?? '') === 'weekly')) . ')',
            'Daily'    => 'Daily (' . count(array_filter($allItems, fn($i) => strtolower($i['pay_cycle'] ?? '') === 'daily')) . ')',
            'Any Day'  => 'Any Day / On-Demand (' . count(array_filter($allItems, fn($i) => strtolower($i['pay_cycle'] ?? '') === 'any day')) . ')'
        ];
        foreach ($cycles as $cycKey => $cycLabel):
            $isActive = strtolower($selectedCycle) === strtolower($cycKey);
            $btnClass = $isActive ? 'bg-indigo-600 text-white font-bold shadow-2xs' : 'text-slate-600 font-semibold hover:bg-slate-100';
        ?>
            <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($cycKey) ?>" class="px-3 py-1.5 text-xs rounded-lg transition-colors <?= $btnClass ?>">
                <?= htmlspecialchars($cycLabel) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Period Selector & Employee Search Bar -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
    <!-- Period Selector Form -->
    <form method="GET" action="<?= $baseUrl ?>/payroll" class="flex items-center gap-2 w-full sm:w-auto">
        <input type="hidden" name="cycle" value="<?= htmlspecialchars($selectedCycle) ?>">
        <label class="text-xs font-bold text-slate-700 whitespace-nowrap flex items-center gap-1">
            <i data-lucide="calendar" class="size-4 text-indigo-600"></i> Payroll Period:
        </label>
        <select name="month" onchange="this.form.submit()" class="px-2.5 py-1.5 text-xs font-semibold border rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $selectedMonth === $m ? 'selected' : '' ?>>
                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                </option>
            <?php endfor; ?>
        </select>
        <select name="year" onchange="this.form.submit()" class="px-2.5 py-1.5 text-xs font-semibold border rounded-lg bg-white text-slate-800 focus:ring-2 focus:ring-indigo-500">
            <?php for ($y = 2030; $y >= 2024; $y--): ?>
                <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <!-- Actions & Live Employee Search Bar -->
    <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
        <a href="<?= $baseUrl ?>/payroll/export-bank?id=<?= $period['id'] ?>" title="Export Corporate Internet Banking Excel Schedule (CRDB, NMB, NBC, n.k.)" class="px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 flex items-center gap-1.5 shadow-2xs">
            <i data-lucide="building-2" class="size-4 text-indigo-600"></i> Bank Disbursement (.xls)
        </a>
        <a href="<?= $baseUrl ?>/payroll/reports/statutory?id=<?= $period['id'] ?>" title="View TRA PAYE & NSSF Statutory Compliance Schedules" class="px-3 py-1.5 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 flex items-center gap-1.5 shadow-2xs">
            <i data-lucide="shield-check" class="size-4 text-amber-600"></i> Statutory Reports
        </a>
        <a href="<?= $baseUrl ?>/payroll/export-excel?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" title="Export Payroll Summary Sheet to Excel" class="px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 flex items-center gap-1.5 shadow-2xs">
            <i data-lucide="file-spreadsheet" class="size-4 text-emerald-600"></i> Excel Export
        </a>
        <a href="<?= $baseUrl ?>/payroll/export-pdf?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>" target="_blank" title="Export Landscape PDF Report" class="px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 flex items-center gap-1.5 shadow-2xs">
            <i data-lucide="file-text" class="size-4 text-rose-600"></i> PDF Report
        </a>
        <div class="relative w-full sm:w-64">
            <i data-lucide="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></i>
            <input type="text" id="employeeSearch" onkeyup="searchPayrollEmployees()" placeholder="Search employee name or code..." class="pl-9 w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
    </div>
</div>

<script>
function searchPayrollEmployees() {
    const query = document.getElementById('employeeSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.payroll-row');

    rows.forEach(row => {
        const textContent = row.textContent.toLowerCase();
        if (query === '' || textContent.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<!-- Payroll Items Table -->
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Pay Frequency</th>
                    <th class="p-3.5">Basic Salary</th>
                    <th class="p-3.5">Allowances</th>
                    <th class="p-3.5">Gross Pay</th>
                    <th class="p-3.5">NSSF (10%)</th>
                    <th class="p-3.5">PAYE Tax</th>
                    <th class="p-3.5">Net Salary</th>
                    <th class="p-3.5 text-right">Payslip</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" class="p-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="users" class="size-8 text-slate-300"></i>
                                <p class="text-xs font-semibold text-slate-600">Hakuna mfanyakazi aliyeongezwa kwenye Payroll ya mwezi huu.</p>
                                <?php if (AuthService::hasPermission('payroll.process')): ?>
                                    <a href="<?= $baseUrl ?>/payroll/add-item?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="mt-2 px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100">
                                        + Ongeza Wafanyakazi Kwenye Payroll
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="payroll-row hover:bg-slate-50/80 transition-colors" 
                            data-item-id="<?= (int)$item['item_id'] ?>"
                            data-emp-name="<?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>"
                            data-emp-code="<?= htmlspecialchars($item['employee_code'] ?? 'N/A') ?>"
                            data-cycle="<?= htmlspecialchars($item['pay_cycle'] ?? 'Monthly') ?>"
                            data-basic="<?= (float)$item['basic_salary'] ?>"
                            data-allowances="<?= (float)$item['allowances'] ?>"
                            data-overtime="<?= (float)($item['overtime'] ?? 0) ?>"
                            data-other-deduct="<?= (float)($item['other_deductions'] ?? 0) ?>"
                            data-nssf="<?= (float)$item['statutory_deduction'] ?>"
                            data-paye="<?= (float)$item['tax_deduction'] ?>"
                            data-notes="<?= htmlspecialchars($item['notes'] ?? '') ?>">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                        <?= get_initials($item['first_name'] . ' ' . $item['last_name']) ?>
                                    </span>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></span>
                                            <?php if (!empty($item['is_manually_adjusted'])): ?>
                                                <span class="px-1.5 py-0.5 text-[9px] font-bold text-amber-800 bg-amber-100 border border-amber-300 rounded" title="Manual Payroll Override Applied (Protected from auto-population wipes)">
                                                    ⚙️ Override
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($item['position_title'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <?php 
                                    $cyc = $item['pay_cycle'] ?? 'Monthly';
                                    $cycBadge = $cyc === 'Monthly' ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20' : 
                                               ($cyc === 'Weekly' ? 'bg-purple-50 text-purple-700 ring-purple-600/20' : 
                                               ($cyc === 'Daily' ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 
                                               ($cyc === 'Any Day' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-50 text-slate-700 ring-slate-600/20')));
                                ?>
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset <?= $cycBadge ?>">
                                    <?= htmlspecialchars($cyc) ?>
                                </span>
                            </td>
                            <td class="p-3.5 font-mono text-xs text-slate-700"><?= format_currency($item['basic_salary']) ?></td>
                            <td class="p-3.5 font-mono text-xs text-slate-700"><?= format_currency($item['allowances']) ?></td>
                            <td class="p-3.5 font-mono text-xs font-semibold text-slate-900"><?= format_currency($item['gross_salary']) ?></td>
                            <td class="p-3.5 font-mono text-xs text-slate-500"><?= format_currency($item['statutory_deduction']) ?></td>
                            <td class="p-3.5 font-mono text-xs text-slate-500"><?= format_currency($item['tax_deduction']) ?></td>
                            <td class="p-3.5 font-mono text-xs font-bold text-emerald-600"><?= format_currency($item['net_salary']) ?></td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <?php if (AuthService::hasPermission('payroll.process') && $period['status'] !== 'Processed'): ?>
                                        <?php $pId = (int)($item['item_id'] ?? $item['id']); ?>
                                        <a href="<?= $baseUrl ?>/payroll/edit-item?id=<?= $pId ?>&month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="px-2 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 flex items-center gap-1">
                                            <i data-lucide="sliders" class="size-3 text-indigo-600"></i> Edit Manual
                                        </a>
                                        <form method="POST" action="<?= $baseUrl ?>/payroll/remove-item" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="item_id" value="<?= $pId ?>">
                                            <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                                            <input type="hidden" name="year" value="<?= $selectedYear ?>">
                                            <button type="submit" title="Remove from Payroll" class="p-1 text-rose-500 hover:bg-rose-50 rounded cursor-pointer">
                                                <i data-lucide="trash-2" class="size-3.5"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= $baseUrl ?>/payroll/view?id=<?= $pId ?>" class="px-2.5 py-1 text-xs font-semibold text-indigo-600 border border-indigo-200 rounded hover:bg-indigo-50">
                                        View Payslip
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
