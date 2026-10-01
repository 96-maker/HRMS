<?php
// app/views/admin/create_user.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/users" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to System Users
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Add System User Account</h2>
    <p class="text-xs text-slate-500 mb-6">Create login credentials and assign system access permission role for staff members.</p>

    <form method="POST" action="<?= $baseUrl ?>/users/invite" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Credential Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Username *</label>
                    <input type="text" name="username" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="e.g. victor.mbise">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address *</label>
                    <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="e.g. victor@dontech.co.tz">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. System Access & Security</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Assign System Role *</label>
                    <select name="role_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="p-3 bg-slate-50 border rounded-lg text-[11px] text-slate-500 flex flex-col justify-center">
                    <label for="user_password" class="font-semibold text-slate-700 mb-0.5">Initial Password *</label>
                    <input id="user_password" type="password" name="password" required minlength="12" autocomplete="new-password" class="mt-1 w-full px-2 py-1.5 text-xs border rounded-lg bg-white" placeholder="At least 12 characters">
                    <label for="user_password_confirmation" class="font-semibold text-slate-700 mt-2 mb-0.5">Confirm Password *</label>
                    <input id="user_password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="mt-1 w-full px-2 py-1.5 text-xs border rounded-lg bg-white" placeholder="Repeat the password">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/users" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Create User Account
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
