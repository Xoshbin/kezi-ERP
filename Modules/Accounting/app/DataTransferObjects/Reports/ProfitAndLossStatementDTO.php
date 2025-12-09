<?php

namespace Modules\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class ProfitAndLossStatementDTO
{
    /**
     * @param  Collection<int, ReportLineDTO>  $revenueLines
     * @param  Collection<int, ReportLineDTO>  $expenseLines
     */
    public function __construct(
        public Collection $revenueLines,
        public Money $totalRevenue,
        public Collection $expenseLines,
        public Money $totalExpenses,
        public Money $netIncome,
    ) {}
}
