<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports\Consolidation;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class ConsolidatedTrialBalanceDTO
{
    /**
     * @param  Collection<int, ConsolidatedTrialBalanceLineDTO>  $reportLines
     */
    public function __construct(
        public Collection $reportLines,
        public Money $totalDebit,
        public Money $totalCredit,
        public bool $isBalanced,
    ) {}
}
