<?php
// app/views/tools/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tools & API Integrations</h1>
        <p class="text-xs text-slate-500">Configure corporate branding logo, Beem SMS Gateway credentials, and SMTP email services.</p>
    </div>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="<?= $baseUrl ?>/tools/update" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Branding Logo -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3 flex items-center gap-1.5">
                <i data-lucide="image" class="size-4"></i> 1. System Branding Logo
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Upload New Logo (Browse Computer) *</label>
                    <input type="file" name="logo_file" accept="image/png, image/jpeg, image/jpg, image/svg+xml" class="mt-1 block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border rounded-lg cursor-pointer">
                    <input type="hidden" name="system_logo_url" value="<?= htmlspecialchars($settings['system_logo_url'] ?? '/public/assets/images/logo.png') ?>">
                    <span class="text-[11px] text-slate-400 mt-1 block">Supported formats: PNG, JPG, JPEG, SVG (Max 5MB).</span>
                </div>
                <div class="p-3 border rounded-lg bg-slate-50 flex items-center gap-3">
                    <?php 
                        $previewUrl = $settings['system_logo_url'] ?? '/public/assets/images/logo.png';
                        if (strpos($previewUrl, 'http') !== 0 && strpos($previewUrl, '/') === 0) {
                            $previewUrl = $baseUrl . $previewUrl;
                        }
                    ?>
                    <img src="<?= htmlspecialchars($previewUrl) ?>" alt="Logo Preview" class="h-12 w-auto max-w-[140px] object-contain shrink-0">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-800">Active Company Logo</span>
                        <span class="text-[11px] text-slate-500">Currently in use on Reports & Forms</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: SMS Gateway Engine (Developer Protected) -->
        <div class="relative rounded-xl border border-slate-200 p-5 bg-slate-50/50">
            <?php 
                $isDevUnlocked = isset($_SESSION['beem_dev_unlocked']) && $_SESSION['beem_dev_unlocked'] === true;
            ?>

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 flex items-center gap-1.5">
                    <i data-lucide="message-square" class="size-4"></i> 2. SMS Gateway Engine & Dispatcher
                </h3>
                <?php if ($isDevUnlocked): ?>
                    <a href="<?= $baseUrl ?>/tools/lock-dev" class="px-2.5 py-1 text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition-colors flex items-center gap-1">
                        <i data-lucide="lock" class="size-3"></i> Lock API Settings
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$isDevUnlocked): ?>
                <!-- Developer Lock Mask Screen -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 text-center shadow-xs space-y-4">
                    <div class="flex size-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 mx-auto">
                        <i data-lucide="shield-lock" class="size-6"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Developer Authorization Required</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">Access to SMS Gateway API Credentials & Sender ID requires Developer Passcode Authentication for security compliance.</p>
                    </div>

                    <div class="flex justify-center items-center gap-2 max-w-xs mx-auto">
                        <input type="password" id="devPasscodeInput" placeholder="Enter Security Passcode" class="px-3 py-2 border rounded-lg text-xs font-mono text-center w-full focus:ring-2 focus:ring-indigo-500">
                        <button type="button" onclick="unlockBeemDev()" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm shrink-0">
                            Unlock
                        </button>
                    </div>
                    <p id="devLockError" class="text-xs text-rose-600 font-semibold hidden">Incorrect Security Passcode!</p>
                </div>
            <?php else: ?>
                <!-- Unlocked SMS Gateway Configuration Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">SMS Gateway API Key *</label>
                        <input type="text" name="beem_api_key" value="<?= htmlspecialchars($settings['beem_api_key'] ?? 'bm_live_98a72b4c10e9') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">SMS Gateway Secret Key *</label>
                        <input type="password" name="beem_secret_key" value="<?= htmlspecialchars($settings['beem_secret_key'] ?? 'NzQ1MGIzMDQ5MDcxOGU5') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Sender ID / Mask *</label>
                        <input type="text" name="beem_sender_id" value="<?= htmlspecialchars($settings['beem_sender_id'] ?? 'DONTECH_HR') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Developer Security Passcode *</label>
                        <input type="password" name="beem_dev_password" value="<?= htmlspecialchars($settings['beem_dev_password'] ?? '2026') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                    </div>
                </div>
                <span class="text-[11px] text-slate-400 block">Used for sending automated SMS notification alerts (Leave status, Payslip notifications, Birthday wishes).</span>
            <?php endif; ?>
        </div>

        <!-- Section 3: Corporate SMTP Email -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3 flex items-center gap-1.5">
                <i data-lucide="mail" class="size-4"></i> 3. SMTP Mail Server Configuration
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SMTP Host Server</label>
                    <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'mail.dontech.co.tz') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '465') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SMTP Username / Email</label>
                    <input type="email" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? 'notifications@dontech.co.tz') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SMTP Password</label>
                    <input type="password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '••••••••••••') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t">
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
                <i data-lucide="save" class="size-4"></i> Save Tools Configuration
            </button>
        </div>
    </form>
</div>

<script>
function unlockBeemDev() {
    const code = document.getElementById('devPasscodeInput').value;
    const err = document.getElementById('devLockError');
    if (!code) return;

    fetch('<?= $baseUrl ?>/tools/unlock-dev', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'passcode=' + encodeURIComponent(code) + '&csrf_token=<?= $csrfToken ?>'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            err.classList.remove('hidden');
        }
    })
    .catch(() => {
        err.classList.remove('hidden');
    });
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
