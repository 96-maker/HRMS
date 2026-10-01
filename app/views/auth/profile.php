<?php
// app/views/auth/profile.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Account & Security</h1>
        <p class="text-xs text-slate-500">Manage your profile details and update account login credentials.</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Overview Card -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center text-center">
        <div class="size-20 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-2xl shadow-inner mb-4">
            <?= strtoupper(substr($user['username'] ?? 'U', 0, 2)) ?>
        </div>
        <h2 class="text-lg font-bold text-slate-900"><?= htmlspecialchars($user['username'] ?? 'User') ?></h2>
        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 mt-1">
            <?= htmlspecialchars($user['role'] ?? 'Staff') ?>
        </span>

        <div class="w-full border-t border-slate-100 mt-6 pt-4 text-left space-y-3">
            <div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Email Address</span>
                <span class="text-xs font-medium text-slate-800"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></span>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Account Created</span>
                <span class="text-xs font-medium text-slate-800"><?= isset($user['created_at']) ? date('d M Y, H:i', strtotime($user['created_at'])) : 'N/A' ?></span>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Account Status</span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                    <i data-lucide="check-circle-2" class="size-3.5"></i> Active
                </span>
            </div>
        </div>
    </div>

    <!-- Security & Password Reset Form -->
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-4 mb-6">
            <i data-lucide="key-round" class="size-5 text-indigo-600"></i>
            <h3 class="text-base font-bold text-slate-900">Change Password</h3>
        </div>

        <form method="POST" action="<?= $baseUrl ?>/change-password" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Current Password *</label>
                <input type="password" name="current_password" required class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Enter current password">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">New Password *</label>
                    <input type="password" name="new_password" required minlength="6" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="New password">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Confirm New Password *</label>
                    <input type="password" name="confirm_password" required minlength="6" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Confirm new password">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors cursor-pointer flex items-center gap-1.5">
                    <i data-lucide="lock" class="size-4"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
