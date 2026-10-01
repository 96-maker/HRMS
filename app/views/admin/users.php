<?php
// app/views/admin/users.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<?php
$activeCount  = count(array_filter($allUsers, fn($u) => !empty($u['user_id'])));
$pendingCount = count(array_filter($allUsers, fn($u) => empty($u['user_id'])));
$totalCount   = count($allUsers);
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Administrators & User Accounts</h1>
        <p class="text-xs text-slate-500">Manage user credentials, system activation, reset passwords, and assigned access roles.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= $baseUrl ?>/roles" class="px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm flex items-center gap-1.5">
            <i data-lucide="shield" class="size-4 text-indigo-600"></i> Role & Permission Matrix
        </a>
    </div>
</div>

<!-- Filter Tabs & Live Search Bar -->
<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <!-- Status Filter Tabs -->
    <div class="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto">
        <?php 
        $statusTabs = [
            'all'     => ['label' => 'All Accounts', 'count' => $totalCount, 'icon' => 'users'],
            'active'  => ['label' => 'Active Account', 'count' => $activeCount, 'icon' => 'check-circle-2'],
            'pending' => ['label' => 'Pending Activation', 'count' => $pendingCount, 'icon' => 'clock']
        ];
        foreach ($statusTabs as $tabKey => $tab):
            $isActive = strtolower($statusFilter) === strtolower($tabKey);
            $btnClass = $isActive 
                ? 'bg-indigo-600 text-white font-bold shadow-2xs' 
                : 'bg-slate-50 text-slate-600 font-semibold border border-slate-200 hover:bg-slate-100';
        ?>
            <a href="<?= $baseUrl ?>/users?status=<?= $tabKey ?>&search=<?= urlencode($searchQuery) ?>" class="px-3 py-1.5 text-xs rounded-lg flex items-center gap-1.5 transition-colors whitespace-nowrap <?= $btnClass ?>">
                <i data-lucide="<?= $tab['icon'] ?>" class="size-3.5"></i>
                <?= $tab['label'] ?>
                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold <?= $isActive ? 'bg-indigo-500 text-white' : 'bg-slate-200 text-slate-700' ?>">
                    <?= $tab['count'] ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Live Search Input Form -->
    <form method="GET" action="<?= $baseUrl ?>/users" class="relative w-full md:w-72">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <i data-lucide="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></i>
        <input type="text" id="userSearchInput" name="search" value="<?= htmlspecialchars($searchQuery) ?>" onkeyup="liveSearchUserAccounts()" placeholder="Search user, email, code or role..." class="pl-9 w-full px-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </form>
</div>

<script>
function liveSearchUserAccounts() {
    const query = document.getElementById('userSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.user-account-row');

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

<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">User Details</th>
                    <th class="p-3.5">Assigned Role</th>
                    <th class="p-3.5">Last Login</th>
                    <th class="p-3.5">Account Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="p-8 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i data-lucide="user-x" class="size-8 text-slate-300"></i>
                                <p class="text-xs font-semibold text-slate-600">No user accounts found matching your search filter.</p>
                                <a href="<?= $baseUrl ?>/users" class="mt-1 text-xs text-indigo-600 hover:underline">Reset Filters</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php $hasAccount = !empty($u['user_id']); ?>
                        <tr class="user-account-row hover:bg-slate-50/80 transition-colors">
                        <td class="p-3.5">
                            <div class="flex flex-col">
                                <?php if (!empty($u['first_name'])): ?>
                                    <span class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                                    <?php if ($hasAccount): ?>
                                        <span class="text-[11px] font-mono text-indigo-600">@<?= htmlspecialchars($u['username']) ?> • <?= htmlspecialchars($u['user_email']) ?></span>
                                    <?php else: ?>
                                        <span class="text-[11px] text-amber-600 font-medium">⚠️ Has No System Login Account (Employee Code: <?= htmlspecialchars($u['employee_code']) ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="font-bold text-slate-900 text-xs">@<?= htmlspecialchars($u['username']) ?></span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($u['user_email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="p-3.5">
                            <?php if ($hasAccount): ?>
                                <form method="POST" action="<?= $baseUrl ?>/users/update-role" class="flex items-center gap-1">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <select name="role_id" onchange="this.form.submit()" class="px-2 py-1 text-xs border rounded-lg bg-white font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500">
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id'] ?>" <?= (int)$u['role_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($r['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            <?php else: ?>
                                <span class="text-xs text-slate-400 font-italic">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3.5 text-xs font-mono text-slate-500">
                            <?= $hasAccount ? format_datetime($u['last_login_at']) : 'Never Logged In' ?>
                        </td>
                        <td class="p-3.5">
                            <?php if ($hasAccount): ?>
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                    Active Account
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    Pending Activation
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <?php if ($hasAccount): ?>
                                    <form method="POST" action="<?= $baseUrl ?>/users/reset-password" onsubmit="return confirm('Reset password for <?= htmlspecialchars($u['username']) ?>?');" class="inline-flex items-center gap-1">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                                        <input type="password" name="new_password" required minlength="12" autocomplete="new-password" placeholder="New password" class="w-24 px-2 py-1 text-[11px] border rounded font-mono bg-white" title="New password">
                                        <input type="password" name="new_password_confirmation" required minlength="12" autocomplete="new-password" placeholder="Confirm" class="w-20 px-2 py-1 text-[11px] border rounded font-mono bg-white" title="Confirm new password">
                                        <button type="submit" title="Reset Password" class="px-2 py-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded hover:bg-amber-100 flex items-center gap-1 cursor-pointer">
                                            <i data-lucide="key" class="size-3 text-amber-600"></i> Reset Password
                                        </button>
                                    </form>

                                    <?php if ((int)$u['user_id'] !== (int)$currentUser['id'] && (int)$u['user_id'] !== 1): ?>
                                        <form method="POST" action="<?= $baseUrl ?>/users/delete" onsubmit="return confirm('Delete user account <?= htmlspecialchars($u['username']) ?>?');" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" title="Delete Account" class="p-1 text-rose-500 hover:bg-rose-100 hover:text-rose-700 rounded cursor-pointer transition-colors">
                                                <i data-lucide="trash-2" class="size-4"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form method="POST" action="<?= $baseUrl ?>/users/activate" class="inline-flex items-center gap-1">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="employee_id" value="<?= $u['employee_id'] ?>">
                                        <select name="role_id" class="px-2 py-1 text-[11px] border rounded bg-white font-medium text-slate-700">
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?= $r['id'] ?>" <?= $r['slug'] === 'employee' ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="Initial password" class="w-28 px-2 py-1 text-[11px] border rounded font-mono bg-white" title="Initial password">
                                        <button type="submit" class="px-2.5 py-1 text-xs font-bold text-white bg-emerald-600 rounded hover:bg-emerald-700 shadow-sm flex items-center gap-1">
                                            <i data-lucide="user-check" class="size-3"></i> Activate User
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
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
