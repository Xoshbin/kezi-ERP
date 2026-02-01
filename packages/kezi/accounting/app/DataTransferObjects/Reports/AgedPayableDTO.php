<?php

namespace Kezi\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class AgedPayableDTO
{
    /**
     * @param  Collection<int, AgedPayableLineDTO>  $reportLines
     */
    public function __construct(
        public Collection $reportLines,
        public Money $totalCurrent,
        public Money $totalBucket1_30,
        public Money $totalBucket31_60,
        public Money $totalBucket61_90,
        public Money $totalBucket90_plus,
        public Money $grandTotalDue,
    ) {}
}
