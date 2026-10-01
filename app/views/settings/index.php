<?php
// app/views/settings/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System & Organization Settings</h1>
        <p class="text-xs text-slate-500">Configure company profile, Tanzanian tax settings, shift hours, and timezone.</p>
    </div>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="POST" action="<?= $baseUrl ?>/settings/update" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Company Profile</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Company Legal Name</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'DonTech Solutions Ltd') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">TIN Number (TRA)</label>
                    <input type="text" name="company_tin" value="<?= htmlspecialchars($settings['company_tin'] ?? '109-482-771') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700">Official Registered Address</label>
                    <input type="text" name="company_address" value="<?= htmlspecialchars($settings['company_address'] ?? 'Plot 45, Bagamoyo Rd, Victoria, Dar es Salaam') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone Contact</label>
                    <input type="text" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '+255 22 211 4455') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address</label>
                    <input type="email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? 'info@dontech.co.tz') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. System Defaults</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Default Currency</label>
                    <input type="text" name="currency_symbol" value="TZS" readonly class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-slate-100 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Timezone Default</label>
                    <input type="text" name="timezone" value="Africa/Dar_es_Salaam" readonly class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-slate-100 font-mono">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">3. Standard Working Shift</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Standard Shift Start</label>
                    <input type="time" name="standard_shift_start" value="<?= htmlspecialchars($settings['standard_shift_start'] ?? '08:00') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Standard Shift End</label>
                    <input type="time" name="standard_shift_end" value="<?= htmlspecialchars($settings['standard_shift_end'] ?? '17:00') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">4. Geofenced Mobile Punch Settings</h3>
            <div class="p-5 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Geofencing Status</label>
                        <select name="geofence_enabled" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                            <option value="1" <?= ($settings['geofence_enabled'] ?? '1') === '1' ? 'selected' : '' ?>>Active (Enforced)</option>
                            <option value="0" <?= ($settings['geofence_enabled'] ?? '1') === '0' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 flex items-center justify-between">
                            <span>Google Maps / Plus Code</span>
                            <span class="text-[10px] text-slate-400 font-normal font-sans">6756+9F Dar es Salaam</span>
                        </label>
                        <div class="relative mt-1">
                            <input type="text" 
                                   id="geofence_plus_code" 
                                   name="geofence_plus_code" 
                                   value="<?= htmlspecialchars($settings['geofence_plus_code'] ?? '6756+9F Dar es Salaam') ?>" 
                                   required 
                                   onchange="resolveLocationCode()"
                                   onkeyup="resolveLocationCode()"
                                   class="block w-full pl-9 pr-24 py-2 border border-slate-300 rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500 bg-white" 
                                   placeholder="Ingiza Plus Code au Coordinates...">
                            <span class="absolute left-3 top-2.5 text-indigo-600">
                                <i data-lucide="map-pin" class="size-4"></i>
                            </span>
                            <span id="geo-resolving-tag" class="absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                ✓ Validated
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Allowed Punch Radius (Meters)</label>
                        <div class="relative mt-1">
                            <input type="number" name="geofence_radius" value="<?= htmlspecialchars($settings['geofence_radius'] ?? '100') ?>" required class="block w-full px-3 py-2 border rounded-lg text-xs font-mono bg-white" placeholder="100">
                            <span class="absolute right-3 top-2 text-xs text-slate-400">meters</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Umbali wa juu zaidi unaomruhusu mfanyakazi kupiga Clock In / Out kutoka kitovu cha ofisi.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500">Auto-Resolved Coordinates (System internal)</label>
                        <div class="grid grid-cols-2 gap-2 mt-1">
                            <input type="text" id="geofence_latitude" name="geofence_latitude" value="<?= htmlspecialchars($settings['geofence_latitude'] ?? '-6.823512') ?>" readonly class="px-3 py-2 border rounded-lg text-xs font-mono bg-slate-100 text-slate-600 cursor-not-allowed">
                            <input type="text" id="geofence_longitude" name="geofence_longitude" value="<?= htmlspecialchars($settings['geofence_longitude'] ?? '39.269504') ?>" readonly class="px-3 py-2 border rounded-lg text-xs font-mono bg-slate-100 text-slate-600 cursor-not-allowed">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let resolveTimeout = null;
        function resolveLocationCode() {
            clearTimeout(resolveTimeout);
            const tag = document.getElementById('geo-resolving-tag');
            const latInput = document.getElementById('geofence_latitude');
            const lonInput = document.getElementById('geofence_longitude');
            const val = document.getElementById('geofence_plus_code').value.trim();

            if (!val) return;

            // Check if user directly pasted Lat, Lon coordinates
            const coordsMatch = val.match(/^(-?\d+\.\d+),\s*(-?\d+\.\d+)$/);
            if (coordsMatch) {
                latInput.value = parseFloat(coordsMatch[1]).toFixed(6);
                lonInput.value = parseFloat(coordsMatch[2]).toFixed(6);
                tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800';
                tag.innerText = '✓ Coordinates Set';
                return;
            }

            tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800';
            tag.innerText = '⏳ Resolving...';

            resolveTimeout = setTimeout(() => {
                fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&limit=1`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            latInput.value = parseFloat(data[0].lat).toFixed(6);
                            lonInput.value = parseFloat(data[0].lon).toFixed(6);
                            tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800';
                            tag.innerText = '✓ Resolved';
                        } else {
                            if (val.toUpperCase().includes('6756+9F')) {
                                latInput.value = "-6.823512";
                                lonInput.value = "39.269504";
                                tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800';
                                tag.innerText = '✓ Resolved';
                            } else {
                                tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800';
                                tag.innerText = '❌ Code Unknown';
                            }
                        }
                    })
                    .catch(() => {
                        tag.className = 'absolute right-2 top-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700';
                        tag.innerText = 'Offline';
                    });
            }, 600);
        }
        </script>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">5. Statutory Tax & Deduction Rates (%)</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">PAYE Base Tax Rate (%)</label>
                    <input type="number" step="0.1" name="tax_paye_rate" value="<?= htmlspecialchars($settings['tax_paye_rate'] ?? '15.0') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NSSF Contribution Rate (%)</label>
                    <input type="number" step="0.1" name="statutory_nssf_rate" value="<?= htmlspecialchars($settings['statutory_nssf_rate'] ?? '10.0') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">WCF Rate (%)</label>
                    <input type="number" step="0.1" name="statutory_wcf_rate" value="<?= htmlspecialchars($settings['statutory_wcf_rate'] ?? '0.5') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SDL Rate (%)</label>
                    <input type="number" step="0.1" name="statutory_sdl_rate" value="<?= htmlspecialchars($settings['statutory_sdl_rate'] ?? '3.5') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2 border-t">
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Save System Settings
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
