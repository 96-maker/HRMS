<?php
// app/views/layouts/header.php
$currentUser = AuthService::user();
$csrfToken = CsrfMiddleware::generateToken();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($title ?? 'DonTech PeopleSuite — Enterprise HR & Payroll System') ?></title>
    
    <!-- Tailwind CSS (Standalone Production CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              navy: {
                800: '#1E293B',
                900: '#0F172A',
              }
            }
          }
        }
      }
    </script>
      <style>
        @media (max-width: 1023px) {
          body { background: #f6f8fb; }
          .app-shell { height: 100dvh; min-height: 100svh; }
          .mobile-bottom-nav {
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 50;
            width: 100%;
            min-height: 4rem;
            padding-bottom: env(safe-area-inset-bottom);
            border-radius: 1.25rem 1.25rem 0 0;
          }
          .mobile-app-main { overscroll-behavior-y: contain; }
          .mobile-app-main > .mx-auto { max-width: 100%; }
          .mobile-app-main { padding-bottom: calc(8rem + env(safe-area-inset-bottom)) !important; }
          .mobile-app-main .rounded-xl { border-radius: 1.15rem; }
          .mobile-app-main .shadow-sm { box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
          .mobile-app-main table { min-width: 680px; }
          .mobile-app-main input,
          .mobile-app-main select,
          .mobile-app-main textarea,
          .mobile-app-main button { min-height: 44px; }
        }
      </style>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="h-full font-sans text-slate-900 antialiased flex flex-col min-h-screen">
    
    <div class="app-shell flex h-screen bg-slate-50">
        <!-- Sidebar Partial -->
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <div class="app-scroll flex min-h-0 flex-1 flex-col w-full overflow-y-auto">
            <!-- Top Header Partial -->
            <?php require __DIR__ . '/../partials/top_header.php'; ?>

            <!-- Main Page View Container -->
            <main class="mobile-app-main flex-1 p-4 pb-32 md:p-6 md:pb-32 lg:p-8 lg:pb-8">
                <div class="mx-auto w-full max-w-7xl space-y-6">

                <!-- Toast Alert Banners -->
                <?php if ($flash): ?>
                    <div id="flash-banner" class="rounded-xl border p-4 shadow-sm flex items-center justify-between <?= $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($flash['type'] === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-rose-50 border-rose-200 text-rose-800') ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="size-5"></i>
                            <span class="text-sm font-medium"><?= htmlspecialchars($flash['message']) ?></span>
                        </div>
                        <button onclick="document.getElementById('flash-banner').remove()" class="text-current opacity-70 hover:opacity-100">
                            <i data-lucide="x" class="size-4"></i>
                        </button>
                    </div>
                <?php endif; ?>
