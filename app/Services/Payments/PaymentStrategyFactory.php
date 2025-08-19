<?php

namespace App\Services\Payments;

use App\Enums\Payments\PaymentPurpose;
use App\Services\Payments\Strategies\PaymentStrategy;
use App\Services\Payments\Strategies\SettlementStrategy;
use App\Services\Payments\Strategies\DirectPaymentStrategy;
use InvalidArgumentException;

class PaymentStrategyFactory
{
    /**
     * Create the appropriate payment strategy based on the payment purpose.
     */
    public static function make(string|PaymentPurpose $purpose): PaymentStrategy
    {
        $purposeValue = $purpose instanceof PaymentPurpose ? $purpose->value : $purpose;
        
        return match ($purposeValue) {
            PaymentPurpose::Settlement->value => app(SettlementStrategy::class),
            PaymentPurpose::Loan->value,
            PaymentPurpose::CapitalInjection->value,
            PaymentPurpose::ExpenseClaim->value,
            PaymentPurpose::TaxPayment->value,
            PaymentPurpose::AssetPurchase->value => app(DirectPaymentStrategy::class),
            default => throw new InvalidArgumentException("Invalid payment purpose provided: {$purposeValue}"),
        };
    }
}
