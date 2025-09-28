<?php

namespace Modules\Accounting\Tests\Unit\Services;

use Carbon\Carbon;
use App\Models\Company;
use Modules\Accounting\Models\LockDate;
use Modules\Accounting\Enums\Accounting\LockDateType;
use Modules\Accounting\Exceptions\PeriodIsLockedException;
use Modules\Accounting\Services\Accounting\LockDateService;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->service = new LockDateService();
});

test('it does not throw an exception if the period is not locked', function () {
    // Given
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
    ]);

    $dateToCheck = '2024-01-01';

    // When & Then
    $this->service->enforce($this->company, Carbon::parse($dateToCheck));
    // No exception means the test passes.
    expect(true)->toBeTrue();
});

test('it throws an exception if the period is locked', function () {
    // Given
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => LockDateType::AllUsers->value,
        'locked_until' => '2023-12-31',
    ]);

    $dateToCheck = '2023-11-30';

    // When & Then
    $this->service->enforce($this->company, Carbon::parse($dateToCheck));
})->throws(PeriodIsLockedException::class, 'The period is locked until 2023-12-31.');

test('it does not throw an exception if no lock date is set', function () {
    // Given
    $dateToCheck = '2023-01-01';

    // When & Then
    $this->service->enforce($this->company, Carbon::parse($dateToCheck));
    // No exception means the test passes.
    expect(true)->toBeTrue();
});

test('it throws an exception if the date is the same as the lock date', function () {
    // Given
    $lockDate = '2023-12-31';
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'lock_type' => LockDateType::HardLock->value,
        'locked_until' => $lockDate,
    ]);

    $dateToCheck = '2023-12-31';

    // When & Then
    $this->service->enforce($this->company, Carbon::parse($dateToCheck));
})->throws(PeriodIsLockedException::class);
