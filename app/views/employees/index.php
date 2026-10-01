<?php
// app/views/employees/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Employee Directory</h1>
        <p class="text-xs text-slate-500">Search, filter, and manage complete enterprise workforce profiles.</p>
    </div>
    <div class="flex items-center gap-2">
        <?php if (AuthService::hasPermission('reports.view')): ?>
            <div class="flex items-center bg-white border border-slate-200 rounded-lg shadow-sm overflow-hidden divide-x divide-slate-200">
                <a href="<?= $baseUrl ?>/reports/export-pdf?type=employees" target="_blank" class="px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 flex items-center gap-1.5 transition-colors">
                    <i data-lucide="file-text" class="size-3.5"></i> Export PDF
                </a>
                <a href="<?= $baseUrl ?>/reports/export-excel?type=employees" class="px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 flex items-center gap-1.5 transition-colors">
                    <i data-lucide="sheet" class="size-3.5"></i> Export Excel
                </a>
            </div>
        <?php endif; ?>
        <?php if (AuthService::hasPermission('employees.create')): ?>
        <a href="<?= $baseUrl ?>/employees/create" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors flex items-center gap-1.5">
            <i data-lucide="plus" class="size-4"></i> Add Employee
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters Bar -->
<form id="filterForm" method="GET" action="<?= $baseUrl ?>/employees" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
    <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></i>
        <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email or code..." oninput="debounceSubmit()" class="pl-9 w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <select name="dept" onchange="document.getElementById('filterForm').submit()" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= htmlspecialchars($d['name']) ?>" <?= $deptFilter === $d['name'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status" onchange="document.getElementById('filterForm').submit()" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Status</option>
            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="On Leave" <?= $statusFilter === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
            <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
</form>

<script>
let searchTimeout = null;
function debounceSubmit() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        document.getElementById('filterForm').submit();
    }, 400);
}
// Keep cursor at the end of input field after page load
window.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput && searchInput.value) {
        searchInput.focus();
        const val = searchInput.value;
        searchInput.value = '';
        searchInput.value = val;
    }
});
</script>

<!-- Data Table -->
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Code</th>
                    <th class="p-3.5">Department</th>
                    <th class="p-3.5">Position</th>
                    <th class="p-3.5">Basic Salary</th>
                    <th class="p-3.5">Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colSpan="7" class="p-8 text-center text-slate-400 text-xs">No employees found matching filter criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs">
                                        <?= get_initials($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                    </span>
                                    <div class="flex flex-col">
                                        <a href="<?= $baseUrl ?>/employees/view?id=<?= $emp['id'] ?>" class="font-semibold text-slate-900 hover:text-indigo-600 transition-colors text-xs">
                                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                        </a>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($emp['email']) ?></span>
                                        <?php if (!empty($emp['username'])): ?>
                                            <span class="text-[10px] text-indigo-600 font-mono flex items-center gap-1 mt-0.5">
                                                👤 @<?= htmlspecialchars($emp['username']) ?> (<?= htmlspecialchars($emp['role_name'] ?? 'User') ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 font-mono text-xs text-slate-500"><?= htmlspecialchars($emp['employee_code']) ?></td>
                            <td class="p-3.5 text-xs text-slate-700"><?= htmlspecialchars($emp['department_name'] ?? 'N/A') ?></td>
                            <td class="p-3.5 text-xs text-slate-700"><?= htmlspecialchars($emp['position_title'] ?? 'N/A') ?></td>
                            <td class="p-3.5 font-mono text-xs text-slate-900 font-semibold"><?= format_currency($emp['basic_salary']) ?></td>
                            <td class="p-3.5">
                                <?php 
                                    $st = $emp['status'];
                                    $badgeClass = $st === 'Active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : ($st === 'On Leave' ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20');
                                ?>
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset <?= $badgeClass ?>">
                                    <?= htmlspecialchars($st) ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= $baseUrl ?>/employees/view?id=<?= $emp['id'] ?>" title="View Profile" class="p-1 rounded text-slate-500 hover:bg-slate-100">
                                        <i data-lucide="eye" class="size-4"></i>
                                    </a>
                                    <?php if (AuthService::hasPermission('employees.edit')): ?>
                                        <a href="<?= $baseUrl ?>/employees/edit?id=<?= $emp['id'] ?>" title="Edit Employee Details" class="p-1 rounded text-indigo-600 hover:bg-indigo-50">
                                            <i data-lucide="pencil" class="size-4"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (AuthService::hasPermission('employees.delete')): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/employees/delete" onsubmit="return confirm('Set this employee status to Inactive? All historical payslips, attendance, and leave records will be preserved.');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $emp['id'] ?>">
                                            <button type="submit" title="Deactivate (Mark Inactive)" class="p-1 rounded text-rose-500 hover:bg-rose-50">
                                                <i data-lucide="user-x" class="size-4"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <span>Showing Page <?= $page ?> of <?= $totalPages ?> (Total <?= $totalRecords ?> employees)</span>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="<?= $baseUrl ?>/employees?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($deptFilter) ?>" class="px-2.5 py-1 border rounded bg-white hover:bg-slate-100">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= $baseUrl ?>/employees?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&dept=<?= urlencode($deptFilter) ?>" class="px-2.5 py-1 border rounded bg-white hover:bg-slate-100">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
