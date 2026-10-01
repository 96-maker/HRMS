<?php
// app/views/leave/apply.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/leave" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Leave Management
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Submit Statutory Leave Application</h2>
    <p class="text-xs text-slate-500 mb-6">Select employee, leave category, duration dates, and detailed justification for line manager review.</p>

    <form method="POST" action="<?= $baseUrl ?>/leave/apply" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Applicant & Category Selection</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Employee *</label>
                    <select name="employee_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($employees as $e): ?>
                            <?php if (AuthService::hasRole('employee') && (int)$e['id'] !== (int)($myEmpId ?? 0)) continue; ?>
                            <option value="<?= $e['id'] ?>" <?= (int)$e['id'] === (int)($myEmpId ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Leave Category *</label>
                    <select name="leave_type_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($leaveTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>"><?= htmlspecialchars($lt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Duration & Justification Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Leave Type Scope *</label>
                    <select name="duration_unit" onchange="toggleDurationInputs(this.value)" class="w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="days" selected>Full Multi-Day Leave (Siku)</option>
                        <option value="minutes">Short Leave / Hourly Permission (Masaa/Dakika)</option>
                    </select>
                </div>
            </div>

            <div id="days_inputs" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Start Date *</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">End Date *</label>
                    <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+4 days')) ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div id="minutes_inputs" class="hidden grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Permission Duration (Minutes) *</label>
                    <input type="number" name="duration_minutes" value="2" min="1" max="1440" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 font-medium" placeholder="Specify duration in minutes...">
                    <span class="text-[11px] text-slate-400">Suitable for short leave requests, official permissions, or emergency time-offs.</span>
                </div>
            </div>

            <script>
                function toggleDurationInputs(unit) {
                    if (unit === 'minutes') {
                        document.getElementById('days_inputs').classList.add('hidden');
                        document.getElementById('minutes_inputs').classList.remove('hidden');
                        document.getElementById('minutes_inputs').classList.add('grid');
                    } else {
                        document.getElementById('minutes_inputs').classList.add('hidden');
                        document.getElementById('days_inputs').classList.remove('hidden');
                    }
                }
            </script>
            <div>
                <label class="block text-xs font-semibold text-slate-700">Justification Reason *</label>
                <textarea name="reason" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" rows="3" placeholder="State official reason for leave request..."></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/leave" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Submit Leave Application
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
