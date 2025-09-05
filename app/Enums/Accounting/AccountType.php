<?php

namespace App\Enums\Accounting;

/**
 * Defines the specific type of an account, driving automated accounting logic
 * and the structure of financial reports.
 *
 * @see https://www.odoo.com/documentation/18.0/applications/finance/accounting/get_started/chart_of_accounts.html
 */
enum AccountType: string
{
    // Balance Sheet - Assets
    case Receivable = 'receivable';
    case BankAndCash = 'bank_and_cash';
    case CurrentAssets = 'current_assets';
    case NonCurrentAssets = 'non_current_assets';
    case Prepayments = 'prepayments';
    case FixedAssets = 'fixed_assets';

    // Balance Sheet - Liabilities
    case Payable = 'payable';
    case CreditCard = 'credit_card';
    case CurrentLiabilities = 'current_liabilities';
    case NonCurrentLiabilities = 'non_current_liabilities';

    // Balance Sheet - Equity
    case Equity = 'equity';
    case CurrentYearEarnings = 'current_year_earnings';

    // Profit & Loss - Income
    case Income = 'income';
    case OtherIncome = 'other_income';

    // Profit & Loss - Expense
    case Expense = 'expense';
    case Depreciation = 'depreciation';
    case CostOfRevenue = 'cost_of_revenue';

    // Other
    case OffBalanceSheet = 'off_balance_sheet';

    /**
     * Get the translated label for the account type.
     */
    public function label(): string
    {
        return __('enums.account_type.'.$this->value);
    }

    /**
     * Check if the account type is an Asset.
     */
    public function isAsset(): bool
    {
        return in_array($this, [
            self::Receivable,
            self::BankAndCash,
            self::CurrentAssets,
            self::NonCurrentAssets,
            self::Prepayments,
            self::FixedAssets,
        ]);
    }

    /**
     * Check if the account type is a Liability.
     */
    public function isLiability(): bool
    {
        return in_array($this, [
            self::Payable,
            self::CreditCard,
            self::CurrentLiabilities,
            self::NonCurrentLiabilities,
        ]);
    }

    /**
     * Check if the account type is Equity.
     */
    public function isEquity(): bool
    {
        return in_array($this, [
            self::Equity,
            self::CurrentYearEarnings,
        ]);
    }

    /**
     * Get all Asset account types.
     *
     * @return array<int, AccountType>
     */
    public static function assetTypes(): array
    {
        return [
            self::Receivable,
            self::BankAndCash,
            self::CurrentAssets,
            self::NonCurrentAssets,
            self::Prepayments,
            self::FixedAssets,
        ];
    }

    /**
     * Get all Liability account types.
     *
     * @return array<int, AccountType>
     */
    public static function liabilityTypes(): array
    {
        return [
            self::Payable,
            self::CreditCard,
            self::CurrentLiabilities,
            self::NonCurrentLiabilities,
        ];
    }

    /**
     * Get all Equity account types.
     *
     * @return array<int, AccountType>
     */
    public static function equityTypes(): array
    {
        return [
            self::Equity,
            self::CurrentYearEarnings,
        ];
    }

    /**
     * Get all Balance Sheet account types (Assets, Liabilities, and Equity).
     *
     * @return array<int, AccountType>
     */
    public static function balanceSheetTypes(): array
    {
        return array_merge(
            self::assetTypes(),
            self::liabilityTypes(),
            self::equityTypes()
        );
    }
}
