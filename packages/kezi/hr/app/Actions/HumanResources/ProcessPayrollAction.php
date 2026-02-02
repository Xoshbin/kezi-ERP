<?php

namespace Kezi\HR\Actions\HumanResources;

use Brick\Money\Money;
use Exception;
use Illuminate\Support\Facades\DB;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use Kezi\HR\Models\Payroll;

class ProcessPayrollAction
{
    public function __construct(
        protected CreatePayrollLineAction $createPayrollLineAction,
    ) {}

    public function execute(ProcessPayrollDTO $processPayrollDTO): Payroll
    {
        return DB::transaction(function () use ($processPayrollDTO) {
            $currency = Currency::findOrFail($processPayrollDTO->currency_id);

            // Convert Money fields if they're strings
            $baseSalary = $processPayrollDTO->base_salary instanceof Money
                ? $processPayrollDTO->base_salary
                : Money::of($processPayrollDTO->base_salary, $currency->code);

            $overtimeAmount = $processPayrollDTO->overtime_amount instanceof Money
                ? $processPayrollDTO->overtime_amount
                : Money::of($processPayrollDTO->overtime_amount, $currency->code);

            $housingAllowance = $processPayrollDTO->housing_allowance instanceof Money
                ? $processPayrollDTO->housing_allowance
                : Money::of($processPayrollDTO->housing_allowance, $currency->code);

            $transportAllowance = $processPayrollDTO->transport_allowance instanceof Money
                ? $processPayrollDTO->transport_allowance
                : Money::of($processPayrollDTO->transport_allowance, $currency->code);

            $mealAllowance = $processPayrollDTO->meal_allowance instanceof Money
                ? $processPayrollDTO->meal_allowance
                : Money::of($processPayrollDTO->meal_allowance, $currency->code);

            $otherAllowances = $processPayrollDTO->other_allowances instanceof Money
                ? $processPayrollDTO->other_allowances
                : Money::of($processPayrollDTO->other_allowances, $currency->code);

            $bonus = $processPayrollDTO->bonus instanceof Money
                ? $processPayrollDTO->bonus
                : Money::of($processPayrollDTO->bonus, $currency->code);

            $commission = $processPayrollDTO->commission instanceof Money
                ? $processPayrollDTO->commission
                : Money::of($processPayrollDTO->commission, $currency->code);

            $incomeTax = $processPayrollDTO->income_tax instanceof Money
                ? $processPayrollDTO->income_tax
                : Money::of($processPayrollDTO->income_tax, $currency->code);

            $socialSecurity = $processPayrollDTO->social_security instanceof Money
                ? $processPayrollDTO->social_security
                : Money::of($processPayrollDTO->social_security, $currency->code);

            $healthInsurance = $processPayrollDTO->health_insurance instanceof Money
                ? $processPayrollDTO->health_insurance
                : Money::of($processPayrollDTO->health_insurance, $currency->code);

            $pensionContribution = $processPayrollDTO->pension_contribution instanceof Money
                ? $processPayrollDTO->pension_contribution
                : Money::of($processPayrollDTO->pension_contribution, $currency->code);

            $loanDeduction = $processPayrollDTO->loan_deduction instanceof Money
                ? $processPayrollDTO->loan_deduction
                : Money::of($processPayrollDTO->loan_deduction, $currency->code);

            $otherDeductions = $processPayrollDTO->other_deductions instanceof Money
                ? $processPayrollDTO->other_deductions
                : Money::of($processPayrollDTO->other_deductions, $currency->code);

            // Calculate totals
            $grossSalary = $baseSalary
                ->plus($overtimeAmount)
                ->plus($housingAllowance)
                ->plus($transportAllowance)
                ->plus($mealAllowance)
                ->plus($otherAllowances)
                ->plus($bonus)
                ->plus($commission);

            $totalDeductions = $incomeTax
                ->plus($socialSecurity)
                ->plus($healthInsurance)
                ->plus($pensionContribution)
                ->plus($loanDeduction)
                ->plus($otherDeductions);

            $netSalary = $grossSalary->minus($totalDeductions);

            $totalHours = $processPayrollDTO->regular_hours + $processPayrollDTO->overtime_hours;

            // Generate payroll number if not provided
            $payrollNumber = $processPayrollDTO->payroll_number;
            if (empty($payrollNumber)) {
                $payrollNumber = $this->generatePayrollNumber($processPayrollDTO->company_id);
            }

            $payroll = Payroll::create([
                'company_id' => $processPayrollDTO->company_id,
                'employee_id' => $processPayrollDTO->employee_id,
                'currency_id' => $processPayrollDTO->currency_id,
                'payroll_number' => $payrollNumber,
                'period_start_date' => $processPayrollDTO->period_start_date,
                'period_end_date' => $processPayrollDTO->period_end_date,
                'pay_date' => $processPayrollDTO->pay_date,
                'pay_frequency' => $processPayrollDTO->pay_frequency,
                'base_salary' => $baseSalary,
                'overtime_amount' => $overtimeAmount,
                'housing_allowance' => $housingAllowance,
                'transport_allowance' => $transportAllowance,
                'meal_allowance' => $mealAllowance,
                'other_allowances' => $otherAllowances,
                'bonus' => $bonus,
                'commission' => $commission,
                'income_tax' => $incomeTax,
                'social_security' => $socialSecurity,
                'health_insurance' => $healthInsurance,
                'pension_contribution' => $pensionContribution,
                'loan_deduction' => $loanDeduction,
                'other_deductions' => $otherDeductions,
                'gross_salary' => $grossSalary,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
                'regular_hours' => $processPayrollDTO->regular_hours,
                'overtime_hours' => $processPayrollDTO->overtime_hours,
                'total_hours' => $totalHours,
                'notes' => $processPayrollDTO->notes,
                'adjustments' => $processPayrollDTO->adjustments,
                'processed_by_user_id' => $processPayrollDTO->processed_by_user_id,
                'processed_at' => now(),
            ]);

            // Create payroll lines
            foreach ($processPayrollDTO->payrollLines as $lineDTO) {
                $this->createPayrollLineAction->execute($payroll, $lineDTO);
            }

            $freshPayroll = $payroll->fresh('payrollLines');
            if (! $freshPayroll) {
                throw new Exception('Failed to refresh payroll after creation');
            }

            return $freshPayroll;
        });
    }

    private function generatePayrollNumber(int $companyId): string
    {
        $prefix = 'PAY';
        $year = now()->year;
        $month = now()->format('m');

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
