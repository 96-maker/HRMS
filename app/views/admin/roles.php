<?php
// app/views/admin/roles.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Role & Action Permission Matrix</h1>
        <p class="text-xs text-slate-500">Configure fine-grained module permissions per system role.</p>
    </div>
</div>

<div class="space-y-6">
    <?php foreach ($roles as $role): ?>
        <form method="POST" action="<?= $baseUrl ?>/roles/permissions" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="role_id" value="<?= $role['id'] ?>">

            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900"><?= htmlspecialchars($role['name']) ?></h3>
                    <p class="text-xs text-slate-500"><?= htmlspecialchars($role['description']) ?></p>
                </div>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                    Save <?= htmlspecialchars($role['name']) ?> Permissions
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <?php foreach ($permissions as $p): ?>
                    <?php $isChecked = isset($matrix[$role['id']][$p['id']]); ?>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs">
                        <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= $isChecked ? 'checked' : '' ?> class="rounded text-indigo-600 focus:ring-indigo-500 size-4">
                        <span class="font-medium text-slate-700"><?= htmlspecialchars($p['description'] ?: $p['slug']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
