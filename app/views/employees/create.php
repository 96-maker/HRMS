<?php
// app/views/employees/create.php
require __DIR__ . '/../layouts/header.php';
$csrfToken = CsrfMiddleware::generateToken();
$baseUrl = get_base_url();
?>

<div class="flex items-center gap-2 mb-4">
    <a href="<?= $baseUrl ?>/employees" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Directory
    </a>
</div>

<div class="max-w-3xl mx-auto rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Add New Employee Profile</h2>
    <p class="text-xs text-slate-500 mb-6">Complete all required personal, contractual, and emergency contact details.</p>

    <form method="POST" action="<?= $baseUrl ?>/employees" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Section 1: Personal Info -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">1. Personal Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">First Name *</label>
                    <input type="text" name="first_name" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Baraka">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Last Name *</label>
                    <input type="text" name="last_name" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Mushi">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Gender *</label>
                    <select name="gender" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Date of Birth *</label>
                    <input type="date" name="dob" value="1995-01-01" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Section 2: Contact Details & System User Account -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">2. Contact & System Login Account</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address (Used for Login) *</label>
                    <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="baraka.mushi@dontech.co.tz">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone Number (+255) *</label>
                    <input type="text" name="phone" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="+255715667788">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">System Username (Manual Input) *</label>
                    <input type="text" name="username" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="baraka.mushi">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">System Role / Access Level *</label>
                    <select name="role_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-semibold">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $r['slug'] === 'employee' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['name']) ?> — (<?= htmlspecialchars($r['description']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Initial Account Status *</label>
                    <select name="create_user_account" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-semibold text-amber-700">
                        <option value="pending" selected>Pending Activation (Akaunti Haijawa Active / Subiri Uamilisho)</option>
                        <option value="active">Active (Tengeneza Akaunti na Uifanye Active Mara Moja)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Initial Password <span class="font-normal text-slate-400">(required only for Active accounts)</span></label>
                    <input type="password" name="initial_password" minlength="12" autocomplete="new-password" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500 bg-white" placeholder="At least 12 characters">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700">Physical Address *</label>
                    <input type="text" name="address" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Sinza, Dar es Salaam, Tanzania">
                </div>
            </div>
        </div>

        <!-- Section 3: Employment Details -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">3. Employment & Compensation</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department *</label>
                    <select name="department_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Job Position *</label>
                    <select name="position_id" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <?php foreach ($positions as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Employment Type *</label>
                    <select name="employment_type" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        <option value="Full-time">Full-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Probation">Probation</option>
                        <option value="Intern">Intern</option>
                        <option value="Part-time">Part-time</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Pay Frequency / Cycle *</label>
                    <select name="pay_cycle" id="pay_cycle_select" onchange="updateSalaryLabels(this.value)" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="Monthly">Monthly (Permanent Staff)</option>
                        <option value="Weekly">Weekly (Weekly Wages)</option>
                        <option value="Bi-weekly">Bi-weekly (Fortnightly)</option>
                        <option value="Daily">Daily (Casual Workers / Day Rate)</option>
                        <option value="Any Day">Any Day / On-Demand (Piecework / Task Basis)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Date Joined *</label>
                    <input type="date" name="date_joined" value="<?= date('Y-m-d') ?>" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label id="basic_salary_label" class="block text-xs font-semibold text-slate-700">Basic Monthly Salary (TZS) *</label>
                    <input type="number" step="1000" name="basic_salary" value="1800000" required class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label id="allowances_label" class="block text-xs font-semibold text-slate-700">Monthly Allowances (TZS)</label>
                    <input type="number" step="1000" name="allowances" value="400000" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500">
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
                </script>
            </div>
        </div>

        <!-- Section 4: Emergency Contact -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">4. Emergency Contact</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Emergency Contact Name</label>
                    <input type="text" name="emergency_contact_name" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Joyce Mushi">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Emergency Contact Phone</label>
                    <input type="text" name="emergency_contact_phone" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="+255757445566">
                </div>
            </div>
        </div>

        <!-- Section 5: Bank Payment Details -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">5. Bank Payment Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Bank Name</label>
                    <input type="text" name="bank_name" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="CRDB Bank / NMB Bank / Absa">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Account Number</label>
                    <input type="text" name="bank_account_no" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="015200000000">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Bank Branch</label>
                    <input type="text" name="bank_branch" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Victoria Branch, Dar es Salaam">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">SWIFT / Sort Code</label>
                    <input type="text" name="swift_code" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="CORUTZT1">
                </div>
            </div>
        </div>

        <!-- Section 6: Statutory Identifiers -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">6. Statutory Identifiers (Tanzania TRA / NSSF / NIDA)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">TIN Number (TRA)</label>
                    <input type="text" name="tin_number" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="123-456-789">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NIDA National ID No.</label>
                    <input type="text" name="nida_number" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="19950101-12345-00001-12">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">NSSF Social Security No.</label>
                    <input type="text" name="nssf_number" class="mt-1 block w-full px-3 py-2 border rounded-lg text-xs font-mono focus:ring-2 focus:ring-indigo-500" placeholder="1098765432">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
            <a href="<?= $baseUrl ?>/employees" class="px-4 py-2 text-xs font-semibold border rounded-lg hover:bg-slate-50">Cancel</a>
            <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm">
                Save & Submit Employee
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
