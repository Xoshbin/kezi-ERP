<?php

use App\Services\Loans\InterestCalculatorService;
use Brick\Money\Money;

it('computes annuity payment correctly for monthly schedule', function () {
    /** @var InterestCalculatorService $svc */
    $svc = app(InterestCalculatorService::class);

    $principal = Money::of('10000', 'USD');
    $annualRatePct = 12.0; // 12% annual nominal
    $paymentsPerYear = 12;
    $numberOfPayments = 12;

    $payment = $svc->annuityPayment(
        principal: $principal,
        annualRatePct: $annualRatePct,
        paymentsPerYear: $paymentsPerYear,
        numberOfPayments: $numberOfPayments,
    );

    expect($payment->isEqualTo(Money::of('888.49', 'USD')))->toBeTrue();
});
