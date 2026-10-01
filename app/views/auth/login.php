<?php
// app/views/auth/login.php
$csrfToken = CsrfMiddleware::generateToken();
$flash = get_flash();
$baseUrl = get_base_url();

$db = Database::getInstance();
$compSettings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key IN ('company_name', 'system_logo_url')")->fetchAll(PDO::FETCH_KEY_PAIR);
$clientName = $compSettings['company_name'] ?? 'MICO Hospital';
$clientLogoRaw = trim($compSettings['system_logo_url'] ?? '');

$hasLogo = !empty($clientLogoRaw);
$clientLogoUrl = '';

if ($hasLogo) {
    if (strpos($clientLogoRaw, 'http') === 0) {
        $clientLogoUrl = $clientLogoRaw;
    } else {
        $cleanPath = '/' . ltrim($clientLogoRaw, '/');
        if ($baseUrl !== '' && strpos($cleanPath, '/public/') === 0 && (str_ends_with($baseUrl, '/public') || str_contains($_SERVER['SCRIPT_NAME'], '/public/'))) {
            $cleanPath = substr($cleanPath, 7);
        }
        $clientLogoUrl = $baseUrl . $cleanPath;
    }
}
$companyInitials = get_initials($clientName);
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? "Sign In — {$clientName}") ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-2xl border border-slate-800">
        <div class="text-center">
            <?php if ($hasLogo): ?>
                <div class="mx-auto flex h-16 w-auto max-w-[200px] items-center justify-center mb-3">
                    <img src="<?= htmlspecialchars($clientLogoUrl) ?>" alt="<?= htmlspecialchars($clientName) ?> Logo" class="h-14 w-auto max-w-[200px] object-contain">
                </div>
            <?php else: ?>
                <div class="mx-auto size-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-mono text-xl font-bold shadow-md">
                    <?= htmlspecialchars($companyInitials) ?>
                </div>
            <?php endif; ?>
            <h2 class="mt-3 text-xl font-bold text-slate-900 tracking-tight"><?= htmlspecialchars($clientName) ?></h2>
            <p class="mt-1 text-xs text-slate-500">Enterprise HR & Payroll Management System</p>
        </div>

        <?php if ($flash): ?>
            <div class="rounded-lg p-3 text-xs font-medium <?= $flash['type'] === 'danger' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-4" action="login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Username or Email</label>
                <input id="username" name="username" type="text" value="admin@dontech.co.tz" required autocomplete="username" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Username or email">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Password</label>
                <input id="password" name="password" type="password" value="Password123!" required autocomplete="current-password" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter your password">
            </div>

            <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                Sign In to Dashboard
            </button>
        </form>

    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
