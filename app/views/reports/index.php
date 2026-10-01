<?php
// app/views/reports/index.php
require __DIR__ . '/../layouts/header.php';
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Reports & Analytics Engine</h1>
        <p class="text-xs text-slate-500">Demographic insights, departmental payroll allocations, and multi-format export streams.</p>
    </div>
    <div class="flex items-center gap-2">
        <div class="flex items-center bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden divide-x divide-slate-200">
            <a href="<?= $baseUrl ?>/reports/export-pdf?type=employees" target="_blank" class="px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 flex items-center gap-1.5 transition-colors">
                <i data-lucide="file-text" class="size-3.5"></i> Export PDF
            </a>
            <a href="<?= $baseUrl ?>/reports/export-excel?type=employees" class="px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 flex items-center gap-1.5 transition-colors">
                <i data-lucide="sheet" class="size-3.5"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<form method="GET" action="<?= $baseUrl ?>/reports" class="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
    <label class="text-xs font-semibold text-slate-600">Filter workforce</label>
    <select name="department_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
        <option value="0">All departments</option>
        <?php foreach ($departments as $department): ?>
            <option value="<?= (int)$department['id'] ?>" <?= $departmentFilter === (int)$department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
        <option value="all">All active statuses</option>
        <?php foreach (['Active', 'On Leave', 'Inactive'] as $status): ?>
            <option value="<?= $status ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-900">Apply filters</button>
</form>

<!-- 3 Summary Metrics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Total Active Headcount</span>
        <span class="text-3xl font-extrabold text-slate-900 mt-2 block"><?= $totalEmp ?> Staff</span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Gender Distribution</span>
        <span class="text-sm font-bold text-slate-800 mt-2 block">
            Male: <?= $maleEmp ?> (<?= $totalEmp > 0 ? round(($maleEmp/$totalEmp)*100) : 0 ?>%) | Female: <?= $femaleEmp ?> (<?= $totalEmp > 0 ? round(($femaleEmp/$totalEmp)*100) : 0 ?>%)
        </span>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Active Operating Units</span>
        <span class="text-3xl font-extrabold text-slate-900 mt-2 block"><?= count($deptCosts) ?> Units</span>
    </div>
</div>

<!-- Operational Snapshot -->
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ([
        ['label' => 'Present this month', 'value' => ($attendanceSummary['Present'] ?? 0), 'tone' => 'text-emerald-600'],
        ['label' => 'Late this month', 'value' => ($attendanceSummary['Late'] ?? 0), 'tone' => 'text-amber-600'],
        ['label' => 'Pending leave', 'value' => $pendingLeave, 'tone' => 'text-rose-600'],
        ['label' => 'Current net payroll', 'value' => format_currency($payrollSummary['net_total'] ?? 0), 'tone' => 'text-indigo-600'],
    ] as $snapshot): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="block text-xs font-semibold uppercase tracking-wider text-slate-500"><?= $snapshot['label'] ?></span>
            <span class="mt-2 block text-xl font-extrabold <?= $snapshot['tone'] ?>"><?= $snapshot['value'] ?></span>
        </div>
    <?php endforeach; ?>
</div>

<!-- Departmental Allocation Table -->
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-200">
        <h3 class="text-base font-bold text-slate-900">Departmental Payroll Allocation Breakdown</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Department</th>
                    <th class="p-3.5">Staff Count</th>
                    <th class="p-3.5">Monthly Payroll Commitment (TZS)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($deptCosts as $dc): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5 font-semibold text-slate-900 text-xs"><?= htmlspecialchars($dc['name']) ?></td>
                        <td class="p-3.5 text-xs text-slate-700 font-mono"><?= $dc['staff_count'] ?> Employees</td>
                        <td class="p-3.5 font-mono text-xs font-bold text-indigo-600"><?= format_currency($dc['monthly_cost']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
