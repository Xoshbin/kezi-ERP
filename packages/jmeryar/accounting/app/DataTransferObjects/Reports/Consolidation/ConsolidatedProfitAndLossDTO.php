<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports\Consolidation;

use Brick\Money\Money;
use Illuminate\Support\Collection;
use Jmeryar\Accounting\DataTransferObjects\Reports\ReportLineDTO;

readonly class ConsolidatedProfitAndLossDTO
{
    /**
     * @param  Collection<int, ReportLineDTO>  $incomeLines
     * @param  Collection<int, ReportLineDTO>  $expenseLines
     */
    public function __construct(
        public Collection $incomeLines,
        public Money $totalIncome,
        public Collection $expenseLines,
        public Money $totalExpenses,
        public Money $netProfit,
    ) {}
}
