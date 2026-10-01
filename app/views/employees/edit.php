<?php
// app/views/employees/edit.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
$fullName = $employee['first_name'] . ' ' . $employee['last_name'];
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/employees/view?id=<?= $employee['id'] ?>" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Profile
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Employee Profile: <?= htmlspecialchars($fullName) ?></h2>
            <p class="text-xs text-slate-500">Update personal info, compensation, contract terms, or status.</p>
        </div>
        <span class="font-mono text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg border border-indigo-200">
            <?= htmlspecialchars($employee['employee_code']) ?>
        </span>
    </div>

    <form method="POST" action="<?= $baseUrl ?>/employees/update" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" name="id" value="<?= $employee['id'] ?>">

        <!-- Section 1: Personal Info -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">First Name *</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($employee['first_name']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Last Name *</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($employee['last_name']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Gender *</label>
                    <select name="gender" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Male" <?= $employee['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $employee['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Date of Birth *</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($employee['dob']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Info -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Contact Details</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone Number (+255) *</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($employee['phone']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700">Physical Address *</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($employee['address']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Section 3: Employment Details -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">3. Employment, Compensation & Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department *</label>
                    <select name="department_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $employee['department_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Job Position *</label>
                    <select name="position_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($positions as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $employee['position_id'] == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Employment Type *</label>
                    <select name="employment_type" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Full-time" <?= $employee['employment_type'] === 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                        <option value="Contract" <?= $employee['employment_type'] === 'Contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="Probation" <?= $employee['employment_type'] === 'Probation' ? 'selected' : '' ?>>Probation</option>
                        <option value="Intern" <?= $employee['employment_type'] === 'Intern' ? 'selected' : '' ?>>Intern</option>
                        <option value="Part-time" <?= $employee['employment_type'] === 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Pay Frequency / Cycle *</label>
                    <select name="pay_cycle" id="pay_cycle_select" onchange="updateSalaryLabels(this.value)" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="Monthly" <?= ($employee['pay_cycle'] ?? 'Monthly') === 'Monthly' ? 'selected' : '' ?>>Monthly (Permanent Staff)</option>
                        <option value="Weekly" <?= ($employee['pay_cycle'] ?? '') === 'Weekly' ? 'selected' : '' ?>>Weekly (Weekly Wages)</option>
                        <option value="Bi-weekly" <?= ($employee['pay_cycle'] ?? '') === 'Bi-weekly' ? 'selected' : '' ?>>Bi-weekly (Fortnightly)</option>
                        <option value="Daily" <?= ($employee['pay_cycle'] ?? '') === 'Daily' ? 'selected' : '' ?>>Daily (Casual Workers / Day Rate)</option>
                        <option value="Any Day" <?= ($employee['pay_cycle'] ?? '') === 'Any Day' ? 'selected' : '' ?>>Any Day / On-Demand (Piecework / Task Basis)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Date Joined *</label>
                    <input type="date" name="date_joined" value="<?= htmlspecialchars($employee['date_joined']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Employment Status *</label>
                    <select name="status" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Active" <?= $employee['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="On Leave" <?= $employee['status'] === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                        <option value="Inactive" <?= $employee['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="Terminated" <?= $employee['status'] === 'Terminated' ? 'selected' : '' ?>>Terminated</option>
                    </select>
                </div>
                <div>
                    <label id="basic_salary_label" class="block text-xs font-semibold text-slate-700">Basic Monthly Salary (TZS) *</label>
                    <input type="number" step="1000" name="basic_salary" value="<?= htmlspecialchars($employee['basic_salary']) ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label id="allowances_label" class="block text-xs font-semibold text-slate-700">Monthly Allowances (TZS)</label>
                    <input type="number" step="1000" name="allowances" value="<?= htmlspecialchars($employee['allowances']) ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>

                <script>
                function updateSalaryLabels(cycle) {
                    const basicLabel = document.getElementById('basic_salary_label');
                    const allowancesLabel = document.getElementById('allowances_label');
                    
                    if (cycle === 'Daily') {
                        basicLabel.innerText = 'Basic Daily Rate (TZS) *';
                        allowancesLabel.innerText = 'Daily Allowances (TZS)';
                    } else if (cycle === 'Weekly') {
                        basicLabel.innerText = 'Basic Weekly Wage (TZS) *';
                        allowancesLabel.innerText = 'Weekly Allowances (TZS)';
                    } else if (cycle === 'Bi-weekly') {
                        basicLabel.innerText = 'Basic Bi-weekly Wage (TZS) *';
                        allowancesLabel.innerText = 'Bi-weekly Allowances (TZS)';
                    } else if (cycle === 'Any Day') {
                        basicLabel.innerText = 'Task / Piecework Rate (TZS) *';
                        allowancesLabel.innerText = 'Additional Task Allowances (TZS)';
                    } else {
                        basicLabel.innerText = 'Basic Monthly Salary (TZS) *';
                        allowancesLabel.innerText = 'Monthly Allowances (TZS)';
                    }
                }
                window.addEventListener('DOMContentLoaded', function() {
                    const cycleVal = document.getElementById('pay_cycle_select').value;
                    updateSalaryLabels(cycleVal);
                });
                </script>
            </div>
        </div>

        <!-- Section 4: Emergency Contact -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">4. Emergency Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($employee['emergency_contact_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" value="<?= htmlspecialchars($employee['emergency_contact_phone'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Section 5: Bank Payment Details -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">5. Bank Payment Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Bank Name</label>
                    <input type="text" name="bank_name" value="<?= htmlspecialchars($employee['bank_name'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="CRDB Bank / NMB Bank">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Account Number</label>
                    <input type="text" name="bank_account_no" value="<?= htmlspecialchars($employee['bank_account_no'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Bank Branch</label>
                    <input type="text" name="bank_branch" value="<?= htmlspecialchars($employee['bank_branch'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SWIFT / Sort Code</label>
                    <input type="text" name="swift_code" value="<?= htmlspecialchars($employee['swift_code'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Section 6: Statutory Identifiers -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">6. Statutory Identifiers (Tanzania TRA / NSSF / NIDA)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">TIN Number (TRA)</label>
                    <input type="text" name="tin_number" value="<?= htmlspecialchars($employee['tin_number'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NIDA National ID No.</label>
                    <input type="text" name="nida_number" value="<?= htmlspecialchars($employee['nida_number'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NSSF Social Security No.</label>
                    <input type="text" name="nssf_number" value="<?= htmlspecialchars($employee['nssf_number'] ?? '') ?>" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/employees/view?id=<?= $employee['id'] ?>" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
                <i data-lucide="save" class="size-4"></i> Save & Update Employee Profile
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
