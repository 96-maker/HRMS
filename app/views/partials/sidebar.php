<?php
// app/views/partials/sidebar.php
$baseUrl = get_base_url();
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($baseUrl !== '' && strpos($requestUri, $baseUrl) === 0) {
    $requestUri = substr($requestUri, strlen($baseUrl));
}
$uri = '/' . ltrim($requestUri, '/');
$navGroups = [
    ['label' => 'Workforce', 'icon' => 'users-round', 'color' => 'text-emerald-400', 'children' => [
        ['label' => 'Employees', 'url' => '/employees', 'icon' => 'users', 'perm' => 'employees.view'],
        ['label' => 'Departments', 'url' => '/departments', 'icon' => 'building-2', 'perm' => 'employees.view'],
        ['label' => 'Positions', 'url' => '/positions', 'icon' => 'briefcase', 'perm' => 'employees.view'],
        ['label' => 'Documents', 'url' => '/documents', 'icon' => 'folder-open', 'perm' => 'documents.view'],
    ]],
    ['label' => 'Time & Leave', 'icon' => 'calendar-clock', 'color' => 'text-amber-400', 'children' => [
        ['label' => 'Attendance', 'url' => '/attendance', 'icon' => 'calendar-check', 'perm' => 'attendance.view'],
        ['label' => 'Leave Management', 'url' => '/leave', 'icon' => 'calendar-days', 'perm' => 'leave.apply', 'badge' => 'pendingLeave'],
    ]],
    ['label' => 'Payroll', 'icon' => 'wallet-cards', 'color' => 'text-violet-400', 'children' => [
        ['label' => 'Payroll Operations', 'url' => '/payroll', 'icon' => 'wallet', 'perm' => 'payroll.process'],
        ['label' => 'Payslips', 'url' => '/payslips', 'icon' => 'receipt-text', 'perm' => 'payroll.view'],
        ['label' => 'Statutory Reports', 'url' => '/payroll/reports/statutory', 'icon' => 'file-check-2', 'perm' => 'reports.view'],
    ]],
    ['label' => 'System', 'icon' => 'settings', 'color' => 'text-sky-400', 'children' => [
        ['label' => 'Reports & Analytics', 'url' => '/reports', 'icon' => 'bar-chart-3', 'perm' => 'reports.view'],
        ['label' => 'Broadcasts & SMS', 'url' => '/broadcasts', 'icon' => 'radio', 'perm' => 'settings.manage'],
        ['label' => 'Users & Roles', 'url' => '/users', 'icon' => 'users-cog', 'perm' => 'settings.manage'],
        ['label' => 'System Settings', 'url' => '/settings', 'icon' => 'settings', 'perm' => 'settings.manage'],
        ['label' => 'Tools & APIs', 'url' => '/tools', 'icon' => 'wrench', 'perm' => 'settings.manage'],
    ]],
];
?>
<?php
// Fetch company branding settings
$db = Database::getInstance();
$compSettings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key IN ('company_name', 'system_logo_url')")->fetchAll(PDO::FETCH_KEY_PAIR);
$clientName = $compSettings['company_name'] ?? 'DonTech Solutions Ltd';
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
$pendingLeaveCount = AuthService::hasPermission('leave.approve')
    ? (int)$db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn()
    : 0;

