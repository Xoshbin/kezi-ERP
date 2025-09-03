<?php

namespace App\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class BalanceSheetDTO
{
    /**
     * @param  Collection<int, ReportLineDTO>  $assetLines
     * @param  Collection<int, ReportLineDTO>  $liabilityLines
     * @param  Collection<int, ReportLineDTO>  $equityLines
     */
    public function __construct(
        public Collection $assetLines,
        public Money $totalAssets,
        public Collection $liabilityLines,
        public Money $totalLiabilities,
        public Collection $equityLines,
        public Money $retainedEarnings, // Historical equity
        public Money $currentYearEarnings, // Net income for the current period
        public Money $totalEquity,
        public Money $totalLiabilitiesAndEquity,
    ) {}
}
