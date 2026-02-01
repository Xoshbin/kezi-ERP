<?php

namespace Jmeryar\HR\Actions\EmploymentContracts;

use Illuminate\Support\Facades\DB;
use Jmeryar\HR\DataTransferObjects\EmploymentContracts\EmploymentContractDTO;
use Jmeryar\HR\Models\EmploymentContract;

class CreateEmploymentContractAction
{
    public function execute(EmploymentContractDTO $dto): EmploymentContract
    {
        return DB::transaction(function () use ($dto) {
            $contract = new EmploymentContract;

            $contract->company_id = $dto->company_id;
            $contract->employee_id = $dto->employee_id;
            $contract->currency_id = $dto->currency_id;

            // Business logic: Generate contract number if not provided
            $contract->contract_number = $dto->contract_number ?? EmploymentContract::generateContractNumber($contract->company);

            $contract->contract_type = $dto->contract_type;
            $contract->start_date = $dto->start_date;
            $contract->end_date = $dto->end_date;
            $contract->is_active = $dto->is_active;
            $contract->base_salary = $dto->base_salary;
            $contract->hourly_rate = $dto->hourly_rate;
            $contract->pay_frequency = $dto->pay_frequency;
            $contract->housing_allowance = $dto->housing_allowance;
            $contract->transport_allowance = $dto->transport_allowance;
            $contract->meal_allowance = $dto->meal_allowance;
            $contract->other_allowances = $dto->other_allowances;
            $contract->working_hours_per_week = $dto->working_hours_per_week;
            $contract->working_days_per_week = $dto->working_days_per_week;
            $contract->annual_leave_days = $dto->annual_leave_days;
            $contract->sick_leave_days = $dto->sick_leave_days;
            $contract->maternity_leave_days = $dto->maternity_leave_days;
            $contract->paternity_leave_days = $dto->paternity_leave_days;
            $contract->probation_period_months = $dto->probation_period_months;
            $contract->probation_end_date = $dto->probation_end_date;
            $contract->notice_period_days = $dto->notice_period_days;
            $contract->terms_and_conditions = $dto->terms_and_conditions;
            $contract->job_description = $dto->job_description;
            $contract->approved_by_user_id = $dto->approved_by_user_id;
            $contract->approved_at = $dto->approved_at;
            $contract->signed_at = $dto->signed_at;

            $contract->save();

            return $contract;
        });
    }
}
