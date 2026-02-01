<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports\Consolidation;

use Brick\Money\Money;
use Illuminate\Support\Collection;
use Jmeryar\Accounting\DataTransferObjects\Reports\ReportLineDTO;

readonly class ConsolidatedBalanceSheetDTO
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
        public Money $retainedEarnings,
        public Money $currentYearEarnings,
        public Money $totalEquity,
        public Money $totalLiabilitiesAndEquity,
    ) {}
}
