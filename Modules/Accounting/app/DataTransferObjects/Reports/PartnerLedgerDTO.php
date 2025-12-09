<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class PartnerLedgerDTO
{
    /**
     * @param  Collection<int, PartnerLedgerTransactionLineDTO>  $transactionLines
     */
    public function __construct(
        public int $partnerId,
        public string $partnerName,
        public string $currency,
        public Money $openingBalance,
        public Collection $transactionLines,
        public Money $closingBalance,
    ) {}
}
