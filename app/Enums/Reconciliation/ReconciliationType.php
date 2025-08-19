<?php

namespace App\Enums\Reconciliation;

/**
 * Enum ReconciliationType
 *
 * Defines the different types of reconciliation processes available in the system.
 * Each type represents a specific reconciliation workflow with its own business rules.
 */
enum ReconciliationType: string
{
    /**
     * Manual reconciliation of Accounts Receivable and Accounts Payable.
     * Used for matching customer payments against invoices or vendor payments against bills.
     */
    case ManualArAp = 'manual_ar_ap';

    /**
     * Bank statement reconciliation.
     * Used for matching bank statement lines with payment records.
     */
    case BankStatement = 'bank_statement';

    /**
     * General manual reconciliation.
     * Used for other types of manual reconciliation not covered by specific types.
     */
    case ManualGeneral = 'manual_general';

    /**
     * Get the human-readable label for the reconciliation type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ManualArAp => __('reconciliation.type.manual_ar_ap'),
            self::BankStatement => __('reconciliation.type.bank_statement'),
            self::ManualGeneral => __('reconciliation.type.manual_general'),
        };
    }

    /**
     * Get the description for the reconciliation type.
     */
    public function description(): string
    {
        return match ($this) {
            self::ManualArAp => __('reconciliation.type.manual_ar_ap_description'),
            self::BankStatement => __('reconciliation.type.bank_statement_description'),
            self::ManualGeneral => __('reconciliation.type.manual_general_description'),
        };
    }

    /**
     * Get all available reconciliation types as an array.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }
}
