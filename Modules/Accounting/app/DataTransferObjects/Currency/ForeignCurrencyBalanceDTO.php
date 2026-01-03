<?php

namespace Modules\Accounting\DataTransferObjects\Currency;

use Brick\Money\Money;

/**
 * DTO representing a foreign currency balance for revaluation.
 */
readonly class ForeignCurrencyBalanceDTO
{
    /**
     * @param  int  $account_id  The account with the foreign currency balance
     * @param  int  $currency_id  The foreign currency
     * @param  int|null  $partner_id  Optional partner associated with this balance
     * @param  Money  $foreign_balance  Balance in foreign currency
     * @param  Money  $book_value  Current book value in base currency
     * @param  float  $weighted_avg_rate  Weighted average exchange rate from original transactions
     */
    public function __construct(
        public int $account_id,
        public int $currency_id,
        public ?int $partner_id,
        public Money $foreign_balance,
        public Money $book_value,
        public float $weighted_avg_rate,
    ) {}
}

