<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class WithholdingTaxReportDTO
{
    /**
     * @param  Collection<int, WithholdingTaxReportLineDTO>  $vendorLines
     * @param  Collection<int, WithholdingTaxReportTypeLineDTO>  $typeLines
     */
    public function __construct(
        public Collection $vendorLines,
        public Collection $typeLines,
        public Money $totalBaseAmount,
        public Money $totalWithheldAmount,
        public int $totalCertificates,
        public int $uncertifiedEntries,
    ) {}
}
