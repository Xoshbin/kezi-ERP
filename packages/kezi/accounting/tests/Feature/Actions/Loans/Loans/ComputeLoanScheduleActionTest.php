<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Loans\ComputeLoanScheduleAction;
use Kezi\Accounting\Enums\Loans\ScheduleMethod;
use Kezi\Accounting\Models\LoanAgreement;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    // Ensure USD currency exists and is company's base
    $this->company->refresh();
});

it('computes annuity schedule for a 12-month loan', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $this->company->currency->code),
        'loan_date' => now()->startOfMonth(),
        'start_date' => now()->startOfMonth()->addMonth(),
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $loan->load('scheduleEntries');
    expect($loan->scheduleEntries)->toHaveCount(12);

    // First payment interest: 10000 * 1% = 100; payment ≈ 888.49; principal ≈ 788.49
    $first = $loan->scheduleEntries()->orderBy('sequence')->first();
    $code = $this->company->currency->code;
    expect((string) $first->interest_component->getAmount())->toBe((string) Money::of('100', $code)->getAmount());
    // Allow small rounding tolerance (e.g., 0.01 in minor units)
    $expectedPayment = Money::of('888.49', $code);
    expect($first->payment_amount->minus($expectedPayment)->abs()->isLessThanOrEqualTo(Money::of('0.01', $code)))->toBeTrue();
    $expectedPrincipal = $expectedPayment->minus(Money::of('100', $code));
    expect($first->principal_component->minus($expectedPrincipal)->abs()->isLessThanOrEqualTo(Money::of('0.01', $code)))->toBeTrue();
});

it('computes straight-line principal schedule', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('12000', $this->company->currency->code),
        'loan_date' => now()->startOfMonth(),
        'start_date' => now()->startOfMonth()->addMonth(),
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::StraightLinePrincipal,
        'interest_rate' => 12.0,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $loan->load('scheduleEntries');
    expect($loan->scheduleEntries)->toHaveCount(12);

    $first = $loan->scheduleEntries()->orderBy('sequence')->first();
    $code = $this->company->currency->code;
    expect((string) $first->principal_component->getAmount())->toBe((string) Money::of('1000', $code)->getAmount());
    expect((string) $first->interest_component->getAmount())->toBe((string) Money::of('120', $code)->getAmount());
    expect((string) $first->payment_amount->getAmount())->toBe((string) Money::of('1120', $code)->getAmount());
});
