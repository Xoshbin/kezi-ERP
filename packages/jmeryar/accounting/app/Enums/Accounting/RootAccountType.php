<?php

namespace Jmeryar\Accounting\Enums\Accounting;

/**
 * Defines the five fundamental account types according to GAAP/IFRS standards.
 * These are the root types that all specific account types map to.
 */
enum RootAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Get the normal balance for this root type.
     * Assets and Expenses normally have debit balances.
     * Liabilities, Equity, and Income normally have credit balances.
     */
    public function normalBalance(): string
    {
        return match ($this) {
            self::Asset, self::Expense => 'debit',
            self::Liability, self::Equity, self::Income => 'credit',
        };
    }

    /**
     * Determine which financial statement this root type appears on.
     */
    public function appearsOn(): string
    {
        return match ($this) {
            self::Asset, self::Liability, self::Equity => 'balance_sheet',
            self::Income, self::Expense => 'income_statement',
        };
    }

    /**
     * Get the starting digit for account codes of this root type.
     */
    public function codeRangeStart(): string
    {
        return match ($this) {
            self::Asset => '1',
            self::Liability => '2',
            self::Equity => '3',
            self::Income => '4',
            self::Expense => '5',
        };
    }

    /**
     * Get the translated label for the root account type.
     */
    public function label(): string
    {
        return __('accounting::enums.root_account_type.'.$this->value);
    }
}
