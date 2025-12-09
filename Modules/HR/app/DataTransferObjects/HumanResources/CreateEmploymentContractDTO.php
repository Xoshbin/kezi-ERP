<?php

namespace Modules\HR\DataTransferObjects\HumanResources;

use Brick\Money\Money;

readonly class CreateEmploymentContractDTO
{
    public function __construct(
        public int $company_id,
        public int $employee_id,
        public int $currency_id,
        public string $contract_number,
        public string $contract_type,
        public string $start_date,
        public ?string $end_date,
        public bool $is_active,
        public string|Money $base_salary,
        public string|Money|null $hourly_rate,
        public string $pay_frequency,
        public string|Money $housing_allowance,
        public string|Money $transport_allowance,
        public string|Money $meal_allowance,
        public string|Money $other_allowances,
        public float $working_hours_per_week,
        public float $working_days_per_week,
        public int $annual_leave_days,
        public int $sick_leave_days,
        public int $maternity_leave_days,
        public int $paternity_leave_days,
        public ?int $probation_period_months,
        public ?string $probation_end_date,
        public int $notice_period_days,
        public ?string $terms_and_conditions,
        public ?string $job_description,
        public int $created_by_user_id,
    ) {
    }
}
