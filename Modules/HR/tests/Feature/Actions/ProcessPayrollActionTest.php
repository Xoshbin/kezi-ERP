<?php

use App\Models\User;
use Brick\Money\Money;
use Modules\Foundation\Models\Currency;
use Modules\HR\Actions\HumanResources\ProcessPayrollAction;
use Modules\HR\DataTransferObjects\HumanResources\ProcessPayrollDTO;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Payroll;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

test('it processes payroll successfully with money objects', function () {
    $employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD']
    );

    // Create processor user
    $processor = User::factory()->create();
    $processor->companies()->attach($this->company);

    $dto = new ProcessPayrollDTO(
        company_id: $this->company->id,
        employee_id: $employee->id,
        currency_id: $currency->id,
        payroll_number: '',
        period_start_date: '2023-01-01',
        period_end_date: '2023-01-31',
        pay_date: '2023-02-01',
        pay_frequency: 'monthly',
        base_salary: Money::of(1000000, 'IQD'),
        overtime_amount: Money::of(50000, 'IQD'),
        housing_allowance: Money::of(100000, 'IQD'),
        transport_allowance: Money::of(50000, 'IQD'),
        meal_allowance: Money::of(0, 'IQD'),
        other_allowances: Money::of(0, 'IQD'),
        bonus: Money::of(0, 'IQD'),
        commission: Money::of(0, 'IQD'),
        income_tax: Money::of(0, 'IQD'),
        social_security: Money::of(0, 'IQD'),
        health_insurance: Money::of(0, 'IQD'),
        pension_contribution: Money::of(0, 'IQD'),
        loan_deduction: Money::of(0, 'IQD'),
        other_deductions: Money::of(0, 'IQD'),
        regular_hours: 160,
        overtime_hours: 5,
        payrollLines: [],
        notes: 'Test Payroll',
        adjustments: null,
        processed_by_user_id: $processor->id,
    );

    $action = app(ProcessPayrollAction::class);
    $payroll = $action->execute($dto);

    expect($payroll)->toBeInstanceOf(Payroll::class)
        ->and($payroll->net_salary->getAmount()->toInt())->toBe(1200000)
        ->and($payroll->total_hours)->toBe('165.00')
        ->and($payroll->payroll_number)->not->toBeEmpty();
});
