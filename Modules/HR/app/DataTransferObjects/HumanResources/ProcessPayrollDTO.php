<?php

namespace Modules\HR\DataTransferObjects\HumanResources;

use Brick\Money\Money;

readonly class ProcessPayrollDTO
{
    /**
     * @param  PayrollLineDTO[]  $payrollLines
     */
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public int $currency_id,
        public string $payroll_number,
        public string $period_start_date,
        public string $period_end_date,
        public string $pay_date,
        public string $pay_frequency,
        public string|Money $base_salary,
        public string|Money $overtime_amount,
        public string|Money $housing_allowance,
        public string|Money $transport_allowance,
        public string|Money $meal_allowance,
        public string|Money $other_allowances,
        public string|Money $bonus,
        public string|Money $commission,
        public string|Money $income_tax,
        public string|Money $social_security,
        public string|Money $health_insurance,
        public string|Money $pension_contribution,
        public string|Money $loan_deduction,
        public string|Money $other_deductions,
        public float $regular_hours,
        public float $overtime_hours,
        /** @var array<int, PayrollLineDTO> */
        public array $payrollLines,
        public ?string $notes,
        /** @var array<string, mixed>|null */
        public ?array $adjustments,
        public int $processed_by_user_id,
    ) {}
}
