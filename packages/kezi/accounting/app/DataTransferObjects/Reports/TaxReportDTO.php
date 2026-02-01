<?php

namespace Kezi\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class TaxReportDTO
{
    /**
     * @param  Collection<int, TaxReportLineDTO>  $outputTaxLines
     * @param  Collection<int, TaxReportLineDTO>  $inputTaxLines
     */
    public function __construct(
        public Collection $outputTaxLines,
        public Collection $inputTaxLines,
        public Money $totalOutputTax,
        public Money $totalInputTax,
        public Money $netTaxPayable,
    ) {}
}
