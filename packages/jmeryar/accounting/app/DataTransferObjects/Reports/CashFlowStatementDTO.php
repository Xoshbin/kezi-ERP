<?php

namespace Jmeryar\Accounting\DataTransferObjects\Reports;

use Brick\Money\Money;
use Illuminate\Support\Collection;

readonly class CashFlowStatementDTO
{
    /**
     * @param  Collection<int, CashFlowLineDTO>  $operatingLines
     * @param  Collection<int, CashFlowLineDTO>  $investingLines
     * @param  Collection<int, CashFlowLineDTO>  $financingLines
     */
    public function __construct(
        public Collection $operatingLines,
        public Money $totalOperating,
        public Collection $investingLines,
        public Money $totalInvesting,
        public Collection $financingLines,
        public Money $totalFinancing,
        public Money $netChangeInCash,
        public Money $beginningCash,
        public Money $endingCash,
    ) {}
}
