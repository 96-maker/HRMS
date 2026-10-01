<?php
// app/views/errors/403.php
require __DIR__ . '/../layouts/header.php';
?>
<div class="py-16 text-center space-y-4">
    <div class="size-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
        <i data-lucide="shield-alert" class="size-8"></i>
    </div>
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">403 Access Forbidden</h1>
    <p class="text-xs text-slate-500 max-w-sm mx-auto">
        You do not possess the required permission (<code><?= htmlspecialchars($permission ?? 'restricted') ?></code>) to access this enterprise module.
    </p>
    <div class="pt-2">
        <a href="<?= (isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === 'employee') ? get_base_url() . '/attendance' : get_base_url() . '/dashboard' ?>" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Return Home</a>
    </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
