<?php

namespace App\Actions\HumanResources;

use App\DataTransferObjects\HumanResources\CreateEmploymentContractDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\EmploymentContract;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateEmploymentContractAction
{
    public function execute(CreateEmploymentContractDTO $createContractDTO): EmploymentContract
    {
        return DB::transaction(function () use ($createContractDTO): EmploymentContract {
            $currency = Currency::find($createContractDTO->currency_id);
            if (!$currency) {
                throw new \InvalidArgumentException('Currency not found');
            }

            // Convert Money fields if they're strings
            $baseSalary = $createContractDTO->base_salary instanceof Money
                ? $createContractDTO->base_salary
                : Money::of($createContractDTO->base_salary, $currency->code);

            $hourlyRate = null;
            if ($createContractDTO->hourly_rate) {
                $hourlyRate = $createContractDTO->hourly_rate instanceof Money
                    ? $createContractDTO->hourly_rate
                    : Money::of($createContractDTO->hourly_rate, $currency->code);
            }

            $housingAllowance = $createContractDTO->housing_allowance instanceof Money
                ? $createContractDTO->housing_allowance
                : Money::of($createContractDTO->housing_allowance, $currency->code);

            $transportAllowance = $createContractDTO->transport_allowance instanceof Money
                ? $createContractDTO->transport_allowance
                : Money::of($createContractDTO->transport_allowance, $currency->code);

            $mealAllowance = $createContractDTO->meal_allowance instanceof Money
                ? $createContractDTO->meal_allowance
                : Money::of($createContractDTO->meal_allowance, $currency->code);

            $otherAllowances = $createContractDTO->other_allowances instanceof Money
                ? $createContractDTO->other_allowances
                : Money::of($createContractDTO->other_allowances, $currency->code);

            // Generate contract number if not provided
            $contractNumber = $createContractDTO->contract_number;
            if (empty($contractNumber)) {
                $company = Company::find($createContractDTO->company_id);
                if (!$company) {
                    throw new \InvalidArgumentException('Company not found');
                }
                $contractNumber = EmploymentContract::generateContractNumber($company);
            }

            // Calculate probation end date if probation period is specified
            $probationEndDate = null;
            if ($createContractDTO->probation_period_months) {
                $probationEndDate = Carbon::parse($createContractDTO->start_date)
                    ->addMonths($createContractDTO->probation_period_months);
            }

            $contract = EmploymentContract::create([
                'company_id' => $createContractDTO->company_id,
                'employee_id' => $createContractDTO->employee_id,
                'currency_id' => $createContractDTO->currency_id,
                'contract_number' => $contractNumber,
                'contract_type' => $createContractDTO->contract_type,
                'start_date' => $createContractDTO->start_date,
                'end_date' => $createContractDTO->end_date,
                'is_active' => $createContractDTO->is_active,
                'base_salary' => $baseSalary,
                'hourly_rate' => $hourlyRate,
                'pay_frequency' => $createContractDTO->pay_frequency,
                'housing_allowance' => $housingAllowance,
                'transport_allowance' => $transportAllowance,
                'meal_allowance' => $mealAllowance,
                'other_allowances' => $otherAllowances,
                'working_hours_per_week' => $createContractDTO->working_hours_per_week,
                'working_days_per_week' => $createContractDTO->working_days_per_week,
                'annual_leave_days' => $createContractDTO->annual_leave_days,
                'sick_leave_days' => $createContractDTO->sick_leave_days,
                'maternity_leave_days' => $createContractDTO->maternity_leave_days,
                'paternity_leave_days' => $createContractDTO->paternity_leave_days,
                'probation_period_months' => $createContractDTO->probation_period_months,
                'probation_end_date' => $probationEndDate,
                'notice_period_days' => $createContractDTO->notice_period_days,
                'terms_and_conditions' => $createContractDTO->terms_and_conditions,
                'job_description' => $createContractDTO->job_description,
            ]);

            $fresh = $contract->fresh();
            if (!$fresh) {
                throw new \RuntimeException('Failed to refresh contract after creation');
            }

            return $fresh;
        });
    }
}
