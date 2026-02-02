<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Foundation\Models\Currency;
use Kezi\HR\Models\Employee;
use Kezi\HR\Models\EmploymentContract;
use Kezi\HR\Models\Payroll;
use Kezi\HR\Services\HumanResources\PayrollService;
use Tests\Traits\WithConfiguredCompany;

/** @var \Tests\TestCase&\Tests\Traits\WithConfiguredCompany $this */
uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();

    // Mock LockDateService
    $this->lockDateService = Mockery::mock(LockDateService::class);
    $this->lockDateService->shouldReceive('enforce')->andReturnTrue();

    $this->app->instance(LockDateService::class, $this->lockDateService);

    $this->service = app(PayrollService::class);

    // Setup basic employee and contract
    $this->currency = Currency::firstOrCreate(
        ['code' => 'IQD'],
        ['name' => 'Iraqi Dinar', 'symbol' => 'IQD', 'decimal_places' => 3]
    );

    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $this->contract = EmploymentContract::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'currency_id' => $this->currency->id,
        'base_salary' => Money::of(1000000, 'IQD'),
        'start_date' => Carbon::parse('2023-01-01'),
        'pay_frequency' => 'monthly',
        'working_hours_per_week' => 40,
    ]);

});

test('it processes payroll correctly', function () {
    $service = app(PayrollService::class);
    $user = User::factory()->create();

    /** @var \App\Models\Company $company */
    $company = $this->company;
    $user->companies()->attach($company);

    /** @var \Kezi\HR\Models\Employee $employee */
    $employee = $this->employee;

    // Mock Gate
    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    $periodStart = '2023-01-01';
    $periodEnd = '2023-01-31';
    $payDate = '2023-02-01';

    $payroll = $service->processPayroll(
        $employee,
        $periodStart,
        $periodEnd,
        $payDate,
        $user
    );

    expect($payroll)->toBeInstanceOf(Payroll::class);
    expect($payroll->net_salary->getAmount()->toInt())->toBeGreaterThan(0);
    expect($payroll->base_salary->getAmount()->toInt())->toBe(1000000);
    expect($payroll->status)->toBe('draft');
});

test('it calculates proration for partial month', function () {
    $service = app(PayrollService::class);
    $user = User::factory()->create();
    /** @var \App\Models\Company $company */
    $company = $this->company;
    $user->companies()->attach($company);

    /** @var \Kezi\HR\Models\Employee $employee */
    $employee = $this->employee;

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // 15 days in Jan (Jan 1 to Jan 15)
    $periodStart = '2023-01-01';
    $periodEnd = '2023-01-15';
    $payDate = '2023-02-01';

    $payroll = $service->processPayroll(
        $employee,
        $periodStart,
        $periodEnd,
        $payDate,
        $user
    );

    $actualBase = $payroll->base_salary->getAmount()->toFloat();

    // Allow small rounding diff
    expect($actualBase)->toBeGreaterThan(480000);
    expect($actualBase)->toBeLessThan(490000);
});

test('it calculates proration for mid-month start', function () {
    $user = User::factory()->create();
    $this->company->users()->attach($user);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // Start Jan 25 (works Jan 25-31 = 7 days out of 31)
    $periodStart = '2023-01-25';
    $periodEnd = '2023-01-31';
    $payDate = '2023-02-01';

    $payroll = $this->service->processPayroll(
        $this->employee,
        $periodStart,
        $periodEnd,
        $payDate,
        $user
    );

    $actualBase = $payroll->base_salary->getAmount()->toFloat();
    // 1,000,000 * (7/31) = 225,806.45...
    expect($actualBase)->toBeGreaterThan(225000);
    expect($actualBase)->toBeLessThan(227000);
});