$renderNavigation = function () use ($baseUrl, $uri, $navGroups, $pendingLeaveCount): void {
    $renderLink = function (array $item) use ($baseUrl, $uri, $pendingLeaveCount): void {
        if ($item['perm'] !== null && !AuthService::hasPermission($item['perm'])) {
            return;
        }

        $isActive = ($item['url'] === '/dashboard' && ($uri === '/' || $uri === '/dashboard'))
            || ($item['url'] !== '/dashboard' && str_starts_with($uri, $item['url']));
        $badge = ($item['badge'] ?? '') === 'pendingLeave' ? $pendingLeaveCount : 0;
        $fullUrl = $baseUrl . $item['url'];
        ?>
            <a href="<?= $fullUrl ?>" class="flex items-center gap-3 border-l-2 pl-11 pr-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $isActive ? 'border-indigo-400 bg-indigo-500/15 text-white font-semibold shadow-sm' : 'border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-white' ?>">
                <i data-lucide="corner-down-right" class="size-3.5 shrink-0 text-slate-500"></i>
            <span class="flex-1"><?= htmlspecialchars($item['label']) ?></span>
            <?php if ($badge > 0): ?><span class="min-w-5 rounded-full bg-amber-500 px-1.5 py-0.5 text-center text-[10px] font-bold text-slate-950"><?= $badge > 99 ? '99+' : $badge ?></span><?php endif; ?>
        </a>
        <?php
    };

    ?>
    <a href="<?= $baseUrl ?>/dashboard" class="flex items-center gap-3 border-l-2 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?= ($uri === '/' || $uri === '/dashboard') ? 'border-indigo-400 bg-indigo-500/15 text-white font-semibold shadow-sm' : 'border-transparent text-slate-400 hover:bg-slate-800/50 hover:text-white' ?>">
        <i data-lucide="layout-dashboard" class="size-4 <?= ($uri === '/' || $uri === '/dashboard') ? 'text-indigo-400' : 'text-slate-400' ?>"></i>
        <span>Dashboard</span>
    </a>
    <?php
    foreach ($navGroups as $group) {
        $visibleChildren = array_filter($group['children'], function (array $item): bool {
            return $item['perm'] === null || AuthService::hasPermission($item['perm']);
        });
        if (!$visibleChildren) {
            continue;
        }

        $groupIsActive = false;
        foreach ($visibleChildren as $child) {
            if (str_starts_with($uri, $child['url'])) {
                $groupIsActive = true;
                break;
            }
        }

        ?>
        <details class="group rounded-lg <?= $groupIsActive ? 'border-l-2 border-indigo-400 bg-slate-800/70' : 'border-l-2 border-transparent' ?>" data-nav-group <?= $groupIsActive ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center gap-2 px-4 pt-5 pb-1 text-xs font-semibold <?= $groupIsActive ? 'text-indigo-300' : 'text-slate-200' ?> hover:text-white">
                <i data-lucide="<?= htmlspecialchars($group['icon']) ?>" class="size-4 <?= htmlspecialchars($group['color']) ?>"></i>
                <span class="flex-1"><?= htmlspecialchars($group['label']) ?></span>
                <i data-lucide="chevron-down" class="size-3 transition-transform group-open:rotate-180"></i>
            </summary>
            <div class="ml-5 border-l border-slate-700/60 pl-1 space-y-0.5 pb-1">
                <?php foreach ($visibleChildren as $child) { $renderLink($child); } ?>
            </div>
        </details>
        <?php
    }
};
?>
<!-- Desktop Sidebar -->
<aside class="hidden lg:flex w-64 bg-navy-900 text-slate-300 flex-col shrink-0 border-r border-slate-800">
    <!-- Brand Logo Header -->
    <div class="h-16 flex items-center gap-3 px-4 border-b border-slate-800 bg-navy-900" title="<?= htmlspecialchars($clientName) ?>">
        <?php if ($hasLogo): ?>
            <div class="flex h-9 px-2 items-center justify-center rounded-lg bg-slate-800 border border-slate-700/80 shadow-sm shrink-0">
                <img src="<?= htmlspecialchars($clientLogoUrl) ?>" alt="<?= htmlspecialchars($clientName) ?> Logo" class="h-6 w-auto max-w-[100px] object-contain">
            </div>
        <?php else: ?>
            <div class="flex size-9 items-center justify-center rounded-lg bg-indigo-600 font-mono text-xs font-bold text-white shadow-sm shrink-0">
                <?= htmlspecialchars($companyInitials) ?>
            </div>
        <?php endif; ?>
        <div class="flex flex-col leading-tight overflow-hidden">
            <span class="text-xs font-bold text-white tracking-wide truncate" title="<?= htmlspecialchars($clientName) ?>"><?= htmlspecialchars($clientName) ?></span>
        </div>
    </div>

    <!-- Navigation Items -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?php $renderNavigation(); ?>
    </nav>

    <!-- User Profile Footer -->
    <div class="p-4 border-t border-slate-800 bg-navy-900">
        <div class="flex items-center gap-3 rounded-lg bg-slate-800/50 p-2.5">
            <span class="flex size-9 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                <?= get_initials($_SESSION['username'] ?? 'User') ?>
            </span>
            <div class="flex flex-col min-w-0 flex-1 leading-tight">
                <span class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                <span class="text-[10px] text-indigo-300 truncate"><?= htmlspecialchars($_SESSION['role_name'] ?? 'Staff') ?></span>
            </div>
            <form method="POST" action="<?= $baseUrl ?>/logout" class="inline">
                <input type="hidden" name="csrf_token" value="<?= CsrfMiddleware::generateToken() ?>">
                <button type="submit" title="Logout" class="text-slate-400 hover:text-rose-400 p-1 rounded transition-colors">
                    <i data-lucide="log-out" class="size-4"></i>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Slide-Over Drawer -->
