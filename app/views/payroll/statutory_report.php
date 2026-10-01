<?php
// app/views/payroll/statutory_report.php
require __DIR__ . '/../layouts/header.php';
$baseUrl = get_base_url();

$periodName = date('F Y', mktime(0, 0, 0, (int)$period['month'], 1, (int)$period['year']));
$cName = $company['company_name'] ?? 'DonTech Solutions Ltd';
$cTin  = $company['company_tin'] ?? '109-482-771';

$totBasic = 0;
$totGross = 0;
$totNssfEmp = 0;
$totTaxable = 0;
$totPaye = 0;
$totNssfEmployer = 0;
$totWcf = 0;
$totSdl = 0;

foreach ($items as $it) {
    $bs  = (float)$it['basic_salary'];
    $gr  = (float)$it['gross_salary'];
    $ns  = (float)$it['statutory_deduction'];
    $tx  = max(0, $gr - $ns);
    $py  = (float)$it['tax_deduction'];
    $en  = (float)($it['employer_nssf'] ?? ($gr * 0.10));
    $wcf = (float)($it['employer_wcf'] ?? ($gr * 0.005));
    $sdl = (float)($it['employer_sdl'] ?? ($gr * 0.035));

    $totBasic += $bs;
    $totGross += $gr;
    $totNssfEmp += $ns;
    $totTaxable += $tx;
    $totPaye += $py;
    $totNssfEmployer += $en;
    $totWcf += $wcf;
    $totSdl += $sdl;
}

$totNssfRemittance = $totNssfEmp + $totNssfEmployer;
$totEmployerCost   = $totGross + $totNssfEmployer + $totWcf + $totSdl;
?>

<style>
@media print {
    /* Hide non-printable layout elements */
    header, sidebar, nav, aside, .no-print, #flash-banner {
        display: none !important;
    }

    /* Reset layout height and scrolling constraints so full document height prints across pages */
    html, body {
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    div, main, section {
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
    }

    .print-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    table {
        page-break-inside: auto;
        width: 100% !important;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }
}
</style>

<div class="flex items-center justify-between mb-6 no-print">
    <div class="flex items-center gap-2">
        <a href="<?= $baseUrl ?>/payroll?month=<?= $period['month'] ?>&year=<?= $period['year'] ?>" class="text-xs font-semibold text-slate-500 hover:text-indigo-600 flex items-center gap-1">
            <i data-lucide="arrow-left" class="size-4"></i> Back to Payroll Batch
        </a>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= $baseUrl ?>/payroll/reports/statutory-excel?id=<?= $period['id'] ?>" class="px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 shadow-2xs flex items-center gap-1.5 transition-colors">
            <i data-lucide="file-spreadsheet" class="size-4 text-emerald-600"></i> Export to Excel (.xls)
        </a>
        <button onclick="window.print()" class="px-4 py-2 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="printer" class="size-4"></i> Print / Save PDF
        </button>
    </div>
</div>

<!-- Header Card -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($cName) ?></h1>
            <p class="text-xs text-slate-500 mt-0.5">TIN: <span class="font-mono font-semibold text-slate-700"><?= htmlspecialchars($cTin) ?></span> | Official Statutory Returns & Compliance Summary</p>
        </div>
        <div class="text-right">
            <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 font-bold text-xs uppercase tracking-wider rounded-md border border-indigo-200">
                <?= htmlspecialchars($periodName) ?> Return
            </span>
            <p class="text-[11px] text-slate-400 mt-1">Generated: <?= date('d M Y H:i:s') ?></p>
        </div>
    </div>

    <!-- Executive Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">TRA PAYE Tax Total</span>
            <span class="text-lg font-bold font-mono text-rose-600 mt-1 block"><?= format_currency($totPaye) ?></span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Monthly TRA Remittance</span>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">Total NSSF Remittance (20%)</span>
            <span class="text-lg font-bold font-mono text-indigo-700 mt-1 block"><?= format_currency($totNssfRemittance) ?></span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Employee 10% + Employer 10%</span>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">WCF + SDL Levies</span>
            <span class="text-lg font-bold font-mono text-amber-700 mt-1 block"><?= format_currency($totWcf + $totSdl) ?></span>
            <span class="text-[10px] text-slate-400 block mt-0.5">WCF (0.5%) + SDL (3.5%)</span>
        </div>
        <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200">
            <span class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider block">Grand Total Employer CTC</span>
            <span class="text-lg font-bold font-mono text-indigo-950 mt-1 block"><?= format_currency($totEmployerCost) ?></span>
            <span class="text-[10px] text-indigo-600 block mt-0.5">Gross + All Employer Liabilities</span>
        </div>
    </div>
</div>

