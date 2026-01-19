<?php

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\HR\Actions\HumanResources\CreateEmploymentContractAction;
use Modules\HR\DataTransferObjects\HumanResources\CreateEmploymentContractDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmploymentContract;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('CreateEmploymentContractAction', function () {
    it('creates an employment contract with string money values', function () {
        // Arrange
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $currency = $this->company->currency;
        $startDate = Carbon::now()->toDateString();

        $dto = new CreateEmploymentContractDTO(
            company_id: $this->company->id,
            employee_id: $employee->id,
            currency_id: $currency->id,
            contract_number: '',
            contract_type: 'Full Time',
            start_date: $startDate,
            end_date: null,
            is_active: true,
            base_salary: '1000',
            hourly_rate: null,
            pay_frequency: 'Monthly',
            housing_allowance: '200',
            transport_allowance: '100',
            meal_allowance: '50',
            other_allowances: '0',
            working_hours_per_week: 40,
            working_days_per_week: 5,
            annual_leave_days: 21,
            sick_leave_days: 10,
            maternity_leave_days: 90,
            paternity_leave_days: 5,
            probation_period_months: 3,
            probation_end_date: null,
            notice_period_days: 30,
            terms_and_conditions: 'Standard terms',
            job_description: 'Developer',
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateEmploymentContractAction::class);
        $contract = $action->execute($dto);

        // Assert
        expect($contract)->toBeInstanceOf(EmploymentContract::class);
        expect($contract->contract_number)->not->toBeEmpty();

        // Assert Money values
        expect($contract->base_salary->getAmount()->toFloat())->toBe(1000.0);
        expect($contract->base_salary->getCurrency()->getCurrencyCode())->toBe($currency->code);

        expect($contract->housing_allowance->getAmount()->toFloat())->toBe(200.0);
        expect($contract->housing_allowance->getCurrency()->getCurrencyCode())->toBe($currency->code);

        // Assert Probation Date Calculation
        $expectedProbationEnd = Carbon::parse($startDate)->addMonths(3);
        expect($contract->probation_end_date->toDateString())->toBe($expectedProbationEnd->toDateString());
    });

    it('creates an employment contract with Money objects', function () {
        // Arrange
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $currency = $this->company->currency;
        $startDate = Carbon::now()->toDateString();

        $baseSalary = Money::of(2000, $currency->code);
        $housingAllowance = Money::of(300, $currency->code);

        $dto = new CreateEmploymentContractDTO(
            company_id: $this->company->id,
            employee_id: $employee->id,
            currency_id: $currency->id,
            contract_number: 'CNT-002',
            contract_type: 'Full Time',
            start_date: $startDate,
            end_date: null,
            is_active: true,
            base_salary: $baseSalary,
            hourly_rate: null,
            pay_frequency: 'Monthly',
            housing_allowance: $housingAllowance,
            transport_allowance: '0',
            meal_allowance: '0',
            other_allowances: '0',
            working_hours_per_week: 40,
            working_days_per_week: 5,
            annual_leave_days: 21,
            sick_leave_days: 10,
            maternity_leave_days: 0,
            paternity_leave_days: 0,
            probation_period_months: null,
            probation_end_date: null,
            notice_period_days: 30,
            terms_and_conditions: null,
            job_description: null,
            created_by_user_id: $this->user->id,
        );

        // Act
        $action = app(CreateEmploymentContractAction::class);
        $contract = $action->execute($dto);

        // Assert
        expect($contract->contract_number)->toBe('CNT-002');
        expect($contract->base_salary->getAmount()->toFloat())->toBe(2000.0);
        expect($contract->housing_allowance->getAmount()->toFloat())->toBe(300.0);
        expect($contract->probation_end_date)->toBeNull();
    });
});
