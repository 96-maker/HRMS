<?php
// app/views/positions/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Positions & Roles Allocation</h1>
        <p class="text-xs text-slate-500">Define job titles, seniority levels, headcount structure, and open vacancies.</p>
    </div>
    <?php if (AuthService::hasPermission('employees.create')): ?>
        <a href="<?= $baseUrl ?>/positions/create" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5 transition-colors">
            <i data-lucide="plus" class="size-4"></i> Add Job Position
        </a>
    <?php endif; ?>
</div>

<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Job Title</th>
                    <th class="p-3.5">Department</th>
                    <th class="p-3.5">Level</th>
                    <th class="p-3.5">Approved Headcount</th>
                    <th class="p-3.5">Open Vacancies</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($positions as $pos): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5 font-semibold text-slate-900 text-xs"><?= htmlspecialchars($pos['title']) ?></td>
                        <td class="p-3.5 text-xs text-slate-700"><?= htmlspecialchars($pos['department_name']) ?></td>
                        <td class="p-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800">
                                <?= $pos['level'] ?>
                            </span>
                        </td>
                        <td class="p-3.5 font-mono text-xs text-slate-700"><?= $pos['headcount'] ?> Staff</td>
                        <td class="p-3.5">
                            <?php if ($pos['open_roles'] > 0): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <?= $pos['open_roles'] ?> Active Hiring
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">Filled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
