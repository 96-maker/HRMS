<?php
// app/views/partials/top_header.php
$baseUrl = get_base_url();
$db = Database::getInstance();
$compSettings = $db->query("SELECT setting_key, setting_value FROM company_settings WHERE setting_key IN ('company_name', 'company_address', 'system_logo_url')")->fetchAll(PDO::FETCH_KEY_PAIR);
$activeWorkspace = $compSettings['company_name'] ?? 'DonTech Solutions Ltd';
$clientLogoRaw   = trim($compSettings['system_logo_url'] ?? '');

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
$companyInitials = get_initials($activeWorkspace);
?>
<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between border-b border-slate-200 bg-white/95 px-4 md:px-6 backdrop-blur lg:h-16">
    <div class="flex items-center gap-3">
        <div class="flex min-w-0 items-center gap-2.5">
            <?php if ($hasLogo): ?>
                <div class="flex size-10 items-center justify-center rounded-2xl bg-slate-100 border border-slate-200/80 shadow-2xs shrink-0">
                    <img src="<?= htmlspecialchars($clientLogoUrl) ?>" alt="<?= htmlspecialchars($activeWorkspace) ?> Logo" class="h-7 w-auto max-w-[100px] object-contain">
                </div>
            <?php else: ?>
                <div class="flex size-10 items-center justify-center rounded-2xl bg-indigo-600 font-mono text-xs font-bold text-white shadow-sm shrink-0">
                    <?= htmlspecialchars($companyInitials) ?>
                </div>
            <?php endif; ?>
            <h2 class="max-w-[150px] truncate text-sm font-extrabold tracking-tight text-slate-900 sm:max-w-none"><?= htmlspecialchars($activeWorkspace) ?></h2>
        </div>
        <span class="hidden md:inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
            Africa/Dar_es_Salaam (EAT +03:00)
        </span>
    </div>

    <div class="flex items-center gap-4">
        <!-- Interactive Notification Dropdown -->
        <?php 
            $currUserId = $_SESSION['user_id'] ?? null;
            $unreadNotifs = NotificationService::getUnread($currUserId);
            $unreadCount = count($unreadNotifs);
        ?>
        <div class="relative">
            <button type="button" id="notif-toggle-btn" onclick="toggleNotifDropdown()" class="relative flex size-9 items-center justify-center rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors focus:outline-none cursor-pointer" title="Notifications">
                <i data-lucide="bell" class="size-4.5 text-slate-700"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-600 text-[10px] font-extrabold text-white ring-2 ring-white shadow-xs">
                        <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                    </span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Panel -->
            <div id="notif-dropdown-panel" class="hidden absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white p-4 shadow-xl border border-slate-200 z-50">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Notifications</h3>
                        <?php if ($unreadCount > 0): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700"><?= $unreadCount ?> new</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" action="<?= $baseUrl ?>/notifications/mark-all-read" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= CsrfMiddleware::generateToken() ?>">
                            <button type="submit" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer">Mark all as read</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="max-h-72 overflow-y-auto space-y-2">
                    <?php if (empty($unreadNotifs)): ?>
                        <div class="py-8 text-center">
                            <i data-lucide="bell-off" class="size-8 text-slate-300 mx-auto mb-2"></i>
                            <p class="text-xs font-medium text-slate-500">No new notifications</p>
                            <p class="text-[11px] text-slate-400">You're all caught up!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unreadNotifs as $notif): ?>
                            <a href="<?= !empty($notif['url']) ? $baseUrl . $notif['url'] : '#' ?>" class="block p-3 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-indigo-50/50 hover:border-indigo-200 transition-all">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="text-xs font-bold text-slate-900"><?= htmlspecialchars($notif['title']) ?></h4>
                                    <span class="text-[10px] text-slate-400 font-mono whitespace-nowrap"><?= format_datetime($notif['created_at']) ?></span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-1 line-clamp-2"><?= htmlspecialchars($notif['message']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
        function toggleNotifDropdown() {
            const panel = document.getElementById('notif-dropdown-panel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }
        document.addEventListener('click', function(e) {
            const toggleBtn = document.getElementById('notif-toggle-btn');
            const panel = document.getElementById('notif-dropdown-panel');
            if (toggleBtn && panel && !toggleBtn.contains(e.target) && !panel.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
        </script>

        <div class="h-4 w-px bg-slate-200"></div>

        <!-- Profile Link -->
        <a href="<?= $baseUrl ?>/profile" class="flex size-11 items-center justify-center rounded-2xl text-xs font-semibold text-slate-700 hover:bg-slate-100 hover:text-indigo-600 transition-colors lg:h-auto lg:w-auto lg:rounded-none lg:bg-transparent">
            <i data-lucide="user" class="size-4 text-slate-500"></i>
            <span class="hidden lg:inline">My Account</span>
        </a>
    </div>
</header>
