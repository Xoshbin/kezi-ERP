<?php

use App\Models\Company;
use App\Models\Currency;
use App\Models\Employee;
use App\Models\Payroll;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = \Modules\Foundation\Models\Currency::firstOrCreate(
        ['code' => 'IQD'],
        [
            'name' => 'Iraqi Dinar',
            'symbol' => 'IQD',
            'decimal_places' => 3,
            'is_active' => true,
        ]
    );
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->employee = \Modules\HR\Models\Employee::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'employment_status' => 'active',
    ]);
});

test('payroll can be created with automatic number generation', function () {
    $payrollData = [
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(5000000, 'IQD'), // 5,000,000 IQD
        'overtime_amount' => Money::of(0, 'IQD'),
        'housing_allowance' => Money::of(1000000, 'IQD'), // 1,000,000 IQD
        'transport_allowance' => Money::of(500000, 'IQD'), // 500,000 IQD
        'meal_allowance' => Money::of(300000, 'IQD'), // 300,000 IQD
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(200000, 'IQD'), // 200,000 IQD
        'social_security' => Money::of(150000, 'IQD'), // 150,000 IQD
        'health_insurance' => Money::of(100000, 'IQD'), // 100,000 IQD
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
        'notes' => 'Test payroll creation',
    ];

    $payroll = \Modules\HR\Models\Payroll::create($payrollData);

    expect($payroll)->toBeInstanceOf(\Modules\HR\Models\Payroll::class);
    expect($payroll->payroll_number)->toStartWith('PAY2025');
    expect($payroll->status)->toBe('draft');
    expect($payroll->company_id)->toBe($this->company->id);
    expect($payroll->employee_id)->toBe($this->employee->id);
});

test('payroll calculates gross salary correctly', function () {
    $payrollData = [
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(5000000, 'IQD'),
        'overtime_amount' => Money::of(500000, 'IQD'),
        'housing_allowance' => Money::of(1000000, 'IQD'),
        'transport_allowance' => Money::of(500000, 'IQD'),
        'meal_allowance' => Money::of(300000, 'IQD'),
        'other_allowances' => Money::of(200000, 'IQD'),
        'bonus' => Money::of(1000000, 'IQD'),
        'commission' => Money::of(300000, 'IQD'),
        'income_tax' => Money::of(0, 'IQD'),
        'social_security' => Money::of(0, 'IQD'),
        'health_insurance' => Money::of(0, 'IQD'),
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
    ];

    $payroll = \Modules\HR\Models\Payroll::create($payrollData);

    // Expected gross salary: 5,000,000 + 500,000 + 1,000,000 + 500,000 + 300,000 + 200,000 + 1,000,000 + 300,000 = 8,800,000
    $expectedGrossSalary = Money::of(8800000, 'IQD');

    expect($payroll->gross_salary->isEqualTo($expectedGrossSalary))->toBeTrue();
});

test('payroll calculates total deductions correctly', function () {
    $payrollData = [
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(5000000, 'IQD'),
        'overtime_amount' => Money::of(0, 'IQD'),
        'housing_allowance' => Money::of(0, 'IQD'),
        'transport_allowance' => Money::of(0, 'IQD'),
        'meal_allowance' => Money::of(0, 'IQD'),
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(300000, 'IQD'),
        'social_security' => Money::of(200000, 'IQD'),
        'health_insurance' => Money::of(150000, 'IQD'),
        'pension_contribution' => Money::of(100000, 'IQD'),
        'other_deductions' => Money::of(50000, 'IQD'),
    ];

    $payroll = \Modules\HR\Models\Payroll::create($payrollData);

    // Expected total deductions: 300,000 + 200,000 + 150,000 + 100,000 + 50,000 = 800,000
    $expectedTotalDeductions = Money::of(800000, 'IQD');

    expect($payroll->total_deductions->isEqualTo($expectedTotalDeductions))->toBeTrue();
});

test('payroll calculates net salary correctly', function () {
    $payrollData = [
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(5000000, 'IQD'),
        'overtime_amount' => Money::of(500000, 'IQD'),
        'housing_allowance' => Money::of(1000000, 'IQD'),
        'transport_allowance' => Money::of(0, 'IQD'),
        'meal_allowance' => Money::of(0, 'IQD'),
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(300000, 'IQD'),
        'social_security' => Money::of(200000, 'IQD'),
        'health_insurance' => Money::of(150000, 'IQD'),
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
    ];

    $payroll = \Modules\HR\Models\Payroll::create($payrollData);

    // Expected gross salary: 5,000,000 + 500,000 + 1,000,000 = 6,500,000
    // Expected total deductions: 300,000 + 200,000 + 150,000 = 650,000
    // Expected net salary: 6,500,000 - 650,000 = 5,850,000
    $expectedNetSalary = Money::of(5850000, 'IQD');

    expect($payroll->net_salary->isEqualTo($expectedNetSalary))->toBeTrue();
});

test('payroll number is unique per company and month', function () {
    $payroll1 = \Modules\HR\Models\Payroll::create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(5000000, 'IQD'),
        'overtime_amount' => Money::of(0, 'IQD'),
        'housing_allowance' => Money::of(0, 'IQD'),
        'transport_allowance' => Money::of(0, 'IQD'),
        'meal_allowance' => Money::of(0, 'IQD'),
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(0, 'IQD'),
        'social_security' => Money::of(0, 'IQD'),
        'health_insurance' => Money::of(0, 'IQD'),
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
    ]);

    $employee2 = \Modules\HR\Models\Employee::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
        'employment_status' => 'active',
    ]);

    $payroll2 = \Modules\HR\Models\Payroll::create([
        'company_id' => $this->company->id,
        'employee_id' => $employee2->id,
        'currency_id' => $this->currency->id,
        'period_start_date' => '2025-08-01',
        'period_end_date' => '2025-08-31',
        'pay_date' => '2025-08-31',
        'pay_frequency' => 'monthly',
        'base_salary' => Money::of(4000000, 'IQD'),
        'overtime_amount' => Money::of(0, 'IQD'),
        'housing_allowance' => Money::of(0, 'IQD'),
        'transport_allowance' => Money::of(0, 'IQD'),
        'meal_allowance' => Money::of(0, 'IQD'),
        'other_allowances' => Money::of(0, 'IQD'),
        'bonus' => Money::of(0, 'IQD'),
        'commission' => Money::of(0, 'IQD'),
        'income_tax' => Money::of(0, 'IQD'),
        'social_security' => Money::of(0, 'IQD'),
        'health_insurance' => Money::of(0, 'IQD'),
        'pension_contribution' => Money::of(0, 'IQD'),
        'other_deductions' => Money::of(0, 'IQD'),
    ]);

    expect($payroll1->payroll_number)->not->toBe($payroll2->payroll_number);
    expect($payroll1->payroll_number)->toEndWith('0001');
    expect($payroll2->payroll_number)->toEndWith('0002');
});
