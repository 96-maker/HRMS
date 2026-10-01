<?php
// app/views/payroll/show.php
$name = $item['first_name'] . ' ' . $item['last_name'];
?>
<!DOCTYPE html>
<html lang="en" class="bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="p-3 sm:p-6 font-sans text-slate-900">

<?php $baseUrl = get_base_url(); ?>
<div class="max-w-3xl mx-auto mb-4 no-print flex items-center justify-between">
    <a href="<?= $baseUrl ?>/payroll" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 flex items-center gap-1">
        <i data-lucide="arrow-left" class="size-4"></i> Back to Payroll Batch
    </a>
    <button onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
        <i data-lucide="printer" class="size-4"></i> Print Official Payslip
    </button>
</div>

<!-- Official Payslip Card -->
<div class="print-card max-w-3xl mx-auto bg-white border border-slate-200 rounded-2xl p-4 sm:p-8 shadow-lg space-y-6">
    <!-- Company & Document Header -->
    <div class="flex items-start justify-between border-b border-slate-200 pb-6">
        <div>
            <h1 class="text-xl font-bold text-indigo-900"><?= htmlspecialchars($company['company_name'] ?? 'DonTech Solutions Ltd') ?></h1>
            <p class="text-xs text-slate-500 max-w-xs mt-1"><?= htmlspecialchars($company['company_address'] ?? 'Plot 45, Bagamoyo Rd, Dar es Salaam') ?></p>
            <p class="text-xs text-slate-400 mt-0.5">TIN: <?= htmlspecialchars($company['company_tin'] ?? '109-482-771') ?> | VRN: <?= htmlspecialchars($company['company_vrn'] ?? '400-881-229') ?></p>
        </div>
        <div class="text-right">
            <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs uppercase tracking-wider rounded-md border border-indigo-200">
                Official Payslip
            </span>
            <p class="text-xs font-semibold text-slate-700 mt-2"><?= htmlspecialchars($item['period_name']) ?></p>
            <p class="text-xs text-slate-500 font-semibold mt-0.5">Issued: <?= date('d M Y', strtotime($item['created_at'] ?? 'now')) ?></p>
        </div>
    </div>

    <!-- Employee Information Grid -->
    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl text-xs border border-slate-100">
        <div>
            <span class="text-slate-400 block">Employee Name</span>
            <span class="font-bold text-sm text-slate-900"><?= htmlspecialchars($name) ?></span>
        </div>
        <div>
            <span class="text-slate-400 block">Employee Code</span>
            <span class="font-mono font-semibold text-slate-800"><?= htmlspecialchars($item['employee_code']) ?></span>
        </div>
        <div>
            <span class="text-slate-400 block">Department / Role</span>
            <span class="font-medium text-slate-800"><?= htmlspecialchars($item['department_name'] ?? 'N/A') ?> — <?= htmlspecialchars($item['position_title'] ?? 'Staff') ?></span>
        </div>
        <div>
            <span class="text-slate-400 block">NSSF Pension No. / TIN</span>
            <span class="font-mono text-slate-800"><?= htmlspecialchars($item['nssf_number'] ?? 'N/A') ?> / <?= htmlspecialchars($item['tin_number'] ?? 'N/A') ?></span>
        </div>
    </div>

    <?php if (!empty($item['notes'])): ?>
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs">
            <span class="font-bold text-amber-900 block mb-0.5">📝 Description / Work Notes (Maelezo ya Kazi / Vibarua):</span>
            <p class="text-amber-800 italic"><?= htmlspecialchars($item['notes']) ?></p>
        </div>
    <?php endif; ?>

    <!-- Earnings & Statutory Deductions Table -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Earnings -->
        <div class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b pb-1">Earnings Breakdown</h3>
            <div class="flex justify-between text-xs py-1">
                <span class="text-slate-600">
                    <?php 
                        $cycle = $item['pay_cycle'] ?? 'Monthly';
                        if ($cycle === 'Daily') echo 'Basic Daily Rate / Wage';
                        elseif ($cycle === 'Weekly') echo 'Basic Weekly Wage';
                        elseif ($cycle === 'Bi-weekly') echo 'Basic Bi-weekly Wage';
                        elseif ($cycle === 'Any Day') echo 'Task / Piecework Wage';
                        else echo 'Basic Monthly Salary';
                    ?>
                </span>
                <span class="font-mono font-semibold text-slate-900"><?= format_currency($item['basic_salary']) ?></span>
            </div>
            <div class="flex justify-between text-xs py-1">
                <span class="text-slate-600">Allowances & Extras</span>
                <span class="font-mono font-semibold text-slate-900"><?= format_currency($item['allowances']) ?></span>
            </div>
            <?php if (!empty($item['overtime']) && $item['overtime'] > 0): ?>
                <div class="flex justify-between text-xs py-1">
                    <span class="text-slate-600">Overtime / Extra Work</span>
                    <span class="font-mono font-semibold text-slate-900"><?= format_currency($item['overtime']) ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between text-xs py-1 border-t pt-2 font-bold text-slate-900">
                <span>Gross Earnings</span>
                <span class="font-mono"><?= format_currency($item['gross_salary']) ?></span>
            </div>
        </div>

        <!-- Deductions & Tax Calculation Breakdown -->
        <div class="space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 border-b pb-1">Employee Deductions & Taxes</h3>
            <div class="flex justify-between text-xs py-1">
                <span class="text-slate-600">NSSF Employee Contribution (10%)</span>
                <span class="font-mono font-semibold text-slate-900">- <?= format_currency($item['statutory_deduction']) ?></span>
            </div>
            <div class="flex justify-between text-xs py-1 bg-slate-50 px-2 py-1 rounded border border-slate-100 font-semibold text-slate-700">
                <span>Taxable Pay (Gross - NSSF 10%)</span>
                <span class="font-mono"><?= format_currency(max(0, $item['gross_salary'] - $item['statutory_deduction'])) ?></span>
            </div>
            <div class="flex justify-between text-xs py-1">
                <span class="text-slate-600">TRA PAYE Tax (Progressive Rate)</span>
                <span class="font-mono font-semibold text-rose-600">- <?= format_currency($item['tax_deduction']) ?></span>
            </div>
            <?php if (!empty($item['other_deductions']) && $item['other_deductions'] > 0): ?>
                <div class="flex justify-between text-xs py-1">
                    <span class="text-slate-600">Absence / Fine Deduction</span>
                    <span class="font-mono font-semibold text-rose-600">- <?= format_currency($item['other_deductions']) ?></span>
                </div>
            <?php endif; ?>
            <div class="flex justify-between text-xs py-1 border-t pt-2 font-bold text-rose-600">
                <span>Total Statutory Deductions</span>
                <span class="font-mono">- <?= format_currency($item['tax_deduction'] + $item['statutory_deduction'] + ($item['other_deductions'] ?? 0)) ?></span>
            </div>
        </div>
    </div>

    <!-- Employer Statutory Contributions & Cost to Company (CTC) Section -->
    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
        <h4 class="font-bold text-slate-700 uppercase tracking-wider text-[11px] flex items-center justify-between border-b pb-1.5">
            <span>MICHANGO YA MWAJIRI (Employer Statutory Contributions)</span>
            <span class="text-indigo-600 font-mono">Cost to Company (CTC)</span>
        </h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
            <div>
                <span class="text-slate-400 block text-[11px]">Employer NSSF (10%)</span>
                <span class="font-mono font-semibold text-slate-800"><?= format_currency($item['employer_nssf'] ?? ($item['gross_salary'] * 0.10)) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">WCF Levy (0.5%)</span>
                <span class="font-mono font-semibold text-slate-800"><?= format_currency($item['employer_wcf'] ?? ($item['gross_salary'] * 0.005)) ?></span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px]">SDL Levy (3.5%)</span>
                <span class="font-mono font-semibold text-slate-800"><?= format_currency($item['employer_sdl'] ?? ($item['gross_salary'] * 0.035)) ?></span>
            </div>
            <div class="bg-indigo-50 p-2 rounded-lg border border-indigo-100">
                <span class="text-indigo-600 font-bold block text-[11px]">Total Employer CTC</span>
                <span class="font-mono font-bold text-indigo-900"><?= format_currency($item['gross_salary'] + ($item['employer_nssf'] ?? ($item['gross_salary'] * 0.10)) + ($item['employer_wcf'] ?? ($item['gross_salary'] * 0.005)) + ($item['employer_sdl'] ?? ($item['gross_salary'] * 0.035))) ?></span>
            </div>
        </div>
    </div>

    <!-- Net Take Home Highlight -->
    <div class="flex items-center justify-between bg-indigo-600 text-white p-5 rounded-xl shadow-md">
        <div>
            <span class="text-xs uppercase font-bold tracking-wider text-indigo-200 block">Net Take-Home Salary</span>
            <span class="text-2xl font-extrabold font-mono"><?= format_currency($item['net_salary']) ?></span>
        </div>
        <div class="text-right text-xs text-indigo-100">
            <span>Direct Bank Disbursement</span>
            <p class="font-mono text-[11px] opacity-90">
                <?php if (!empty($item['bank_name'])): ?>
                    <?= htmlspecialchars($item['bank_name']) ?> — Acc <?= htmlspecialchars($item['bank_account_no'] ?? 'N/A') ?>
                <?php else: ?>
                    Cash / Direct Transfer
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="text-[10px] text-slate-400 text-center border-t pt-4">
        Generated securely via DonTech PeopleSuite HRMS | Technology Partner: DonTech Solutions Ltd
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
