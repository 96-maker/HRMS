<?php
// app/views/departments/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Department Management</h1>
        <p class="text-xs text-slate-500">Manage organizational units, head assignments, and annual budget allocations.</p>
    </div>
    <?php if (AuthService::hasPermission('employees.create')): ?>
        <a href="<?= $baseUrl ?>/departments/create" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5 transition-colors">
            <i data-lucide="plus" class="size-4"></i> Create Department
        </a>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($departments as $dept): ?>
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between space-y-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i data-lucide="building-2" class="size-5"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($dept['name']) ?></h3>
                        <span class="text-xs text-slate-400 font-mono">Code: <?= htmlspecialchars($dept['code']) ?></span>
                    </div>
                </div>
                <?php if (AuthService::hasPermission('employees.delete')): ?>
                    <form method="POST" action="<?= $baseUrl ?>/departments/delete" onsubmit="return confirm('Delete department?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                        <button type="submit" class="text-slate-400 hover:text-rose-600"><i data-lucide="trash-2" class="size-4"></i></button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded-lg border border-slate-100">
                <div>
                    <span class="text-slate-400 block">Department Head</span>
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars(($dept['manager_fn'] ?? 'Unassigned') . ' ' . ($dept['manager_ln'] ?? '')) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 block">Headcount</span>
                    <span class="font-semibold text-slate-800"><?= $dept['total_employees'] ?> Employees</span>
                </div>
                <div class="col-span-2">
                    <span class="text-slate-400 block">Annual Budget</span>
                    <span class="font-mono font-bold text-slate-900"><?= format_compact_currency($dept['budget']) ?></span>
                </div>
            </div>

            <a href="<?= $baseUrl ?>/employees?dept=<?= urlencode($dept['name']) ?>" class="w-full text-center px-3 py-2 text-xs font-semibold text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                View Staff Members (<?= $dept['total_employees'] ?>)
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
