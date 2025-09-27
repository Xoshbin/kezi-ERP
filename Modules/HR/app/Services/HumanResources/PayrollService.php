<?php

namespace Modules\HR\Services\HumanResources;

use App\Actions\Accounting\CreateJournalEntryForPayrollAction;
use App\Actions\HumanResources\CreatePaymentFromPayrollAction;
use App\Actions\HumanResources\ProcessPayrollAction;
use App\DataTransferObjects\HumanResources\PayrollLineDTO;
use App\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use App\Models\Account;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\User;
use App\Services\Accounting\LockDateService;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class PayrollService
{
    public function __construct(
        protected ProcessPayrollAction $processPayrollAction,
        protected LockDateService $lockDateService,
        protected CreateJournalEntryForPayrollAction $createJournalEntryForPayrollAction,
        protected CreatePaymentFromPayrollAction $createPaymentFromPayrollAction,
    ) {}

    /**
     * Process payroll for an employee.
     */
    public function processPayroll(Employee $employee, string $periodStartDate, string $periodEndDate, string $payDate, User $user): Payroll
    {
        Gate::forUser($user)->authorize('create', Payroll::class);

        $this->lockDateService->enforce($employee->company, Carbon::parse($payDate));

        return DB::transaction(function () use ($employee, $periodStartDate, $periodEndDate, $payDate, $user) {
            $contract = $employee->currentContract;
            if (! $contract) {
                throw new Exception('Employee does not have an active contract.');
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
            throw new Exception('Only draft payrolls can be approved.');
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
     *
     * @return array<string, mixed>
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
    private function calculateBaseSalary(\App\Models\EmploymentContract $contract, string $periodStartDate, string $periodEndDate): Money
    {
        $baseSalary = $contract->base_salary;

        // For monthly salary, check if we need to prorate
        if ($contract->pay_frequency === 'monthly') {
            $periodStart = Carbon::parse($periodStartDate);
            $periodEnd = Carbon::parse($periodEndDate);
            $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
            $daysInMonth = $periodStart->daysInMonth;

            if ($daysInPeriod < $daysInMonth) {
                // Prorate the salary
                $prorationFactor = $daysInPeriod / $daysInMonth;
                $baseSalary = $baseSalary->multipliedBy($prorationFactor, RoundingMode::HALF_UP);
            }
        }

        return $baseSalary;
    }

    /**
     * Calculate overtime amount.
     */
    private function calculateOvertimeAmount(\App\Models\EmploymentContract $contract, float $overtimeHours): Money
    {
        if ($overtimeHours <= 0) {
            return Money::of(0, $contract->currency->code);
        }

        // Calculate overtime rate (typically 1.5x regular rate)
        if ($contract->hourly_rate) {
            $regularHourlyRate = $contract->hourly_rate;
        } else {
            // Calculate hourly rate from monthly salary
            $monthlyHours = $contract->working_hours_per_week * 4.33; // Approximate monthly hours
            $regularHourlyRate = $contract->base_salary->dividedBy($monthlyHours, RoundingMode::HALF_UP);
        }

        $overtimeRate = $regularHourlyRate->multipliedBy(1.5, RoundingMode::HALF_UP);

        return $overtimeRate->multipliedBy($overtimeHours, RoundingMode::HALF_UP);
    }

    /**
     * Calculate deductions.
     */
    /**
     * @return array<string, Money>
     */
    private function calculateDeductions(Money $grossSalary, \App\Models\EmploymentContract $contract): array
    {
        $currency = $contract->currency->code;

        // TODO: Implement proper tax calculation based on company's tax rules
        // For now, using simple percentages
        $incomeTax = $grossSalary->multipliedBy(0.10, RoundingMode::HALF_UP); // 10% income tax
        $socialSecurity = $grossSalary->multipliedBy(0.05, RoundingMode::HALF_UP); // 5% social security
        $healthInsurance = Money::of(50, $currency); // Fixed amount
        $pensionContribution = $grossSalary->multipliedBy(0.03, RoundingMode::HALF_UP); // 3% pension

        return [
            'income_tax' => $incomeTax,
            'social_security' => $socialSecurity,
            'health_insurance' => $healthInsurance,
            'pension_contribution' => $pensionContribution,
        ];
    }

    /**
     * Create payroll lines for accounting integration.
     *
     * @param  array<string, mixed>  $attendanceData
     * @param  array<string, mixed>  $deductions
     * @return list<PayrollLineDTO>
     */
    private function createPayrollLines(Employee $employee, Money $baseSalary, array $attendanceData, array $deductions): array
    {
        $lines = [];
        $company = $employee->company;

        // Get proper account IDs from company's chart of accounts
        $salaryExpenseAccountId = $company->default_salary_expense_account_id;
        $incomeTaxPayableAccountId = $company->default_income_tax_payable_account_id;
        $socialSecurityPayableAccountId = $company->default_social_security_payable_account_id;
        $healthInsurancePayableAccountId = $company->default_health_insurance_payable_account_id;
        $pensionPayableAccountId = $company->default_pension_payable_account_id;

        // Use fallback accounts if not configured (for testing/development)
        if (! $salaryExpenseAccountId) {
            $salaryExpenseAccountId = 1; // fallback account ID
        }

        $salaryPayableAccountId = $company->default_salary_payable_account_id ?? 1; // fallback account ID

        // Calculate total deductions first
        $totalDeductions = Money::of(0, $baseSalary->getCurrency());
        foreach ($deductions as $amount) {
            $totalDeductions = $totalDeductions->plus($amount);
        }

        // Calculate gross salary (base salary for now - could include allowances)
        $grossSalary = $baseSalary;

        // Gross salary expense line (debit) - this should equal all credits
        $lines[] = new PayrollLineDTO(
            company_id: $company->id,
            account_id: $salaryExpenseAccountId,
            line_type: 'earning',
            code: 'gross_salary',
            description: ['en' => 'Gross Salary Expense'],
            quantity: 1,
            unit: 'fixed',
            rate: $grossSalary,
            amount: $grossSalary,
            tax_rate: null,
            is_taxable: true,
            is_statutory: false,
            debit_credit: 'debit',
            analytic_account_id: null,
            notes: null,
            reference: null,
        );

        // Individual deduction lines (credits to respective payable accounts)
        $accountMapping = [
            'income_tax' => $incomeTaxPayableAccountId,
            'social_security' => $socialSecurityPayableAccountId,
            'health_insurance' => $healthInsurancePayableAccountId,
            'pension_contribution' => $pensionPayableAccountId,
        ];

        foreach ($deductions as $type => $amount) {
            $accountId = $accountMapping[$type] ?? $salaryPayableAccountId; // fallback to salary payable

            $lines[] = new PayrollLineDTO(
                company_id: $company->id,
                account_id: $accountId,
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

        // Net salary payable line (credit) - this is what we owe the employee
        $netSalary = $grossSalary->minus($totalDeductions);
        $lines[] = new PayrollLineDTO(
            company_id: $company->id,
            account_id: $salaryPayableAccountId,
            line_type: 'liability',
            code: 'net_salary_payable',
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

        if ($payroll->payment_id) {
            throw new Exception('Payroll has already been paid.');
        }

        if ($payroll->status !== 'processed') {
            throw new InvalidArgumentException('Only processed payrolls can be paid.');
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
