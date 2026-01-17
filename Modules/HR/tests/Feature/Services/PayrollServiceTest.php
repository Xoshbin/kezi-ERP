<?php

use App\Models\User;
use Brick\Money\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Modules\Accounting\Services\Accounting\LockDateService;
use Modules\Foundation\Models\Currency;
use Modules\HR\Models\Employee;
use Modules\HR\Models\EmploymentContract;
use Modules\HR\Models\Payroll;
use Modules\HR\Services\HumanResources\PayrollService;
use Tests\Traits\WithConfiguredCompany;

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
    $user = User::factory()->create();
    $user->companies()->attach($this->company);

    // Mock Gate
    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    $periodStart = '2023-01-01';
    $periodEnd = '2023-01-31';
    $payDate = '2023-02-01';

    // Create attendance (optional for now, as service defaults to 0 if none)
    // But let's add some presence to test hours calculation if desired.
    // For now basic test.

    $payroll = $this->service->processPayroll(
        $this->employee,
        $periodStart,
        $periodEnd,
        $payDate,
        $user
    );

    expect($payroll)->toBeInstanceOf(Payroll::class)
        ->and($payroll->net_salary->getAmount()->toInt())->toBeGreaterThan(0)
        ->and($payroll->base_salary->getAmount()->toInt())->toBe(1000000)
        ->and($payroll->status)->toBe('draft');
});

test('it calculates proration for partial month', function () {
    $user = User::factory()->create();
    $user->companies()->attach($this->company);

    Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('create', Payroll::class)->andReturn(true);

    // 15 days in Jan (Jan 1 to Jan 15)
    $periodStart = '2023-01-01';
    $periodEnd = '2023-01-15';
    $payDate = '2023-02-01';

    $payroll = $this->service->processPayroll(
        $this->employee,
        $periodStart,
        $periodEnd,
        $payDate,
        $user
    );

    // Jan has 31 days. 15 days / 31 days ~= 0.4838
    // 1,000,000 * 15/31 = 483,870.96 -> 483,871 (HALF_UP in service with 3 decimals) or 483,870.968

    $expectedBase = (1000000 * 15) / 31;
    $actualBase = $payroll->base_salary->getAmount()->toFloat();

    // Allow small rounding diff
    expect($actualBase)->toBeGreaterThan(480000)
        ->and($actualBase)->toBeLessThan(490000);
});
