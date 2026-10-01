<?php
// app/views/more/index.php
require __DIR__ . '/../layouts/header.php';
$baseUrl = get_base_url();

$moduleCards = [
    [
        'title' => 'System Settings',
        'description' => 'Company profile, shifts, tax rules and integrations.',
        'icon' => 'settings-2',
        'accent' => 'indigo',
        'permission' => 'settings.manage',
        'links' => [
            ['label' => 'General settings', 'url' => '/settings', 'icon' => 'sliders-horizontal'],
            ['label' => 'Tools & APIs', 'url' => '/tools', 'icon' => 'wrench'],
        ],
    ],
    [
        'title' => 'Users & Access Control',
        'description' => 'Manage system users, roles and permissions.',
        'icon' => 'shield-check',
        'accent' => 'violet',
        'permission' => 'settings.manage',
        'links' => [
            ['label' => 'Users', 'url' => '/users', 'icon' => 'users-round'],
            ['label' => 'Roles & permissions', 'url' => '/roles', 'icon' => 'key-round'],
        ],
    ],
    [
        'title' => 'Reports & Analytics',
        'description' => 'Workforce insights and downloadable reports.',
        'icon' => 'chart-no-axes-combined',
        'accent' => 'blue',
        'permission' => 'reports.view',
        'links' => [
            ['label' => 'Open analytics', 'url' => '/reports', 'icon' => 'layout-dashboard'],
            ['label' => 'PDF / Excel exports', 'url' => '/reports', 'icon' => 'download'],
        ],
    ],
    [
        'title' => 'Broadcasts & SMS',
        'description' => 'Send internal announcements and employee alerts.',
        'icon' => 'megaphone',
        'accent' => 'amber',
        'permission' => 'settings.manage',
        'links' => [
            ['label' => 'Open broadcasts', 'url' => '/broadcasts', 'icon' => 'radio'],
            ['label' => 'SMS campaigns', 'url' => '/broadcasts', 'icon' => 'message-square'],
        ],
    ],
];

$accentClasses = [
    'indigo' => ['icon' => 'bg-indigo-100 text-indigo-600', 'badge' => 'bg-indigo-50 text-indigo-700', 'hover' => 'hover:border-indigo-200 hover:bg-indigo-50/50'],
    'violet' => ['icon' => 'bg-violet-100 text-violet-600', 'badge' => 'bg-violet-50 text-violet-700', 'hover' => 'hover:border-violet-200 hover:bg-violet-50/50'],
    'blue' => ['icon' => 'bg-blue-100 text-blue-600', 'badge' => 'bg-blue-50 text-blue-700', 'hover' => 'hover:border-blue-200 hover:bg-blue-50/50'],
    'amber' => ['icon' => 'bg-amber-100 text-amber-600', 'badge' => 'bg-amber-50 text-amber-700', 'hover' => 'hover:border-amber-200 hover:bg-amber-50/50'],
];
?>

<div class="mx-auto max-w-3xl space-y-5 pb-28">
    <div class="flex items-center gap-3">
        <a href="<?= $baseUrl ?>/dashboard" class="flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition-colors hover:text-indigo-600" aria-label="Back to dashboard">
            <i data-lucide="arrow-left" class="size-5"></i>
        </a>
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-indigo-600">Workspace</p>
            <h1 class="text-2xl font-black tracking-tight text-slate-950">More</h1>
        </div>
    </div>

    <div class="flex items-end justify-between px-1">
        <div>
            <h2 class="text-sm font-extrabold text-slate-900">Quick access</h2>
            <p class="mt-0.5 text-xs text-slate-500">Manage the parts of your workspace you use most.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-500">Modules</span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <?php foreach ($moduleCards as $card): ?>
            <?php if (!AuthService::hasPermission($card['permission'])) continue; ?>
            <?php $accent = $accentClasses[$card['accent']]; ?>
            <section class="rounded-[1.35rem] border border-slate-200 bg-white p-4 shadow-sm transition-all <?= $accent['hover'] ?>">
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl <?= $accent['icon'] ?>">
                        <i data-lucide="<?= htmlspecialchars($card['icon']) ?>" class="size-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-900"><?= htmlspecialchars($card['title']) ?></h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500"><?= htmlspecialchars($card['description']) ?></p>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    <?php foreach ($card['links'] as $link): ?>
                        <a href="<?= $baseUrl . $link['url'] ?>" class="group flex min-h-11 items-center gap-3 rounded-xl border border-slate-100 px-3 py-2.5 text-xs font-bold text-slate-700 transition-colors hover:border-slate-200 hover:bg-slate-50">
                            <i data-lucide="<?= htmlspecialchars($link['icon']) ?>" class="size-4 text-slate-400 group-hover:text-indigo-500"></i>
                            <span class="flex-1"><?= htmlspecialchars($link['label']) ?></span>
                            <i data-lucide="chevron-right" class="size-4 text-slate-300 transition-transform group-hover:translate-x-0.5"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
