<?php

namespace App\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class AgedPayableLineDTO
{
    public function __construct(
        public int $partnerId,
        public string $partnerName,
        public Money $current,
        public Money $bucket1_30,
        public Money $bucket31_60,
        public Money $bucket61_90,
        public Money $bucket90_plus,
        public Money $totalDue,
    ) {}
}
