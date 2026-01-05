<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class WithholdingTaxReportTypeLineDTO
{
    public function __construct(
        public int $typeId,
        public string $typeName,
        public float $rate,
        public Money $baseAmount,
        public Money $withheldAmount,
        public int $entryCount,
    ) {}
}
