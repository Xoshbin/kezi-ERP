<?php

namespace Tests\Unit\Services;

use App\Exceptions\PeriodIsLockedException;
use App\Models\Company;
use App\Models\LockDate;
use App\Services\AccountingValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->service = new AccountingValidationService();
});

test('it does not throw an exception if the period is not locked', function () {
    // Given
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
    ]);

    $dateToCheck = '2024-01-01';

    // When & Then
    $this->service->checkIfPeriodIsLocked($this->company->id, $dateToCheck);
    // No exception means the test passes.
    expect(true)->toBeTrue();
});

test('it throws an exception if the period is locked', function () {
    // Given
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => '2023-12-31',
    ]);

    $dateToCheck = '2023-11-30';

    // When & Then
    $this->service->checkIfPeriodIsLocked($this->company->id, $dateToCheck);
})->throws(PeriodIsLockedException::class, 'The accounting period is locked and cannot be modified.');

test('it does not throw an exception if no lock date is set', function () {
    // Given
    $dateToCheck = '2023-01-01';

    // When & Then
    $this->service->checkIfPeriodIsLocked($this->company->id, $dateToCheck);
    // No exception means the test passes.
    expect(true)->toBeTrue();
});

test('it throws an exception if the date is the same as the lock date', function () {
    // Given
    $lockDate = '2023-12-31';
    LockDate::factory()->create([
        'company_id' => $this->company->id,
        'locked_until' => $lockDate,
    ]);

    $dateToCheck = '2023-12-31';

    // When & Then
    $this->service->checkIfPeriodIsLocked($this->company->id, $dateToCheck);
})->throws(PeriodIsLockedException::class);
