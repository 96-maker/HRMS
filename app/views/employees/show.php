<?php
// app/views/employees/show.php
require __DIR__ . '/../layouts/header.php';
$name = $employee['first_name'] . ' ' . $employee['last_name'];
$baseUrl = get_base_url();
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/employees" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Employee Directory
    </a>
</div>

<!-- Profile Card Header -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6 mb-6">
    <div class="flex items-center gap-4">
        <span class="flex size-16 items-center justify-center rounded-2xl bg-indigo-600 text-white text-xl font-bold">
            <?= get_initials($name) ?>
        </span>
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars($name) ?></h1>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                    <?= $employee['status'] ?>
                </span>
            </div>
            <p class="text-xs text-slate-500 flex items-center gap-2">
                <i data-lucide="briefcase" class="size-3.5"></i> <?= htmlspecialchars($employee['position_title']) ?> · <?= htmlspecialchars($employee['department_name']) ?>
            </p>
            <p class="text-xs text-slate-400 font-mono">
                Code: <?= htmlspecialchars($employee['employee_code']) ?> · Joined <?= format_date($employee['date_joined']) ?>
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= $baseUrl ?>/employees/edit?id=<?= $employee['id'] ?>" class="px-3 py-2 text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 rounded-lg hover:bg-slate-200 shadow-2xs flex items-center gap-1.5 transition-colors">
            <i data-lucide="pencil" class="size-4 text-indigo-600"></i> Edit Profile
        </a>
        <a href="<?= $baseUrl ?>/employees/export-pdf?id=<?= $employee['id'] ?>" target="_blank" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5 transition-colors">
            <i data-lucide="download" class="size-4"></i> Download PDF Profile
        </a>
    </div>
</div>

<!-- Grid Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Contact Info -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="mail" class="size-4 text-indigo-600"></i> Contact & Address Details
        </h3>
        <dl class="grid grid-cols-1 gap-3 text-xs">
            <div><dt class="text-slate-400">Email Address</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($employee['email']) ?></dd></div>
            <div><dt class="text-slate-400">Phone Number</dt><dd class="font-semibold text-slate-800"><?= format_phone($employee['phone']) ?></dd></div>
            <div><dt class="text-slate-400">Physical Address</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($employee['address']) ?></dd></div>
            <div><dt class="text-slate-400">Gender / DOB</dt><dd class="font-semibold text-slate-800"><?= $employee['gender'] ?> · <?= format_date($employee['dob']) ?></dd></div>
        </dl>
    </div>

    <!-- Compensation & Employment -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="wallet" class="size-4 text-indigo-600"></i> Contract & Salary Specifications
        </h3>
        <dl class="grid grid-cols-2 gap-3 text-xs">
            <div><dt class="text-slate-400">Employment Type</dt><dd class="font-semibold text-slate-800"><?= $employee['employment_type'] ?></dd></div>
            <div><dt class="text-slate-400">Direct Manager</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars(($employee['manager_fn'] ?? 'N/A') . ' ' . ($employee['manager_ln'] ?? '')) ?></dd></div>
            <div><dt class="text-slate-400">Basic Monthly Salary</dt><dd class="font-mono font-bold text-slate-900"><?= format_currency($employee['basic_salary']) ?></dd></div>
            <div><dt class="text-slate-400">Monthly Allowances</dt><dd class="font-mono font-bold text-slate-900"><?= format_currency($employee['allowances']) ?></dd></div>
        </dl>
    </div>

    <!-- Bank & Statutory Identifiers -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="landmark" class="size-4 text-indigo-600"></i> Bank Payment Details & Statutory Identifiers
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
            <dl class="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div><dt class="text-slate-400">Bank Name</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($employee['bank_name'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-slate-400">Account Number</dt><dd class="font-mono font-semibold text-indigo-700"><?= htmlspecialchars($employee['bank_account_no'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-slate-400">Bank Branch</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($employee['bank_branch'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-slate-400">SWIFT / Sort Code</dt><dd class="font-mono text-slate-800"><?= htmlspecialchars($employee['swift_code'] ?: 'N/A') ?></dd></div>
            </dl>
            <dl class="grid grid-cols-3 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div><dt class="text-slate-400">TIN Number (TRA)</dt><dd class="font-mono font-bold text-slate-900"><?= htmlspecialchars($employee['tin_number'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-slate-400">NIDA National ID</dt><dd class="font-mono font-bold text-slate-900"><?= htmlspecialchars($employee['nida_number'] ?: 'N/A') ?></dd></div>
                <div><dt class="text-slate-400">NSSF Pension No.</dt><dd class="font-mono font-bold text-slate-900"><?= htmlspecialchars($employee['nssf_number'] ?: 'N/A') ?></dd></div>
            </dl>
        </div>
    </div>
</div>

<!-- Tabs for Attendance, Leave & Payslips -->
<div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 mb-4">Historical Activity & Linked Records</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="p-4 border rounded-lg bg-slate-50">
            <span class="font-semibold text-slate-700 block mb-2">Recent Attendance</span>
            <span class="text-xl font-bold text-slate-900"><?= count($attendance) ?> Entries Logged</span>
        </div>
        <div class="p-4 border rounded-lg bg-slate-50">
            <span class="font-semibold text-slate-700 block mb-2">Leave Applications</span>
            <span class="text-xl font-bold text-slate-900"><?= count($leaves) ?> Requests</span>
        </div>
        <div class="p-4 border rounded-lg bg-slate-50">
            <span class="font-semibold text-slate-700 block mb-2">Issued Payslips</span>
            <span class="text-xl font-bold text-slate-900"><?= count($payslips) ?> Batches</span>
        </div>
    </div>
</div>

<script>
function downloadProfilePDF() {
    const opt = {
        margin:       [0.4, 0.4, 0.4, 0.4],
        filename:     'employee_profile_<?= strtolower(str_replace(' ', '_', $name)) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(document.querySelector('main') || document.body).save();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