<!-- SECTION 1: TRA PAYE MONTHLY RETURN SCHEDULE -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="receipt" class="size-4 text-indigo-600"></i> 1. TRA PAYE Monthly Return Schedule (Income Tax)
        </h2>
        <span class="text-xs font-mono font-bold text-rose-600">Total TRA PAYE: <?= format_currency($totPaye) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                    <th class="p-2.5">S/N</th>
                    <th class="p-2.5">Employee Name</th>
                    <th class="p-2.5">TIN Number</th>
                    <th class="p-2.5 text-right">Basic Salary</th>
                    <th class="p-2.5 text-right">Gross Salary</th>
                    <th class="p-2.5 text-right">Employee NSSF (10%)</th>
                    <th class="p-2.5 text-right">Taxable Income</th>
                    <th class="p-2.5 text-right">PAYE Tax Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $sn = 1; foreach ($items as $it): 
                    $bs = (float)$it['basic_salary'];
                    $gr = (float)$it['gross_salary'];
                    $ns = (float)$it['statutory_deduction'];
                    $tx = max(0, $gr - $ns);
                    $py = (float)$it['tax_deduction'];
                ?>
                    <tr>
                        <td class="p-2.5 text-slate-400 font-mono"><?= $sn++ ?></td>
                        <td class="p-2.5 font-bold text-slate-900"><?= htmlspecialchars($it['first_name'] . ' ' . $it['last_name']) ?></td>
                        <td class="p-2.5 font-mono text-slate-700"><?= htmlspecialchars($it['tin_number'] ?: 'N/A') ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($bs, 2) ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($gr, 2) ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($ns, 2) ?></td>
                        <td class="p-2.5 font-mono text-right font-semibold text-slate-900"><?= number_format($tx, 2) ?></td>
                        <td class="p-2.5 font-mono text-right font-bold text-rose-600"><?= number_format($py, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                    <td colspan="3" class="p-2.5 text-right text-slate-900">GRAND TOTAL:</td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totBasic, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totGross, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totNssfEmp, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totTaxable, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-rose-600 text-sm"><?= number_format($totPaye, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 2: NSSF MONTHLY CONTRIBUTION SCHEDULE -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="shield-check" class="size-4 text-indigo-600"></i> 2. NSSF Monthly Contribution Schedule (20% Combined Remittance)
        </h2>
        <span class="text-xs font-mono font-bold text-indigo-700">Total NSSF: <?= format_currency($totNssfRemittance) ?></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                <tr>
                    <th class="p-2.5">S/N</th>
                    <th class="p-2.5">Employee Name</th>
                    <th class="p-2.5">NSSF Number</th>
                    <th class="p-2.5 text-right">Gross Salary</th>
                    <th class="p-2.5 text-right">Employee Cont. (10%)</th>
                    <th class="p-2.5 text-right">Employer Cont. (10%)</th>
                    <th class="p-2.5 text-right">Total Remittance (20%)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php $sn = 1; foreach ($items as $it): 
                    $gr = (float)$it['gross_salary'];
                    $ne = (float)$it['statutory_deduction'];
                    $er = (float)($it['employer_nssf'] ?? ($gr * 0.10));
                    $totRem = $ne + $er;
                ?>
                    <tr>
                        <td class="p-2.5 text-slate-400 font-mono"><?= $sn++ ?></td>
                        <td class="p-2.5 font-bold text-slate-900"><?= htmlspecialchars($it['first_name'] . ' ' . $it['last_name']) ?></td>
                        <td class="p-2.5 font-mono text-slate-700"><?= htmlspecialchars($it['nssf_number'] ?: 'N/A') ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($gr, 2) ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($ne, 2) ?></td>
                        <td class="p-2.5 font-mono text-right text-slate-700"><?= number_format($er, 2) ?></td>
                        <td class="p-2.5 font-mono text-right font-bold text-indigo-700"><?= number_format($totRem, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="bg-slate-50 font-bold border-t-2 border-slate-200">
                    <td colspan="3" class="p-2.5 text-right text-slate-900">GRAND TOTAL:</td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totGross, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totNssfEmp, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-slate-900"><?= number_format($totNssfEmployer, 2) ?></td>
                    <td class="p-2.5 font-mono text-right text-indigo-700 text-sm"><?= number_format($totNssfRemittance, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 3: EMPLOYER LIABILITY SUMMARY (WCF, SDL, CTC) -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
    <div class="border-b border-slate-100 pb-3 mb-4">
        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <i data-lucide="building" class="size-4 text-indigo-600"></i> 3. Employer Statutory Liability & Cost to Company (CTC) Summary
        </h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
            <span class="text-slate-500 font-bold uppercase block text-[11px]">WCF Levy (Workers Comp Fund - 0.5%)</span>
            <span class="text-xl font-bold font-mono text-slate-900 block"><?= format_currency($totWcf) ?></span>
            <p class="text-[11px] text-slate-400">Calculated as 0.5% of total Gross Payroll across all employees.</p>
        </div>
        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
            <span class="text-slate-500 font-bold uppercase block text-[11px]">SDL Levy (Skills Dev Levy - 3.5%)</span>
            <span class="text-xl font-bold font-mono text-slate-900 block"><?= format_currency($totSdl) ?></span>
            <p class="text-[11px] text-slate-400">Calculated as 3.5% of total Gross Payroll across all employees.</p>
        </div>
        <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200 space-y-1">
            <span class="text-indigo-700 font-bold uppercase block text-[11px]">Grand Total Employer Cost (CTC)</span>
            <span class="text-xl font-bold font-mono text-indigo-950 block"><?= format_currency($totEmployerCost) ?></span>
            <p class="text-[11px] text-indigo-600">Gross (<?= format_currency($totGross) ?>) + Employer NSSF + WCF + SDL.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
