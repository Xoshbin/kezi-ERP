<?php

use App\Actions\Loans\CalculateEIRAction;
use App\Actions\Loans\ComputeLoanScheduleAction;
use App\Enums\Loans\ScheduleMethod;
use App\Models\LoanAgreement;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('computes EIR and adjusts interest components', function () {
    $code = $this->company->currency->code;

    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $code),
        'loan_date' => now()->startOfMonth(),
        'start_date' => now()->startOfMonth()->addMonth(),
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
        'eir_enabled' => true,
    ]);

    // Capitalized origination fee 200
    $loan->feeLines()->create([
        'date' => now()->toDateString(),
        'type' => 'origination',
        'amount' => Money::of('200', $code),
        'capitalize' => true,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    app(CalculateEIRAction::class)->execute($loan);

    $loan->refresh()->load('scheduleEntries');

    // EIR per-period rate should be > 1% (nominal monthly) and < 2%
    expect($loan->eir_rate)->toBeFloat()->toBeGreaterThan(0.01)->toBeLessThan(0.02);

    $first = $loan->scheduleEntries()->where('sequence', 1)->first();

    $expectedFirstInterest = Money::of(9800 * $loan->eir_rate, $code, null, \Brick\Math\RoundingMode::HALF_UP);

    expect(abs($first->interest_component->getAmount()->toFloat() - $expectedFirstInterest->getAmount()->toFloat()))
        ->toBeLessThan(0.05);

    // After applying EIR across all periods, carrying amount should be near zero
    $last = $loan->scheduleEntries()->orderByDesc('sequence')->first();
    expect($last->outstanding_balance_after->isZero())->toBeTrue();
});

