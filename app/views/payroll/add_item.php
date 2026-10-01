<?php
// app/views/payroll/add_item.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4 max-w-2xl mx-auto">
    <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Payroll Processing
    </a>
</div>

<div class="max-w-2xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="border-b border-slate-100 pb-4 mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Add Employee to Payroll Batch</h2>
        <p class="text-xs text-slate-500">Select an active employee to include in <?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) ?> payroll batch.</p>
    </div>

    <?php if (empty($availableEmployees)): ?>
        <div class="p-6 bg-slate-50 border border-slate-200 rounded-xl text-center space-y-3">
            <i data-lucide="check-circle-2" class="size-10 text-emerald-500 mx-auto block"></i>
            <p class="text-xs font-semibold text-slate-600">All active employees matching this frequency criteria are already included in this payroll batch.</p>
            <div class="pt-2">
                <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="px-4 py-2 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50 inline-block">Return to Payroll</a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= $baseUrl ?>/payroll/add-item" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
            <input type="hidden" name="month" value="<?= $selectedMonth ?>">
            <input type="hidden" name="year" value="<?= $selectedYear ?>">
            <input type="hidden" name="cycle" value="<?= htmlspecialchars($selectedCycle) ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Select Employee *</label>
                <div class="relative mb-2">
                    <i data-lucide="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></i>
                    <input type="text" id="employeeSelectSearch" onkeyup="filterEmployeeSelect()" placeholder="Search employee by name, code or cycle..." class="pl-9 w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <select name="employee_id" id="employee_select_box" size="6" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <?php foreach ($availableEmployees as $ae): ?>
                        <option value="<?= $ae['id'] ?>" class="py-1 px-1">
                            <?= htmlspecialchars($ae['first_name'] . ' ' . $ae['last_name']) ?> (<?= htmlspecialchars($ae['employee_code']) ?> · <?= htmlspecialchars($ae['pay_cycle'] ?? 'Monthly') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Shows active employees who have not exceeded their monthly frequency limit.</p>
            </div>

            <script>
            function filterEmployeeSelect() {
                const search = document.getElementById('employeeSelectSearch').value.toLowerCase().trim();
                const select = document.getElementById('employee_select_box');
                const options = select.options;
                let firstVisible = null;

                for (let i = 0; i < options.length; i++) {
                    const txt = options[i].text.toLowerCase();
                    if (search === '' || txt.includes(search)) {
                        options[i].style.display = '';
                        if (!firstVisible) firstVisible = options[i];
                    } else {
                        options[i].style.display = 'none';
                    }
                }
                if (firstVisible && search !== '') {
                    firstVisible.selected = true;
                }
            }
            </script>

            <div class="flex items-center justify-between border-t border-slate-100 pt-6">
                <button type="button" onclick="document.getElementById('populateAllFormPage').submit()" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5">
                    <i data-lucide="users" class="size-4"></i> + Load All Active Employees (<?= count($availableEmployees) ?>)
                </button>
                <div class="flex gap-2">
                    <a href="<?= $baseUrl ?>/payroll?month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&cycle=<?= urlencode($selectedCycle) ?>" class="px-4 py-2 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">Add to Payroll</button>
                </div>
            </div>
        </form>

        <form id="populateAllFormPage" method="POST" action="<?= $baseUrl ?>/payroll/populate-all" class="hidden">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
            <input type="hidden" name="month" value="<?= $selectedMonth ?>">
            <input type="hidden" name="year" value="<?= $selectedYear ?>">
            <input type="hidden" name="cycle" value="<?= htmlspecialchars($selectedCycle) ?>">
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
