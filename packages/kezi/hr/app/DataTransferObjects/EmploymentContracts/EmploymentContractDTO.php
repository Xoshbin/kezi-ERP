<?php

namespace Kezi\HR\DataTransferObjects\EmploymentContracts;

use Brick\Money\Money;
use Illuminate\Support\Carbon;

readonly class EmploymentContractDTO
{
    public function __construct(
        public int $employee_id,
        public int $currency_id,
        public string $contract_type,
        public Carbon $start_date,
        public bool $is_active,
        public Money $base_salary,
        public string $pay_frequency,
        public Money $housing_allowance,
        public Money $transport_allowance,
        public Money $meal_allowance,
        public Money $other_allowances,
        public float $working_hours_per_week,
        public float $working_days_per_week,
        public int $annual_leave_days,
        public int $sick_leave_days,
        public int $maternity_leave_days,
        public int $paternity_leave_days,
        public int $notice_period_days,
        public ?int $company_id = null,
        public ?string $contract_number = null,
        public ?Carbon $end_date = null,
        public ?Money $hourly_rate = null,
        public ?int $probation_period_months = null,
        public ?Carbon $probation_end_date = null,
        public ?string $terms_and_conditions = null,
        public ?string $job_description = null,
        public ?int $approved_by_user_id = null,
        public ?Carbon $approved_at = null,
        public ?Carbon $signed_at = null,
    ) {}

    public static function fromArray(array $data, string $currencyCode): self
    {
        return new self(
            employee_id: $data['employee_id'],
            currency_id: $data['currency_id'],
            contract_type: $data['contract_type'],
            start_date: $data['start_date'] instanceof Carbon ? $data['start_date'] : Carbon::parse($data['start_date']),
            is_active: (bool) ($data['is_active'] ?? true),
            base_salary: self::toMoney($data['base_salary'] ?? 0, $currencyCode),
            pay_frequency: $data['pay_frequency'],
            housing_allowance: self::toMoney($data['housing_allowance'] ?? 0, $currencyCode),
            transport_allowance: self::toMoney($data['transport_allowance'] ?? 0, $currencyCode),
            meal_allowance: self::toMoney($data['meal_allowance'] ?? 0, $currencyCode),
            other_allowances: self::toMoney($data['other_allowances'] ?? 0, $currencyCode),
            working_hours_per_week: (float) ($data['working_hours_per_week'] ?? 40),
            working_days_per_week: (float) ($data['working_days_per_week'] ?? 5),
            annual_leave_days: (int) ($data['annual_leave_days'] ?? 21),
            sick_leave_days: (int) ($data['sick_leave_days'] ?? 10),
            maternity_leave_days: (int) ($data['maternity_leave_days'] ?? 90),
            paternity_leave_days: (int) ($data['paternity_leave_days'] ?? 7),
            notice_period_days: (int) ($data['notice_period_days'] ?? 30),
            company_id: $data['company_id'] ?? null,
            contract_number: $data['contract_number'] ?? null,
            end_date: isset($data['end_date']) ? ($data['end_date'] instanceof Carbon ? $data['end_date'] : Carbon::parse($data['end_date'])) : null,
            hourly_rate: isset($data['hourly_rate']) ? self::toMoney($data['hourly_rate'], $currencyCode) : null,
            probation_period_months: isset($data['probation_period_months']) ? (int) $data['probation_period_months'] : null,
            probation_end_date: isset($data['probation_end_date']) ? ($data['probation_end_date'] instanceof Carbon ? $data['probation_end_date'] : Carbon::parse($data['probation_end_date'])) : null,
            terms_and_conditions: $data['terms_and_conditions'] ?? null,
            job_description: $data['job_description'] ?? null,
            approved_by_user_id: $data['approved_by_user_id'] ?? null,
            approved_at: isset($data['approved_at']) ? ($data['approved_at'] instanceof Carbon ? $data['approved_at'] : Carbon::parse($data['approved_at'])) : null,
            signed_at: isset($data['signed_at']) ? ($data['signed_at'] instanceof Carbon ? $data['signed_at'] : Carbon::parse($data['signed_at'])) : null,
        );
    }

    private static function toMoney(mixed $amount, string $currencyCode): Money
    {
        if ($amount instanceof Money) {
            return $amount;
        }

        return Money::of($amount ?? 0, $currencyCode);
    }
}
