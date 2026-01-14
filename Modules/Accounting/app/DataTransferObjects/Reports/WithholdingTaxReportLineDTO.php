<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;

readonly class WithholdingTaxReportLineDTO
{
    public function __construct(
        public int $vendorId,
        public string $vendorName,
        public Money $baseAmount,
        public Money $withheldAmount,
        public int $entryCount,
        public int $certificateCount,
    ) {}
}
