<?php

namespace Kezi\Accounting\Services\Reports\Consolidation;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kezi\Accounting\DataTransferObjects\Reports\Consolidation\ConsolidatedProfitAndLossDTO;
use Kezi\Accounting\DataTransferObjects\Reports\Consolidation\ConsolidatedTrialBalanceLineDTO;
use Kezi\Accounting\DataTransferObjects\Reports\ReportLineDTO;
use Kezi\Accounting\Enums\Accounting\AccountType;

class ConsolidatedProfitAndLossService
{
    public function __construct(
        protected ConsolidatedTrialBalanceService $tbService,
    ) {}

    public function generate(Company $parentCompany, Carbon $asOfDate): ConsolidatedProfitAndLossDTO
    {
        // 1. Generate Consolidated Trial Balance
        $tb = $this->tbService->generate($parentCompany, $asOfDate);
        $currency = $parentCompany->currency->code;
        $zero = Money::zero($currency);

        // 2. Map P&L Lines
        $incomeLines = $this->mapLines($tb->reportLines, [AccountType::Income, AccountType::OtherIncome], $currency, true);
        $expenseLines = $this->mapLines($tb->reportLines, [AccountType::Expense, AccountType::Depreciation, AccountType::CostOfRevenue], $currency);

        $totalIncome = $this->sumLines($incomeLines, $zero);
        $totalExpenses = $this->sumLines($expenseLines, $zero);
        $netProfit = $totalIncome->minus($totalExpenses);

        return new ConsolidatedProfitAndLossDTO(
            incomeLines: $incomeLines,
            totalIncome: $totalIncome,
            expenseLines: $expenseLines,
            totalExpenses: $totalExpenses,
            netProfit: $netProfit
        );
    }

    /**
     * @param  Collection<int, ConsolidatedTrialBalanceLineDTO>  $allLines
     * @return Collection<int, ReportLineDTO>
     */
    protected function mapLines(Collection $allLines, array $types, string $currency, bool $negate = false): Collection
    {
        return $allLines->filter(fn ($line) => in_array($line->accountType, $types))
            ->map(function (ConsolidatedTrialBalanceLineDTO $line) use ($negate) {
                // Balance = Debit - Credit
                $balance = $line->consolidatedDebit->minus($line->consolidatedCredit);

                if ($negate) {
                    $balance = $balance->negated();
                }

                return new ReportLineDTO(
                    accountId: null,
                    accountCode: $line->accountCode,
                    accountName: $line->accountName,
                    balance: $balance
                );
            })->values();
    }

    protected function sumLines(Collection $lines, Money $zero): Money
    {
        return $lines->reduce(
            fn (Money $carry, ReportLineDTO $line) => $carry->plus($line->balance),
            $zero
        );
    }
}
