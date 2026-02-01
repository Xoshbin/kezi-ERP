<?php

namespace Kezi\Payment\Enums\Payments;

enum PaymentMethod: string
{
    case Manual = 'manual';
    case Check = 'check';
    case BankTransfer = 'bank_transfer';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Cash = 'cash';
    case WireTransfer = 'wire_transfer';
    case ACH = 'ach';
    case SEPA = 'sepa';
    case OnlinePayment = 'online_payment';

    /**
     * Get the translated label for the payment method.
     */
    public function label(): string
    {
        return __('enums.payment_method.'.$this->value);
    }

    /**
     * Get the icon for the payment method.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Manual => 'heroicon-m-pencil-square',
            self::Check => 'heroicon-m-document-text',
            self::BankTransfer => 'heroicon-m-building-library',
            self::CreditCard => 'heroicon-m-credit-card',
            self::DebitCard => 'heroicon-m-credit-card',
            self::Cash => 'heroicon-m-banknotes',
            self::WireTransfer => 'heroicon-m-arrow-path',
            self::ACH => 'heroicon-m-arrow-path-rounded-square',
            self::SEPA => 'heroicon-m-globe-europe-africa',
            self::OnlinePayment => 'heroicon-m-computer-desktop',
        };
    }

    /**
     * Get the color for the payment method badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Check => 'blue',
            self::BankTransfer => 'green',
            self::CreditCard => 'purple',
            self::DebitCard => 'purple',
            self::Cash => 'yellow',
            self::WireTransfer => 'indigo',
            self::ACH => 'cyan',
            self::SEPA => 'emerald',
            self::OnlinePayment => 'orange',
        };
    }

    /**
     * Get payment methods suitable for inbound payments.
     *
     * @return array<int, self>
     */
    public static function inboundMethods(): array
    {
        return [
            self::Manual,
            self::Check,
            self::BankTransfer,
            self::CreditCard,
            self::DebitCard,
            self::Cash,
            self::WireTransfer,
            self::ACH,
            self::SEPA,
            self::OnlinePayment,
        ];
    }

    /**
     * Get payment methods suitable for outbound payments.
     *
     * @return array<int, self>
     */
    public static function outboundMethods(): array
    {
        return [
            self::Manual,
            self::Check,
            self::BankTransfer,
            self::WireTransfer,
            self::ACH,
            self::SEPA,
        ];
    }

    /**
     * Check if this payment method requires bank reconciliation.
     */
    public function requiresReconciliation(): bool
    {
        return match ($this) {
            self::Manual, self::Cash => false,
            default => true,
        };
    }

    /**
     * Check if this payment method supports batch processing.
     */
    public function supportsBatchProcessing(): bool
    {
        return match ($this) {
            self::BankTransfer, self::WireTransfer, self::ACH, self::SEPA => true,
            default => false,
        };
    }
}
