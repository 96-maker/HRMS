<?php
// app/services/PayrollCalculationService.php

class PayrollCalculationService {

    /**
     * Calculate NSSF contributions (Mainland Tanzania: 10% Employee, 10% Employer)
     */
    public static function calculateNssf(float $gross): array {
        $employeeNssf = round($gross * 0.10, 2);
        $employerNssf = round($gross * 0.10, 2);

        return [
            'employee' => $employeeNssf,
            'employer' => $employerNssf,
        ];
    }

    /**
     * Calculate TRA PAYE (Mainland Tanzania Monthly Tax Brackets)
     * 
     * Brackets:
     * 1. 0 - 270,000: 0%
     * 2. 270,000 - 520,000: 8% of excess over 270,000
     * 3. 520,000 - 760,000: 20,000 + 20% of excess over 520,000
     * 4. 760,000 - 1,000,000: 68,000 + 25% of excess over 760,000
     * 5. > 1,000,000: 128,000 + 30% of excess over 1,000,000
     */
    public static function calculatePaye(float $taxableIncome): float {
        if ($taxableIncome <= 270000) {
            return 0.00;
        } elseif ($taxableIncome <= 520000) {
            return round(($taxableIncome - 270000) * 0.08, 2);
        } elseif ($taxableIncome <= 760000) {
            return round(20000 + (($taxableIncome - 520000) * 0.20), 2);
        } elseif ($taxableIncome <= 1000000) {
            return round(68000 + (($taxableIncome - 760000) * 0.25), 2);
        } else {
            return round(128000 + (($taxableIncome - 1000000) * 0.30), 2);
        }
    }

    /**
     * Calculate Statutory Employer Liabilities (WCF 0.5%, SDL 3.5%)
     */
    public static function calculateStatutoryEmployer(float $gross): array {
        $wcf = round($gross * 0.005, 2); // 0.5%
        $sdl = round($gross * 0.035, 2); // 3.5%

        return [
            'wcf' => $wcf,
            'sdl' => $sdl,
        ];
    }

    /**
     * Complete payroll calculation breakdown for an employee
     */
    public static function calculateFullPayroll(float $basic, float $allowances = 0.00, float $overtime = 0.00, float $otherDeductions = 0.00): array {
        $gross = round($basic + $allowances + $overtime, 2);

        // NSSF
        $nssf = self::calculateNssf($gross);
        $employeeNssf = $nssf['employee'];
        $employerNssf = $nssf['employer'];

        // Taxable Income = Gross Pay - Employee NSSF
        $taxableIncome = max(0, $gross - $employeeNssf);

        // PAYE
        $paye = self::calculatePaye($taxableIncome);

        // Employer Statutory Liabilities
        $employerStatutory = self::calculateStatutoryEmployer($gross);
        $employerWcf = $employerStatutory['wcf'];
        $employerSdl = $employerStatutory['sdl'];

        // Net Pay = Gross - (Employee NSSF + PAYE + Other Deductions)
        $totalEmployeeDeductions = $employeeNssf + $paye + $otherDeductions;
        $netPay = max(0, round($gross - $totalEmployeeDeductions, 2));

        // Total Employer Cost = Gross + Employer NSSF + Employer WCF + Employer SDL
        $totalEmployerCost = round($gross + $employerNssf + $employerWcf + $employerSdl, 2);

        return [
            'basic_salary'        => round($basic, 2),
            'allowances'          => round($allowances, 2),
            'overtime'            => round($overtime, 2),
            'gross_salary'        => $gross,
            'employee_nssf'       => $employeeNssf,
            'employer_nssf'       => $employerNssf,
            'taxable_income'      => $taxableIncome,
            'paye'                => $paye,
            'other_deductions'    => round($otherDeductions, 2),
            'net_salary'          => $netPay,
            'employer_wcf'        => $employerWcf,
            'employer_sdl'        => $employerSdl,
            'total_employer_cost' => $totalEmployerCost,
        ];
    }
}
