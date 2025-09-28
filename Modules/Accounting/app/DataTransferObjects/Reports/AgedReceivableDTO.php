<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class AgedReceivableDTO
{
    /**
     * @param  Collection<int, AgedReceivableLineDTO>  $reportLines
     */
    public function __construct(
        public Collection $reportLines,
        public Money $totalCurrent,
        public Money $totalBucket1_30,
        public Money $totalBucket31_60,
        public Money $totalBucket61_90,
        public Money $totalBucket90_plus,
        public Money $grandTotalDue,
    ) {
    }
}
