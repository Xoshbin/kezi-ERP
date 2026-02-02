<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Actions\Loans\ComputeLoanScheduleAction;
use Kezi\Accounting\Enums\Loans\ScheduleMethod;
use Kezi\Accounting\Models\LoanAgreement;
use Kezi\Accounting\Services\Loans\InterestCalculatorService;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('recomputes annuity payment after mid-term rate change', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('10000', $this->company->currency->code),
        'loan_date' => now()->startOfMonth(),
        'start_date' => now()->startOfMonth()->addMonth(),
        'duration_months' => 12,
        'schedule_method' => ScheduleMethod::Annuity,
        'interest_rate' => 12.0,
    ]);

    // Add a rate change effective at month 7 to 18%
    $loan->rateChanges()->create([
        'effective_date' => now()->startOfMonth()->addMonths(7),
        'annual_rate' => 18.0,
    ]);

    app(ComputeLoanScheduleAction::class)->execute($loan);

    $loan->load('scheduleEntries');
    expect($loan->scheduleEntries)->toHaveCount(12);

    // Base monthly payment for 12% on 10000/12 should be around 888.49
    $first = $loan->scheduleEntries()->where('sequence', 1)->first();
    $code = $this->company->currency->code;
    expect(abs($first->payment_amount->getAmount()->toFloat() - Money::of('888.49', $code)->getAmount()->toFloat()))->toBeLessThan(0.01);

    // Compute expected remaining balance after 6 payments at 12%
    /** @var InterestCalculatorService $calc */
    $calc = app(InterestCalculatorService::class);
    $pmt12 = $calc->annuityPayment(Money::of('10000', $code), 12.0, 12, 12);
    $balance = Money::of('10000', $code);
    $r = 0.12 / 12;
    for ($i = 0; $i < 6; $i++) {
        $interest = Money::of($balance->getAmount()->toFloat() * $r, $code, null, \Brick\Math\RoundingMode::HALF_UP);
        $principalComponent = $pmt12->minus($interest);
        $balance = $balance->minus($principalComponent);
    }

    // Expected new payment for remaining 6 periods at 18%
    $expectedNewPayment = $calc->annuityPayment($balance, 18.0, 12, 6);

    $seventh = $loan->scheduleEntries()->where('sequence', 7)->first();
    expect(abs($seventh->payment_amount->getAmount()->toFloat() - $expectedNewPayment->getAmount()->toFloat()))->toBeLessThan(0.02);
});

it('persists fee lines on a loan', function () {
    $loan = LoanAgreement::factory()->for($this->company)->create([
        'currency_id' => $this->company->currency_id,
        'principal_amount' => Money::of('5000', $this->company->currency->code),
    ]);

    $loan->feeLines()->create([
        'date' => now()->toDateString(),
        'type' => 'origination',
        'amount' => Money::of('100', $this->company->currency->code),
        'capitalize' => true,
    ]);

    $loan->refresh();
    expect($loan->feeLines)->toHaveCount(1);
    $amt = $loan->feeLines->first()->amount->getAmount()->toFloat();
    expect(abs($amt - 100.00))->toBeLessThan(0.001);
});
