<?php
// app/views/payroll/payslips.php
require __DIR__ . '/../layouts/header.php';
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Employee Payslip Repository</h1>
        <p class="text-xs text-slate-500">View and print official individual employee monthly payslips.</p>
    </div>
</div>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Pay Period</th>
                    <th class="p-3.5">Gross Pay</th>
                    <th class="p-3.5">Net Pay (TZS)</th>
                    <th class="p-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($payslips as $ps): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5 font-semibold text-slate-900 text-xs"><?= htmlspecialchars($ps['first_name'] . ' ' . $ps['last_name']) ?></td>
                        <td class="p-3.5 text-xs text-slate-500 font-mono"><?= htmlspecialchars($ps['period_name']) ?></td>
                        <td class="p-3.5 font-mono text-xs text-slate-700"><?= format_currency($ps['gross_salary']) ?></td>
                        <td class="p-3.5 font-mono text-xs font-bold text-emerald-600"><?= format_currency($ps['net_salary']) ?></td>
                        <td class="p-3.5 text-right">
                            <a href="<?= $baseUrl ?>/payroll/view?id=<?= $ps['id'] ?>" class="px-2.5 py-1 text-xs font-semibold text-indigo-600 border border-indigo-200 rounded hover:bg-indigo-50">
                                View Official Payslip
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
