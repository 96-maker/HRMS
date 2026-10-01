<?php
// app/views/dashboard/index.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<!-- Page Title Header -->
<div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Overview</h1>
        <p class="text-xs text-slate-500">Welcome back — enterprise workforce analytics and quick action panel.</p>
    </div>

</div>

<!-- 4 Key Stat Cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat 1 -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Workforce</span>
            <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <i data-lucide="users" class="size-4"></i>
            </span>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900"><?= $totalEmployees ?></span>
            <span class="text-xs font-semibold text-slate-500">Current active headcount</span>
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block">Active headcount</span>
    </div>

    <!-- Stat 2 -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Present Today</span>
            <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <i data-lucide="user-check" class="size-4"></i>
            </span>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900"><?= $presentToday ?></span>
            <span class="text-xs font-semibold text-slate-500">Recorded today</span>
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block">Live check-in log</span>
    </div>

    <!-- Stat 3 -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Approvals</span>
            <span class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <i data-lucide="calendar-clock" class="size-4"></i>
            </span>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-3xl font-extrabold text-slate-900"><?= $pendingLeave ?></span>
            <span class="text-xs font-semibold text-amber-600">Leave requests</span>
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block">Awaiting manager decision</span>
    </div>

    <!-- Stat 4 -->
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly Payroll</span>
            <span class="flex size-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <i data-lucide="wallet" class="size-4"></i>
            </span>
        </div>
        <div class="mt-3 flex items-baseline gap-2">
            <span class="text-2xl font-extrabold font-mono text-slate-900"><?= format_compact_currency($monthlyPayroll) ?></span>
        </div>
        <span class="text-[11px] text-slate-400 mt-1 block"><?= date('F Y') ?> payroll</span>
    </div>
</div>

<!-- Interactive Chart & Pending Approvals -->
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Chart Widget -->
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Attendance Trends (<?= date('F Y') ?>)</h3>
                <p class="text-xs text-slate-500">Daily breakdown of recorded attendance entries</p>
            </div>
        </div>
        <div class="h-64">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- Quick Leave Approval Panel -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Pending Leave Approvals</h3>
                <a href="<?= $baseUrl ?>/leave" class="text-xs font-semibold text-indigo-600 hover:underline">View All</a>
            </div>

            <?php if (empty($pendingLeaveList)): ?>
                <div class="py-8 text-center text-slate-400 text-xs">
                    <i data-lucide="check-circle-2" class="size-8 mx-auto mb-2 text-emerald-500 opacity-60"></i>
                    All caught up! No pending leave requests.
                </div>
            <?php else: ?>
                <ul class="divide-y divide-slate-100">
                    <?php foreach ($pendingLeaveList as $item): ?>
                        <li class="py-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs">
                                    <?= get_initials($item['first_name'] . ' ' . $item['last_name']) ?>
                                </span>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-semibold text-slate-900 truncate"><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></span>
                                    <span class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($item['leave_type_name']) ?> · <?= $item['total_days'] ?> Days</span>
                                </div>
                            </div>
                            <?php if (AuthService::hasPermission('leave.approve')): ?>
                                <div class="flex items-center gap-1">
                                    <form method="POST" action="<?= $baseUrl ?>/leave/approve" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" title="Approve" class="p-1 rounded text-emerald-600 hover:bg-emerald-50 border border-emerald-200">
                                            <i data-lucide="check" class="size-3.5"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= $baseUrl ?>/leave/reject" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" title="Reject" class="p-1 rounded text-rose-600 hover:bg-rose-50 border border-rose-200">
                                            <i data-lucide="x" class="size-3.5"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceTrend = <?= json_encode($attendanceTrend, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const labels = Object.keys(attendanceTrend);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Present',
                    data: labels.map(label => attendanceTrend[label].Present || 0),
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3
                },
                {
                    label: 'Late',
                    data: labels.map(label => attendanceTrend[label].Late || 0),
                    borderColor: '#F59E0B',
                    backgroundColor: 'transparent',
                    tension: 0.3
                },
                {
                    label: 'Absent',
                    data: labels.map(label => attendanceTrend[label].Absent || 0),
                    borderColor: '#EF4444',
                    backgroundColor: 'transparent',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
