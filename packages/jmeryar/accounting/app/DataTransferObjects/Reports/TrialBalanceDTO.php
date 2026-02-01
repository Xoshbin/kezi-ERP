<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class TrialBalanceDTO
{
    /**
     * @param  Collection<int, TrialBalanceLineDTO>  $reportLines
     */
    public function __construct(
        public Collection $reportLines,
        public Money $totalDebit,
        public Money $totalCredit,
        public bool $isBalanced,
    ) {}
}
