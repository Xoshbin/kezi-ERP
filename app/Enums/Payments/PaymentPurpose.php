<?php

namespace App\Enums\Payments;

enum PaymentPurpose: string
{
    case Settlement = 'settlement';
    case Loan = 'loan';
    case CapitalInjection = 'capital_injection';
    case ExpenseClaim = 'expense_claim';
    case TaxPayment = 'tax_payment';
    case AssetPurchase = 'asset_purchase';
    case Payroll = 'payroll';

    /**
     * Get the translated label for the payment purpose.
     */
    public function label(): string
    {
        return __('enums.payment_purpose.' . $this->value);
    }

    /**
     * Get payment purposes that require a counterpart account.
     * Settlement payments use document-based accounts (AR/AP).
     */
    public function requiresCounterpartAccount(): bool
    {
        return $this !== self::Settlement;
    }

    /**
     * Get payment purposes available for inbound payments.
     */
    public static function inboundPurposes(): array
    {
        return [
            self::Settlement,
            self::Loan,
            self::CapitalInjection,
        ];
    }

    /**
     * Get payment purposes available for outbound payments.
     */
    public static function outboundPurposes(): array
    {
        return [
            self::Settlement,
            self::Loan,
            self::ExpenseClaim,
            self::TaxPayment,
            self::AssetPurchase,
            self::Payroll,
        ];
    }
}