test('it calculates overtime correctly with explicit hourly rate', function () {
    $user = User::factory()->create();
    $this->company->users()->attach($user);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // Update contract with hourly rate
    $this->contract->update([
        'hourly_rate' => Money::of(10000, 'IQD'),
    ]);

    // Create attendance for overtime (10 hours)
    $this->employee->attendances()->create([
        'company_id' => $this->company->id,
        'attendance_date' => '2023-01-05',
        'regular_hours' => 8,
        'overtime_hours' => 10,
        'status' => 'present',
    ]);

    $payroll = $this->service->processPayroll(
        $this->employee,
        '2023-01-01',
        '2023-01-31',
        '2023-02-01',
        $user
    );

    // Hourly rate 10,000 * 1.5 = 15,000 per OT hour
    // 15,000 * 10 = 150,000
    expect($payroll->overtime_amount->isEqualTo(Money::of(150000, 'IQD')))->toBeTrue();
});

test('it calculates overtime derived from monthly salary', function () {
    $user = User::factory()->create();
    $this->company->users()->attach($user);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // base_salary 1,000,000, 40 hrs/week
    // monthly hours approx: 40 * 4.33 = 173.2
    // hourly rate: 1,000,000 / 173.2 = 5,773.67...
    // OT rate (1.5x): ~8,660.5...

    $this->employee->attendances()->create([
        'company_id' => $this->company->id,
        'attendance_date' => '2023-01-05',
        'regular_hours' => 8,
        'overtime_hours' => 10,
        'status' => 'present',
    ]);

    $payroll = $this->service->processPayroll(
        $this->employee,
        '2023-01-01',
        '2023-01-31',
        '2023-02-01',
        $user
    );

    expect($payroll->overtime_amount->isGreaterThan(Money::of(80000, 'IQD')))->toBeTrue();
    expect($payroll->overtime_amount->isLessThan(Money::of(90000, 'IQD')))->toBeTrue();
});

test('it applies correct automatic deductions', function () {
    $user = User::factory()->create();
    $this->company->users()->attach($user);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    $payroll = $this->service->processPayroll(
        $this->employee,
        '2023-01-01',
        '2023-01-31',
        '2023-02-01',
        $user
    );

    // base_salary: 1,000,000
    // 10% tax = 100,000
    // 5% social security = 50,000
    // Fixed 50 health insurance
    // 3% pension = 30,000
    expect($payroll->income_tax->isEqualTo(Money::of(100000, 'IQD')))->toBeTrue();
    expect($payroll->social_security->isEqualTo(Money::of(50000, 'IQD')))->toBeTrue();
    expect($payroll->health_insurance->isEqualTo(Money::of(50, 'IQD')))->toBeTrue();
    expect($payroll->pension_contribution->isEqualTo(Money::of(30000, 'IQD')))->toBeTrue();
});

test('it generates correct accounting lines with balanced debits and credits', function () {
    $user = User::factory()->create();
    $this->company->users()->attach($user);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // Setup accounts in company
    $salaryExpenseAccount = \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);
    $salaryPayableAccount = \Kezi\Accounting\Models\Account::factory()->create(['company_id' => $this->company->id]);

    $this->company->update([
        'default_salary_expense_account_id' => $salaryExpenseAccount->id,
        'default_salary_payable_account_id' => $salaryPayableAccount->id,
    ]);

    $payroll = $this->service->processPayroll(
        $this->employee,
        '2023-01-01',
        '2023-01-31',
        '2023-02-01',
        $user
    );

    expect($payroll->payrollLines->count())->toBeGreaterThan(0);

    $totalDebits = Money::of(0, 'IQD');
    $totalCredits = Money::of(0, 'IQD');

    foreach ($payroll->payrollLines as $line) {
        if ($line->debit_credit === 'debit') {
            $totalDebits = $totalDebits->plus($line->amount);
        } else {
            $totalCredits = $totalCredits->plus($line->amount);
        }
    }

    // Gross Salary (1,000,000) = Deductions (100k+50k+50+30k) + Net (819,950)
    // 1,000,000 = 1,000,000
    expect($totalDebits->isEqualTo($totalCredits))->toBeTrue();
    expect($totalDebits->isEqualTo(Money::of(1000000, 'IQD')))->toBeTrue();
});
