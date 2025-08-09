<?php

namespace App\Enums\Accounting;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Get the translated label for the account type.
     */
    public function label(): string
    {
        return __('enums.account_type.' . $this->value);
    }
}