<div id="mobileSidebarDrawer" class="fixed inset-0 z-50 hidden lg:hidden" role="dialog" aria-modal="true" aria-label="More menu">
    <!-- Backdrop Overlay -->
    <div class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity" onclick="toggleMobileSidebar()"></div>

    <aside class="relative z-50 h-full w-[85vw] max-w-sm bg-navy-900 text-slate-300 flex flex-col border-r border-slate-800 shadow-2xl">
        <!-- Mobile Drawer Header -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-800 bg-navy-900">
            <div class="flex items-center gap-3 overflow-hidden">
                <?php if ($hasLogo): ?>
                    <div class="flex h-8 px-2 items-center justify-center rounded-lg bg-slate-800 border border-slate-700/80 shrink-0">
                        <img src="<?= htmlspecialchars($clientLogoUrl) ?>" alt="Logo" class="h-5 w-auto object-contain">
                    </div>
                <?php else: ?>
                    <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-600 font-mono text-xs font-bold text-white shrink-0">
                        <?= htmlspecialchars($companyInitials) ?>
                    </div>
                <?php endif; ?>
                <span class="text-xs font-bold text-white truncate"><?= htmlspecialchars($clientName) ?></span>
            </div>
            <button type="button" onclick="toggleMobileSidebar()" class="size-8 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 flex items-center justify-center cursor-pointer">
                <i data-lucide="x" class="size-5"></i>
            </button>
        </div>

        <div class="border-b border-slate-800 bg-slate-900/60 px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-bold text-indigo-200 ring-1 ring-indigo-400/30">
                    <?= get_initials($_SESSION['username'] ?? 'User') ?>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                    <p class="truncate text-[11px] text-slate-400"><?= htmlspecialchars($_SESSION['role_name'] ?? 'Staff') ?></p>
                </div>
                <span class="rounded-full bg-emerald-400/10 px-2 py-1 text-[10px] font-semibold text-emerald-300">Online</span>
            </div>
        </div>

        <!-- Mobile Navigation Items -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
            <?php $renderNavigation(); ?>
        </nav>

        <!-- Mobile User Profile Footer -->
        <div class="p-4 border-t border-slate-800 bg-navy-900">
            <div class="flex items-center gap-3 rounded-lg bg-slate-800/50 p-2.5">
                <span class="flex size-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                    <?= get_initials($_SESSION['username'] ?? 'User') ?>
                </span>
                <div class="flex flex-col min-w-0 flex-1 leading-tight">
                    <span class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    <span class="text-[10px] text-indigo-300 truncate"><?= htmlspecialchars($_SESSION['role_name'] ?? 'Staff') ?></span>
                </div>
                <form method="POST" action="<?= $baseUrl ?>/logout" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= CsrfMiddleware::generateToken() ?>">
                    <button type="submit" title="Logout" class="text-slate-400 hover:text-rose-400 p-1 rounded transition-colors">
                        <i data-lucide="log-out" class="size-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>
</div>

<script>
function toggleMobileSidebar() {
    const drawer = document.getElementById('mobileSidebarDrawer');
    const moreButton = document.getElementById('mobile-more-button');
    if (drawer) {
        const isOpening = drawer.classList.contains('hidden');
        drawer.classList.toggle('hidden', !isOpening);
        document.body.classList.toggle('overflow-hidden', isOpening);
        if (moreButton) {
            moreButton.setAttribute('aria-expanded', isOpening ? 'true' : 'false');
            moreButton.classList.toggle('text-indigo-600', isOpening);
            moreButton.classList.toggle('text-slate-400', !isOpening);
            const icon = moreButton.querySelector('[data-more-icon]');
            if (icon) {
                icon.classList.toggle('bg-indigo-100', isOpening);
                icon.classList.toggle('text-indigo-600', isOpening);
            }
        }
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const drawer = document.getElementById('mobileSidebarDrawer');
        if (drawer && !drawer.classList.contains('hidden')) {
            toggleMobileSidebar();
        }
    }
});

document.querySelectorAll('#mobileSidebarDrawer nav a').forEach(function(link) {
    link.addEventListener('click', function() {
        const drawer = document.getElementById('mobileSidebarDrawer');
        if (drawer && !drawer.classList.contains('hidden')) {
            toggleMobileSidebar();
        }
    });
});

document.querySelectorAll('[data-nav-group]').forEach(function(group) {
    group.addEventListener('toggle', function() {
        if (!group.open) {
            return;
        }

        document.querySelectorAll('[data-nav-group]').forEach(function(otherGroup) {
            if (otherGroup !== group) {
                otherGroup.open = false;
            }
        });
    });
});
</script>
