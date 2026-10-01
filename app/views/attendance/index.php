<?php
// app/views/attendance/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Time & Attendance Tracking</h1>
        <p class="text-xs text-slate-500">Live employee check-in widget, shift logs, and punctuality monitoring.</p>
    </div>
</div>

<!-- Live Check-in Widget Card -->
<div class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
            <i data-lucide="map-pin" class="size-6"></i>
        </span>
        <div>
            <h3 class="text-base font-bold text-slate-900">Geofenced Mobile Clocking</h3>
            <p class="text-xs text-slate-500">Current Time: <?= date('H:i') ?> EAT | Max Allowed Distance: <strong><?= $geofence['radius'] ?>m</strong></p>
            <div id="geofence-status-container" class="mt-1 flex items-center gap-2 text-xs">
                <span id="geo-badge" class="px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800">
                    <i data-lucide="loader-2" class="inline size-3 animate-spin mr-1"></i> Inakadiria umbali kutoka ofisini...
                </span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <?php if (!empty($myRecord['clock_in'])): ?>
            <?php if (!empty($myRecord['clock_out'])): ?>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-200">
                    ✓ Shift Completed (<?= substr($myRecord['clock_in'], 0, 5) ?> - <?= substr($myRecord['clock_out'], 0, 5) ?>)
                </span>
            <?php else: ?>
                <?php 
                    $isOvernight = ($myRecord['date'] !== date('Y-m-d')); 
                    $shiftLabel = $isOvernight ? "Night Shift Started " . date('M d', strtotime($myRecord['date'])) . " at " . substr($myRecord['clock_in'], 0, 5) : "In: " . substr($myRecord['clock_in'], 0, 5);
                ?>
                <form id="attendance-clock-form" method="POST" action="<?= $baseUrl ?>/attendance/clock-out">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="employee_id" value="<?= $myEmpId ?>">
                    <input type="hidden" name="user_latitude" id="user_latitude">
                    <input type="hidden" name="user_longitude" id="user_longitude">
                    <button id="clock-btn" type="submit" disabled class="px-4 py-2 text-xs font-bold text-white bg-rose-600 rounded-lg hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors">
                        Clock Out Now (<?= $shiftLabel ?>)
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <form id="attendance-clock-form" method="POST" action="<?= $baseUrl ?>/attendance/clock-in">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="employee_id" value="<?= $myEmpId ?>">
                <input type="hidden" name="user_latitude" id="user_latitude">
                <input type="hidden" name="user_longitude" id="user_longitude">
                <button id="clock-btn" type="submit" disabled class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors">
                    Clock In Today (08:00 AM Shift)
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const geofenceConfig = <?= json_encode($geofence) ?>;
    const geoBadge = document.getElementById('geo-badge');
    const clockBtn = document.getElementById('clock-btn');
    const latInput = document.getElementById('user_latitude');
    const lonInput = document.getElementById('user_longitude');
    if (!geofenceConfig.enabled) {
        if (geoBadge) {
            geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800';
            geoBadge.innerHTML = '🌐 Geofencing disabled by Administrator.';
        }
        if (clockBtn) clockBtn.disabled = false;
        return;
    }

    function enableDevFallback(reasonNotice) {
        if (geoBadge) {
            geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-900 border border-amber-300';
            geoBadge.innerHTML = `⚠️ ${reasonNotice}`;
        }
        if (clockBtn) {
            clockBtn.disabled = false;
            clockBtn.title = "Development geofence bypass is enabled by server configuration.";
        }
    }

    // Development bypass is enabled only by server-side environment configuration.
    if (geofenceConfig.bypass_dev) {
        enableDevFallback("Notice: Server-side development geofence bypass is active.");
        return;
    }

    // Haversine Formula implementation in JS
    function calculateHaversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000; // Radius of Earth in meters
        const toRad = x => x * Math.PI / 180;

        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);

        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLat = position.coords.latitude;
                const userLon = position.coords.longitude;

                if (latInput) latInput.value = userLat;
                if (lonInput) lonInput.value = userLon;

                const distance = calculateHaversineMeters(userLat, userLon, geofenceConfig.latitude, geofenceConfig.longitude);
                const roundedDist = Math.round(distance);

                if (distance <= geofenceConfig.radius) {
                    if (geoBadge) {
                        geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300';
                        geoBadge.innerHTML = `✓ You are within office boundary (${roundedDist}m from office)`;
                    }
                    if (clockBtn) {
                        clockBtn.disabled = false;
                        clockBtn.title = "";
                    }
                } else {
                    if (geoBadge) {
                        geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800 border border-rose-300';
                        geoBadge.innerHTML = `⚠️ You are ${roundedDist}m away from office. Clocking disabled outside workplace.`;
                    }
                    if (clockBtn) {
                        clockBtn.disabled = true;
                        clockBtn.title = `You cannot clock in/out because you are ${roundedDist}m away from designated office area (Max allowed: ${geofenceConfig.radius}m).`;
                    }
                }
            },
            function(error) {
                let msg = "";
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        msg = "Location access was denied. Please enable GPS permissions for this site.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        msg = "GPS signal weak or unavailable.";
                        break;
                    case error.TIMEOUT:
                        msg = "Location request timed out. Retrying...";
                        break;
                    default:
                        msg = error.message || "Unable to retrieve GPS location.";
                }

                if (geoBadge) {
                    geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800 border border-rose-300';
                    geoBadge.innerHTML = `❌ ${msg}`;
                }
                if (clockBtn) clockBtn.disabled = true;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        if (geoBadge) {
            geoBadge.className = 'px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-100 text-rose-800';
            geoBadge.innerHTML = '❌ Browser does not support Geolocation.';
        }
        if (clockBtn) clockBtn.disabled = true;
    }
});
</script>

