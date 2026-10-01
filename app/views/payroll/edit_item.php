<?php
// app/views/payroll/edit_item.php
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();

$empFirstName  = $item['first_name'] ?? '';
$empMiddleName = $item['middle_name'] ?? '';
$empLastName   = $item['last_name'] ?? '';
$fullName      = trim($empFirstName . ' ' . ($empMiddleName ? $empMiddleName . ' ' : '') . $empLastName);
$empCode       = $item['employee_code'] ?? '';
$deptName      = $item['department_name'] ?? '';
$posTitle      = $item['position_title'] ?? '';
$cycle         = $item['pay_frequency'] ?? 'Monthly';
$isDaily       = (strtolower($cycle) === 'daily');

$basicSalary   = (float)($item['basic_salary'] ?? 0);
$allowances    = (float)($item['allowances'] ?? 0);
$overtime      = (float)($item['overtime'] ?? 0);
$otherDeduct   = (float)($item['other_deductions'] ?? 0);
$nssfDeduct    = (float)($item['statutory_deduction'] ?? 0);
$payeDeduct    = (float)($item['tax_deduction'] ?? 0);
$workNotes     = $item['notes'] ?? '';
$itemId        = (int)($item['item_id'] ?? 0);

// Shared layout partials use $item for their navigation loop, so prepare the
// payroll values before including the layout.
require __DIR__ . '/../layouts/header.php';
?>

