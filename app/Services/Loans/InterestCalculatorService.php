<?php

namespace App\Services\Loans;

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
        $currency = $principal->getCurrency()->getCurrencyCode();
        if ($annualRatePct <= 0) {
            return $principal->dividedBy($numberOfPayments, RoundingMode::HALF_UP);
        }
        $r = ($annualRatePct / 100.0) / $paymentsPerYear;
        $p = $principal->getAmount()->toFloat();
        $payment = $p * $r / (1 - pow(1 + $r, -$numberOfPayments));
        // Ensure currency-scale rounding (e.g., 2dp for USD, 3dp for IQD)
        return Money::of($payment, $currency, null, RoundingMode::HALF_UP);
    }
}

