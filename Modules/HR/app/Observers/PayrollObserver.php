<?php

namespace Modules\HR\Observers;

use Modules\HR\Models\Payroll;

class PayrollObserver
{
    /**
     * Handle the Payroll "creating" event.
     * Generate payroll number and calculate totals automatically when creating a new payroll.
     */
    public function creating(Payroll $payroll): void
    {
        if (empty($payroll->payroll_number)) {
            $payroll->payroll_number = $this->generatePayrollNumber($payroll->company_id, $payroll->pay_date);
        }
    }

    /**
     * Handle the Payroll "saving" event.
     */
    public function saving(Payroll $payroll): void
    {
        $this->calculatePayrollTotals($payroll);
    }

    /**
     * Calculate gross salary, total deductions, and net salary.
     */
    private function calculatePayrollTotals(Payroll $payroll): void
    {

        // Calculate gross salary (sum of all salary components)
        $grossSalary = $payroll->base_salary
            ->plus($payroll->overtime_amount)
            ->plus($payroll->housing_allowance)
            ->plus($payroll->transport_allowance)
            ->plus($payroll->meal_allowance)
            ->plus($payroll->other_allowances)
            ->plus($payroll->bonus)
            ->plus($payroll->commission);

        // Calculate total deductions (sum of all deduction components)
        $totalDeductions = $payroll->income_tax
            ->plus($payroll->social_security)
            ->plus($payroll->health_insurance)
            ->plus($payroll->pension_contribution)
            ->plus($payroll->other_deductions);

        // Calculate net salary (gross salary minus total deductions)
        $netSalary = $grossSalary->minus($totalDeductions);

        // Set the calculated values
        $payroll->gross_salary = $grossSalary;
        $payroll->total_deductions = $totalDeductions;
        $payroll->net_salary = $netSalary;
    }

    /**
     * Generate a unique payroll number for the company.
     */
    private function generatePayrollNumber(int $companyId, mixed $payDate): string
    {
        $prefix = 'PAY';
        $date = $payDate instanceof \Carbon\Carbon ? $payDate : \Carbon\Carbon::parse($payDate);
        $year = $date->year;
        $month = $date->format('m');

        // Get the next sequential number for this month
        $lastPayroll = Payroll::where('company_id', $companyId)
            ->where('payroll_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('payroll_number', 'desc')
            ->first();

        if ($lastPayroll) {
            $lastNumber = (int) substr($lastPayroll->payroll_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.$year.$month.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
