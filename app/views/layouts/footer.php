<?php
// app/views/layouts/footer.php
$baseUrl = get_base_url();
$mobilePath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($baseUrl !== '' && strpos($mobilePath, $baseUrl) === 0) {
    $mobilePath = substr($mobilePath, strlen($baseUrl));
}
$mobilePath = '/' . ltrim($mobilePath, '/');
$mobileNav = [
    ['label' => 'Home', 'url' => '/dashboard', 'icon' => 'house', 'perm' => null],
    ['label' => 'People', 'url' => '/employees', 'icon' => 'users-round', 'perm' => 'employees.view'],
    ['label' => 'Payroll', 'url' => '/payslips', 'icon' => 'wallet-cards', 'perm' => 'payroll.view'],
    ['label' => 'More', 'url' => '/more', 'icon' => 'menu', 'perm' => null],
];
?>
                    <footer class="mt-8 pb-24 pt-4 border-t border-slate-200 text-center text-[11px] text-slate-400 font-medium lg:pb-0">
                        Generated securely via DonTech PeopleSuite HRMS | Technology Partner: DonTech Solutions Ltd
                    </footer>
                </div>
            </main>
        </div>
    </div>

    <nav class="mobile-bottom-nav fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around border-x-0 border-b-0 border-t border-slate-200 bg-white/95 px-3 shadow-[0_-12px_32px_rgba(15,23,42,0.12)] backdrop-blur lg:hidden" aria-label="Mobile navigation">
        <?php foreach ($mobileNav as $item): ?>
            <?php if ($item['perm'] !== null && !AuthService::hasPermission($item['perm'])) continue; ?>
            <?php $isMobileActive = str_starts_with($mobilePath, $item['url']); ?>
            <a href="<?= $baseUrl . $item['url'] ?>" class="flex min-w-14 flex-col items-center gap-1 rounded-xl px-2 py-1.5 text-[10px] font-semibold transition-colors <?= $isMobileActive ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-700' ?>">
                <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="size-5"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>

        <?php if (AuthService::hasPermission('attendance.clock')): ?>
            <a href="<?= $baseUrl ?>/attendance" class="-mt-7 flex size-14 shrink-0 flex-col items-center justify-center gap-1 rounded-full border-4 border-slate-50 bg-emerald-500 text-[10px] font-bold text-white shadow-lg shadow-emerald-500/30" aria-label="Open attendance clock">
                <i data-lucide="clock-3" class="size-5"></i>
                <span>Clock</span>
            </a>
        <?php endif; ?>
    </nav>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
