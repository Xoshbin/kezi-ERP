<?php

namespace Kezi\Accounting\Services\Loans;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class InterestCalculatorService
{
    /**
     * Compute equal periodic annuity payment using nominal annual rate.
     * Payment per period = P * r / (1 - (1 + r)^-n)
     * where r = annualRatePct/100 / paymentsPerYear
     */
    public function annuityPayment(Money $principal, float $annualRatePct, int $paymentsPerYear, int $numberOfPayments): Money
    {
        $currencyCode = $principal->getCurrency()->getCurrencyCode();

        if ($annualRatePct <= 0) {
            return $principal->dividedBy($numberOfPayments, RoundingMode::HALF_UP);
        }

        // r = (annualRatePct / 100) / paymentsPerYear
        $r = BigDecimal::of($annualRatePct)
            ->dividedBy(100, 10, RoundingMode::HALF_UP)
            ->dividedBy($paymentsPerYear, 10, RoundingMode::HALF_UP);

        $p = $principal->getAmount();

        // Formula: P * r / (1 - (1 + r)^-n)
        // Note: pow() and intermediate 1 + r are still float for simplicity since high precision pow() is complex,
        // but we keep the principal and final result in BigDecimal/Money context.
        $rFloat = $r->toFloat();
        $denominator = 1 - pow(1 + $rFloat, -$numberOfPayments);

        if ($denominator == 0) {
            return $principal->dividedBy($numberOfPayments, RoundingMode::HALF_UP);
        }

        $paymentAmount = $p->multipliedBy($rFloat)
            ->dividedBy($denominator, 10, RoundingMode::HALF_UP);

        return Money::of($paymentAmount, $currencyCode, null, RoundingMode::HALF_UP);
    }
}
