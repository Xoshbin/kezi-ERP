<?php

namespace App\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class TaxReportLineDTO
{
    public function __construct(
        public int $taxId,
        public string $taxName,
        public float $taxRate,
        public Money $netAmount,
        public Money $taxAmount,
    ) {}
}
