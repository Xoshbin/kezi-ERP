<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

/**
 * DTO for a single line in the FX Gain/Loss Report.
 */
readonly class FxGainLossLineDTO
{
    /**
     * @param  string  $date  Transaction or revaluation date
     * @param  string  $reference  Document reference
     * @param  string  $description  Description of the transaction
     * @param  string  $currency_code  Foreign currency code
     * @param  Money  $foreign_amount  Amount in foreign currency
     * @param  float  $original_rate  Original exchange rate
     * @param  float  $settlement_rate  Settlement or revaluation rate
     * @param  Money  $gain_loss_amount  Gain (positive) or loss (negative) amount
     * @param  string  $type  Type: 'realized' or 'unrealized'
     * @param  string|null  $account_code  Related account code
     * @param  string|null  $account_name  Related account name
     * @param  string|null  $partner_name  Related partner name
     * @param  int|null  $source_id  Source document ID
     * @param  string|null  $source_type  Source document type
     */
    public function __construct(
        public string $date,
        public string $reference,
        public string $description,
        public string $currency_code,
        public Money $foreign_amount,
        public float $original_rate,
        public float $settlement_rate,
        public Money $gain_loss_amount,
        public string $type,
        public ?string $account_code = null,
        public ?string $account_name = null,
        public ?string $partner_name = null,
        public ?int $source_id = null,
        public ?string $source_type = null,
    ) {}

    public function isGain(): bool
    {
        return $this->gain_loss_amount->isPositive();
    }

    public function isLoss(): bool
    {
        return $this->gain_loss_amount->isNegative();
    }

    public function isRealized(): bool
    {
        return $this->type === 'realized';
    }

    public function isUnrealized(): bool
    {
        return $this->type === 'unrealized';
    }
}