<div class="flex items-center gap-2 mb-4 max-w-3xl mx-auto">
    <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Payroll Processing
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="border-b border-slate-100 pb-5 mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Manual Payroll Override</h2>
        <p class="text-xs text-slate-500 mb-4">Adjust basic wage, allowances, statutory tax deductions, and work log notes for this specific payroll record.</p>

        <!-- Employee Summary Banner -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <span class="flex size-12 items-center justify-center rounded-full bg-indigo-600 text-white font-bold text-sm shadow-sm ring-2 ring-indigo-100">
                    <?= get_initials($fullName) ?>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($fullName) ?></h3>
                        <span class="px-2 py-0.5 text-[10px] font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-md">
                            <?= htmlspecialchars($empCode) ?>
                        </span>
                    </div>
                    <?php if ($deptName || $posTitle): ?>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= htmlspecialchars($deptName) ?><?= ($deptName && $posTitle) ? ' • ' : '' ?><span class="text-slate-600 font-medium"><?= htmlspecialchars($posTitle) ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <?php 
                    $cycLower = strtolower($cycle);
                    $cycBadge = ($cycLower === 'monthly') ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 
                                (($cycLower === 'weekly') ? 'bg-purple-50 text-purple-700 border-purple-200' : 
                                (($cycLower === 'daily') ? 'bg-amber-50 text-amber-700 border-amber-200' : 
                                (($cycLower === 'any day' || $cycLower === 'any_day') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200')));
                ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold border rounded-lg shadow-2xs <?= $cycBadge ?>">
                    <i data-lucide="clock" class="size-3.5"></i>
                    Pay Frequency: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cycle))) ?>
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= $baseUrl ?>/payroll/update-item" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="id" value="<?= $itemId ?>">
        <input type="hidden" name="item_id" value="<?= $itemId ?>">
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <input type="hidden" name="year" value="<?= $selectedYear ?>">
        <input type="hidden" name="cycle" value="<?= htmlspecialchars($selectedCycle) ?>">

        <!-- Section 1: Daily Wage / Attendance Calculator (If Daily Pay Cycle) -->
        <?php if ($isDaily): ?>
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-600 mb-3">1. Attendance & Work Calculator</h3>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-2">
                    <label class="block text-xs font-bold text-amber-900 flex items-center gap-1.5">
                        <i data-lucide="calculator" class="size-4 text-amber-600"></i> Days Worked Calculator (Kazi ya Siku / Vibarua)
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Days Attended / Worked *</label>
                            <input type="number" id="page_days_worked" value="1" min="0" max="31" oninput="recalculateDailyWagePage()" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Daily Standard Rate (TZS)</label>
                            <input type="number" id="page_daily_rate" value="<?= $basicSalary ?>" readonly class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-slate-100 font-mono text-slate-600">
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Section 2: Earnings & Wage Adjustments -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3"><?= $isDaily ? '2' : '1' ?>. Earnings & Wage Adjustments</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Basic Wage / Salary (TZS) *</label>
                    <input type="number" step="0.01" id="page_basic" name="basic_salary" value="<?= number_format($basicSalary, 2, '.', '') ?>" required class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono font-semibold text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Allowances (TZS)</label>
                    <input type="number" step="0.01" id="page_allowances" name="allowances" value="<?= number_format($allowances, 2, '.', '') ?>" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Overtime / Extra Work (TZS)</label>
                    <input type="number" step="0.01" id="page_overtime" name="overtime" value="<?= number_format($overtime, 2, '.', '') ?>" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-slate-900">
                </div>
            </div>
        </div>

        <!-- Section 3: Statutory & Custom Deductions -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3"><?= $isDaily ? '3' : '2' ?>. Statutory & Custom Deductions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NSSF Deduction (10%) (TZS)</label>
                    <input type="number" step="0.01" id="page_nssf" name="statutory_deduction" value="<?= number_format($nssfDeduct, 2, '.', '') ?>" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">PAYE Tax Withheld (TZS)</label>
                    <input type="number" step="0.01" id="page_paye" name="tax_deduction" value="<?= number_format($payeDeduct, 2, '.', '') ?>" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono text-slate-900">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Absence / Fine Deduction (TZS)</label>
                    <input type="number" step="0.01" id="page_other" name="other_deductions" value="<?= number_format($otherDeduct, 2, '.', '') ?>" class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-rose-500 font-mono text-rose-700" placeholder="0.00">
                </div>
            </div>
        </div>

        <!-- Section 4: Work Log & Payroll Remarks -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3"><?= $isDaily ? '4' : '3' ?>. Work Log & Override Audit Trail</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Manual Adjustment / Override (Sababu ya kubadilisha kwa mikono) *</label>
                    <input type="text" name="adjustment_reason" value="<?= htmlspecialchars($item['adjustment_reason'] ?? '') ?>" placeholder="e.g. Marekebisho ya posho ya safari / Mkataba maalum wa mwezi..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <span class="text-[11px] text-slate-400 block mt-1">Sababu hii itahifadhiwa kwenye Audit Log ya mfumo ili kuzuia recalculation kufuta data hii.</span>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Description / Work Notes (Maelezo ya Kazi / Vibarua)</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Kazi ya kufyeka nyasi tarehe 05-08 Septemba..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($workNotes) ?></textarea>
                    <span class="text-[11px] text-slate-400 block mt-1">Yataonekana kwenye Payslip na taarifa za Payroll kwa ajili ya ushahidi.</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-6">
            <div>
                <?php if ((int)($item['is_manually_adjusted'] ?? 0) === 1): ?>
                    <button type="submit" form="resetForm" onclick="return confirm('Je, una uhakika unataka kufuta mabadiliko haya ya mikono na kurudi kwenye mahesabu ya kiotomatiki ya TRA/NSSF?')" class="px-4 py-2 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 flex items-center gap-1.5 transition-colors">
                        <i data-lucide="rotate-ccw" class="size-3.5"></i> Reset to Auto Calculation
                    </button>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="px-4 py-2 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</a>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                    Save & Apply Override
                </button>
            </div>
        </div>
    </form>

    <?php if ((int)($item['is_manually_adjusted'] ?? 0) === 1): ?>
        <form id="resetForm" method="POST" action="<?= $baseUrl ?>/payroll/reset-item" class="hidden">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="item_id" value="<?= $itemId ?>">
            <input type="hidden" name="month" value="<?= $selectedMonth ?>">
            <input type="hidden" name="year" value="<?= $selectedYear ?>">
            <input type="hidden" name="cycle" value="<?= htmlspecialchars($selectedCycle) ?>">
        </form>
    <?php endif; ?>
</div>

<?php if ($isDaily): ?>
<script>
const dailyBasicRate = <?= $basicSalary ?>;
const dailyAllowanceRate = <?= $allowances ?>;

function recalculateDailyWagePage() {
    const days = parseInt(document.getElementById('page_days_worked').value) || 0;
    const computedBasic = days * dailyBasicRate;
    const computedAllowances = days * dailyAllowanceRate;

    document.getElementById('page_basic').value = computedBasic.toFixed(2);
    document.getElementById('page_allowances').value = computedAllowances.toFixed(2);
    
    const overtime = parseFloat(document.getElementById('page_overtime').value) || 0;
    const gross = computedBasic + computedAllowances + overtime;
    
    document.getElementById('page_paye').value = (gross * 0.15).toFixed(2);
    document.getElementById('page_nssf').value = (computedBasic * 0.10).toFixed(2);
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