<!-- Filters Bar -->
<form method="GET" action="<?= $baseUrl ?>/attendance" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
    <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="absolute left-3 top-2.5 size-4 text-slate-400"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search employee..." class="pl-9 w-full px-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
    </div>
    <div class="flex items-center gap-2">
        <select name="status" class="px-3 py-2 text-xs border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Status</option>
            <option value="Present" <?= $statusFilter === 'Present' ? 'selected' : '' ?>>Present</option>
            <option value="Late" <?= $statusFilter === 'Late' ? 'selected' : '' ?>>Late</option>
            <option value="Absent" <?= $statusFilter === 'Absent' ? 'selected' : '' ?>>Absent</option>
            <option value="Half Day" <?= $statusFilter === 'Half Day' ? 'selected' : '' ?>>Half Day</option>
            <option value="On Leave" <?= $statusFilter === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
        </select>
        <button type="submit" class="px-3 py-2 text-xs font-semibold text-white bg-slate-800 rounded-lg hover:bg-slate-900">
            Filter Log
        </button>
    </div>
</form>

<!-- Attendance Log Table -->
<div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm border-collapse" style="width: 100%;">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="p-3.5">Employee</th>
                    <th class="p-3.5">Date</th>
                    <th class="p-3.5">Clock In</th>
                    <th class="p-3.5">Clock Out</th>
                    <th class="p-3.5">Total Hours</th>
                    <th class="p-3.5">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($records)): ?>
                    <tr>
                        <td colSpan="6" class="p-8 text-center text-slate-400 text-xs">No attendance entries recorded for today yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $att): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                        <?= get_initials($att['first_name'] . ' ' . $att['last_name']) ?>
                                    </span>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900 text-xs"><?= htmlspecialchars($att['first_name'] . ' ' . $att['last_name']) ?></span>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($att['department_name'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 text-xs text-slate-500 font-mono"><?= $att['date'] ?></td>
                            <td class="p-3.5 text-xs font-mono font-semibold text-slate-900"><?= $att['clock_in'] ? substr($att['clock_in'], 0, 5) : '--:--' ?></td>
                            <td class="p-3.5 text-xs font-mono font-semibold text-slate-900"><?= $att['clock_out'] ? substr($att['clock_out'], 0, 5) : '--:--' ?></td>
                            <td class="p-3.5 text-xs font-mono text-slate-700"><?= number_format($att['total_hours'], 1) ?> hrs</td>
                            <td class="p-3.5">
                                <?php 
                                    $st = $att['status'];
                                    $bClass = $st === 'Present' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : ($st === 'Late' ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20');
                                ?>
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap ring-1 ring-inset <?= $bClass ?>">
                                    <?= $st ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
