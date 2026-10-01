<?php
// app/views/errors/404.php
require __DIR__ . '/../layouts/header.php';
?>
<div class="py-16 text-center space-y-4">
    <div class="size-16 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto">
        <i data-lucide="file-question" class="size-8"></i>
    </div>
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">404 Page Not Found</h1>
    <p class="text-xs text-slate-500 max-w-sm mx-auto">
        The requested route <code><?= htmlspecialchars($uri ?? '') ?></code> was not found on this server.
    </p>
    <div class="pt-2">
        <a href="/dashboard" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Return to Dashboard</a>
    </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
