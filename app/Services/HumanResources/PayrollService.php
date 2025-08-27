<?php

namespace App\Services\HumanResources;

use App\Actions\HumanResources\ProcessPayrollAction;
use App\Actions\HumanResources\CreatePaymentFromPayrollAction;
use App\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use App\DataTransferObjects\HumanResources\PayrollLineDTO;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\User;
use App\Models\Company;
use App\Models\Account;
use App\Models\Payment;
use App\Services\Accounting\LockDateService;
use App\Actions\Accounting\CreateJournalEntryForPayrollAction;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PayrollService
{
    public function __construct(
        protected ProcessPayrollAction $processPayrollAction,
        protected LockDateService $lockDateService,
        protected CreateJournalEntryForPayrollAction $createJournalEntryForPayrollAction,
        protected CreatePaymentFromPayrollAction $createPaymentFromPayrollAction,
    ) {
    }

    /**
     * Process payroll for an employee.
     */
    public function processPayroll(Employee $employee, string $periodStartDate, string $periodEndDate, string $payDate, User $user): Payroll
    {
        Gate::forUser($user)->authorize('create', Payroll::class);

        $this->lockDateService->enforce($employee->company, Carbon::parse($payDate));

        return DB::transaction(function () use ($employee, $periodStartDate, $periodEndDate, $payDate, $user) {
            $contract = $employee->currentContract;
            if (!$contract) {
                throw new \Exception('Employee does not have an active contract.');
            }

            // Calculate attendance-based amounts
            $attendanceData = $this->calculateAttendanceAmounts($employee, $periodStartDate, $periodEndDate);

            // Calculate base salary (prorated if needed)
            $baseSalary = $this->calculateBaseSalary($contract, $periodStartDate, $periodEndDate);

            // Calculate overtime
            $overtimeAmount = $this->calculateOvertimeAmount($contract, $attendanceData['overtime_hours']);

            // Get allowances from contract
            $housingAllowance = $contract->housing_allowance;
            $transportAllowance = $contract->transport_allowance;
            $mealAllowance = $contract->meal_allowance;
            $otherAllowances = $contract->other_allowances;

            // Calculate deductions
            $deductions = $this->calculateDeductions($baseSalary, $contract);

            // Create payroll lines for accounting integration
            $payrollLines = $this->createPayrollLines($employee, $baseSalary, $attendanceData, $deductions);

            $processPayrollDTO = new ProcessPayrollDTO(
                company_id: $employee->company_id,
                employee_id: $employee->id,
                currency_id: $contract->currency_id,
                payroll_number: '', // Will be generated
                period_start_date: $periodStartDate,
                period_end_date: $periodEndDate,
                pay_date: $payDate,
                pay_frequency: $contract->pay_frequency,
                base_salary: $baseSalary,
                overtime_amount: $overtimeAmount,
                housing_allowance: $housingAllowance,
                transport_allowance: $transportAllowance,
                meal_allowance: $mealAllowance,
                other_allowances: $otherAllowances,
                bonus: Money::of(0, $contract->currency->code),
                commission: Money::of(0, $contract->currency->code),
                income_tax: $deductions['income_tax'],
                social_security: $deductions['social_security'],
                health_insurance: $deductions['health_insurance'],
                pension_contribution: $deductions['pension_contribution'],
                loan_deduction: Money::of(0, $contract->currency->code),
                other_deductions: Money::of(0, $contract->currency->code),
                regular_hours: $attendanceData['regular_hours'],
                overtime_hours: $attendanceData['overtime_hours'],
                payrollLines: $payrollLines,
                notes: null,
                adjustments: null,
                processed_by_user_id: $user->id,
            );

            return $this->processPayrollAction->execute($processPayrollDTO);
        });
    }

    /**
     * Approve a payroll.
     */
    public function approvePayroll(Payroll $payroll, User $user): void
    {
        Gate::forUser($user)->authorize('approve', $payroll);

        if ($payroll->status !== 'draft') {
            throw new \Exception('Only draft payrolls can be approved.');
        }

        DB::transaction(function () use ($payroll, $user) {
            $payroll->update([
                'status' => 'processed',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ]);

            // Create journal entry for accounting integration
            $journalEntry = $this->createJournalEntryForPayrollAction->execute($payroll, $user);
            $payroll->update(['journal_entry_id' => $journalEntry->id]);
        });
    }

    /**
     * Calculate attendance-based amounts for the period.
     */
    private function calculateAttendanceAmounts(Employee $employee, string $periodStartDate, string $periodEndDate): array
    {
        $attendances = $employee->attendances()
            ->whereBetween('attendance_date', [$periodStartDate, $periodEndDate])
            ->where('status', 'present')
            ->get();

        $regularHours = $attendances->sum('regular_hours') ?? 0;
        $overtimeHours = $attendances->sum('overtime_hours') ?? 0;

        return [
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'total_hours' => $regularHours + $overtimeHours,
        ];
    }

    /**
     * Calculate base salary for the period.
     */
    private function calculateBaseSalary($contract, string $periodStartDate, string $periodEndDate): Money
    {
        $baseSalary = $contract->base_salary;

        // For monthly salary, check if we need to prorate
        if ($contract->pay_frequency === 'monthly') {
            $periodStart = Carbon::parse($periodStartDate);
            $periodEnd = Carbon::parse($periodEndDate);
            $daysInPeriod = $periodEnd->diffInDays($periodStart) + 1;
            $daysInMonth = $periodStart->daysInMonth;

            if ($daysInPeriod < $daysInMonth) {
                // Prorate the salary
                $prorationFactor = $daysInPeriod / $daysInMonth;
                $baseSalary = $baseSalary->multipliedBy($prorationFactor, \Brick\Math\RoundingMode::HALF_UP);
            }
        }

        return $baseSalary;
    }

    /**
     * Calculate overtime amount.
     */
    private function calculateOvertimeAmount($contract, float $overtimeHours): Money
    {
        if ($overtimeHours <= 0) {
            return Money::of(0, $contract->currency->code);
        }

        // Calculate overtime rate (typically 1.5x regular rate)
        $regularHourlyRate = $contract->hourly_rate ??
            $contract->base_salary->dividedBy($contract->working_hours_per_week * 4.33); // Approximate monthly hours

        $overtimeRate = $regularHourlyRate->multipliedBy(1.5);

        return $overtimeRate->multipliedBy($overtimeHours);
    }

    /**
     * Calculate deductions.
     */
    private function calculateDeductions(Money $grossSalary, $contract): array
    {
        $currency = $contract->currency->code;

        // TODO: Implement proper tax calculation based on company's tax rules
        // For now, using simple percentages
        $incomeTax = $grossSalary->multipliedBy(0.10, \Brick\Math\RoundingMode::HALF_UP); // 10% income tax
        $socialSecurity = $grossSalary->multipliedBy(0.05, \Brick\Math\RoundingMode::HALF_UP); // 5% social security
        $healthInsurance = Money::of(50, $currency); // Fixed amount
        $pensionContribution = $grossSalary->multipliedBy(0.03, \Brick\Math\RoundingMode::HALF_UP); // 3% pension

        return [
            'income_tax' => $incomeTax,
            'social_security' => $socialSecurity,
            'health_insurance' => $healthInsurance,
            'pension_contribution' => $pensionContribution,
        ];
    }

    /**
     * Create payroll lines for accounting integration.
     */
    private function createPayrollLines(Employee $employee, Money $baseSalary, array $attendanceData, array $deductions): array
    {
        $lines = [];
        $company = $employee->company;

        // TODO: Get proper account IDs from company's chart of accounts
        // For now, using placeholder account IDs
        $salaryExpenseAccountId = 1; // Should be salary expense account
        $payableAccountId = 2; // Should be salary payable account
        $taxPayableAccountId = 3; // Should be tax payable account

        // Salary expense line (debit)
        $lines[] = new PayrollLineDTO(
            company_id: $company->id,
            account_id: $salaryExpenseAccountId,
            line_type: 'earning',
            code: 'salary',
            description: ['en' => 'Base Salary'],
            quantity: 1,
            unit: 'fixed',
            rate: $baseSalary,
            amount: $baseSalary,
            tax_rate: null,
            is_taxable: true,
            is_statutory: false,
            debit_credit: 'debit',
            analytic_account_id: null,
            notes: null,
            reference: null,
        );

        // Calculate total deductions
        $totalDeductions = Money::of(0, $baseSalary->getCurrency());

        // Tax deduction lines (credit)
        foreach ($deductions as $type => $amount) {
            $totalDeductions = $totalDeductions->plus($amount);

            $lines[] = new PayrollLineDTO(
                company_id: $company->id,
                account_id: $taxPayableAccountId,
                line_type: 'deduction',
                code: $type,
                description: ['en' => ucfirst(str_replace('_', ' ', $type))],
                quantity: 1,
                unit: 'fixed',
                rate: $amount,
                amount: $amount,
                tax_rate: null,
                is_taxable: false,
                is_statutory: true,
                debit_credit: 'credit',
                analytic_account_id: null,
                notes: null,
                reference: null,
            );
        }

        // Net salary payable line (credit)
        $netSalary = $baseSalary->minus($totalDeductions);
        $lines[] = new PayrollLineDTO(
            company_id: $company->id,
            account_id: $payableAccountId,
            line_type: 'earning',
            code: 'net_salary',
            description: ['en' => 'Net Salary Payable'],
            quantity: 1,
            unit: 'fixed',
            rate: $netSalary,
            amount: $netSalary,
            tax_rate: null,
            is_taxable: false,
            is_statutory: false,
            debit_credit: 'credit',
            analytic_account_id: null,
            notes: null,
            reference: null,
        );

        return $lines;
    }

    /**
     * Create payment for an approved payroll.
     */
    public function payEmployee(Payroll $payroll, User $user): Payment
    {
        Gate::forUser($user)->authorize('pay', $payroll);

        if ($payroll->status !== 'processed') {
            throw new \InvalidArgumentException('Only processed payrolls can be paid.');
        }

        if ($payroll->payment_id) {
            throw new \Exception('Payroll has already been paid.');
        }

        $this->lockDateService->enforce($payroll->company, $payroll->pay_date);

        return DB::transaction(function () use ($payroll, $user) {
            // Create payment for net salary
            $payment = $this->createPaymentFromPayrollAction->execute($payroll, $user);

            // Update payroll status to 'paid' and link payment
            $payroll->update([
                'status' => 'paid',
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }
}
