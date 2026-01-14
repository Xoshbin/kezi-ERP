<?php

namespace Modules\Accounting\Services\Reports;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\DataTransferObjects\Reports\CashFlowLineDTO;
use Modules\Accounting\DataTransferObjects\Reports\CashFlowStatementDTO;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Enums\Accounting\JournalEntryState;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Support\TranslatableHelper;

/**
 * Generates a Cash Flow Statement using the Indirect Method.
 *
 * The indirect method starts with Net Income and adjusts for:
 * 1. Non-cash items (depreciation)
 * 2. Changes in working capital (receivables, payables, inventory)
 * 3. Investing activities (fixed assets)
 * 4. Financing activities (equity, long-term debt)
 */
class CashFlowStatementService
{
    public function __construct(
        private readonly ProfitAndLossStatementService $plService
    ) {}

    public function generate(Company $company, Carbon $startDate, Carbon $endDate): CashFlowStatementDTO
    {
        $currency = $company->currency->code;
        $zero = Money::zero($currency);

        // Get beginning and ending cash balances
        $beginningCash = $this->getCashBalance($company, $startDate->copy()->subDay());
        $endingCash = $this->getCashBalance($company, $endDate);

        // Get Net Income from P&L
        $plStatement = $this->plService->generate($company, $startDate, $endDate);
        $netIncome = $plStatement->netIncome;

        // Build Operating Activities section using indirect method
        /** @var \Illuminate\Support\Collection<int, CashFlowLineDTO> $operatingLines */
        $operatingLines = collect();

        // Start with Net Income
        $operatingLines->push(new CashFlowLineDTO(
            accountId: null,
            accountCode: null,
            description: __('accounting::reports.net_income'),
            amount: $netIncome
        ));

        // Add back depreciation (non-cash expense)
        $depreciation = $this->getDepreciationForPeriod($company, $startDate, $endDate);
        if (! $depreciation->isZero()) {
            $operatingLines->push(new CashFlowLineDTO(
                accountId: null,
                accountCode: null,
                description: __('accounting::reports.depreciation'),
                amount: $depreciation
            ));
        }

        // Changes in operating assets (increase in asset = use of cash, so negate)
        $operatingAssetChanges = $this->getAccountTypeChanges(
            $company,
            $startDate,
            $endDate,
            AccountType::operatingAssetTypes()
        );
        foreach ($operatingAssetChanges as $change) {
            // Asset increase uses cash, decrease provides cash (negate the balance change)
            $operatingLines->push(new CashFlowLineDTO(
                accountId: $change['account_id'],
                accountCode: $change['account_code'],
                description: $change['account_name'],
                amount: $change['change']->negated()
            ));
        }

        // Changes in operating liabilities (increase in liability = source of cash)
        $operatingLiabilityChanges = $this->getAccountTypeChanges(
            $company,
            $startDate,
            $endDate,
            AccountType::operatingLiabilityTypes()
        );
        foreach ($operatingLiabilityChanges as $change) {
            // Liability increase provides cash (liabilities have credit nature, so negate twice = keep as is)
            $operatingLines->push(new CashFlowLineDTO(
                accountId: $change['account_id'],
                accountCode: $change['account_code'],
                description: $change['account_name'],
                amount: $change['change']->negated()
            ));
        }

        $totalOperating = $this->sumLines($operatingLines, $zero);

        // Build Investing Activities section
        /** @var \Illuminate\Support\Collection<int, CashFlowLineDTO> $investingLines */
        $investingLines = collect();
        $investingChanges = $this->getAccountTypeChanges(
            $company,
            $startDate,
            $endDate,
            AccountType::investingAssetTypes()
        );
        foreach ($investingChanges as $change) {
            // Asset increase = cash outflow (negate)
            $investingLines->push(new CashFlowLineDTO(
                accountId: $change['account_id'],
                accountCode: $change['account_code'],
                description: $change['account_name'],
                amount: $change['change']->negated()
            ));
        }
        $totalInvesting = $this->sumLines($investingLines, $zero);

        // Build Financing Activities section
        /** @var \Illuminate\Support\Collection<int, CashFlowLineDTO> $financingLines */
        $financingLines = collect();
        $financingChanges = $this->getAccountTypeChanges(
            $company,
            $startDate,
            $endDate,
            AccountType::financingTypes()
        );
        foreach ($financingChanges as $change) {
            // Equity/Liability increase = cash inflow (negate the debit-based change)
            $financingLines->push(new CashFlowLineDTO(
                accountId: $change['account_id'],
                accountCode: $change['account_code'],
                description: $change['account_name'],
                amount: $change['change']->negated()
            ));
        }
        $totalFinancing = $this->sumLines($financingLines, $zero);

        // Calculate net change in cash
        $netChangeInCash = $totalOperating->plus($totalInvesting)->plus($totalFinancing);

        return new CashFlowStatementDTO(
            operatingLines: $operatingLines,
            totalOperating: $totalOperating,
            investingLines: $investingLines,
            totalInvesting: $totalInvesting,
            financingLines: $financingLines,
            totalFinancing: $totalFinancing,
            netChangeInCash: $netChangeInCash,
            beginningCash: $beginningCash,
            endingCash: $endingCash,
        );
    }

    /**
     * Get the cash balance as of a specific date.
     */
    private function getCashBalance(Company $company, Carbon $asOfDate): Money
    {
        $currency = $company->currency->code;

        $result = DB::table('journal_entry_lines')
            ->select([
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', array_map(fn ($type) => $type->value, AccountType::cashAccountTypes()))
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->where('journal_entries.entry_date', '<=', $asOfDate->toDateString())
            ->first();

        /** @var null|object{total_debit: numeric-string|null, total_credit: numeric-string|null} $result */
        $totalDebit = (int) ($result?->total_debit ?: 0);
        $totalCredit = (int) ($result?->total_credit ?: 0);

        return Money::ofMinor($totalDebit - $totalCredit, $currency);
    }

    /**
     * Get total depreciation expense for the period.
     */
    private function getDepreciationForPeriod(Company $company, Carbon $startDate, Carbon $endDate): Money
    {
        $currency = $company->currency->code;

        $result = DB::table('journal_entry_lines')
            ->select([
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->where('accounts.type', AccountType::Depreciation->value)
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->whereBetween('journal_entries.entry_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->first();

        /** @var null|object{total_debit: numeric-string|null, total_credit: numeric-string|null} $result */
        $totalDebit = (int) ($result?->total_debit ?: 0);
        $totalCredit = (int) ($result?->total_credit ?: 0);

        // Depreciation is an expense (debit nature), return as positive for add-back
        return Money::ofMinor($totalDebit - $totalCredit, $currency);
    }

    /**
     * Get balance changes for specific account types between period start and end.
     *
     * @param  array<int, AccountType>  $accountTypes
     * @return array<int, array{account_id: int, account_code: string, account_name: string, change: Money}>
     */
    private function getAccountTypeChanges(
        Company $company,
        Carbon $startDate,
        Carbon $endDate,
        array $accountTypes
    ): array {
        if (empty($accountTypes)) {
            return [];
        }

        $currency = $company->currency->code;
        $dayBeforeStart = $startDate->copy()->subDay();

        // Get balances at end of period
        $endBalances = $this->getBalancesByAccountType($company, $endDate, $accountTypes);

        // Get balances at start of period (day before)
        $startBalances = $this->getBalancesByAccountType($company, $dayBeforeStart, $accountTypes);

        // Calculate changes
        $changes = [];
        $accountIds = $endBalances->keys()->merge($startBalances->keys())->unique();

        // Get account models for translated names
        /** @var \Illuminate\Database\Eloquent\Collection<int, Account> $accounts */
        $accounts = Account::where('company_id', $company->id)
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        foreach ($accountIds as $accountId) {
            $account = $accounts->get($accountId);
            if (! $account) {
                continue;
            }

            $endBalance = $endBalances->get($accountId, 0);
            $startBalance = $startBalances->get($accountId, 0);
            $change = $endBalance - $startBalance;

            if ($change != 0) {
                $changes[] = [
                    'account_id' => $accountId,
                    'account_code' => $account->code,
                    'account_name' => TranslatableHelper::getLocalizedValue($account->name),
                    'change' => Money::ofMinor($change, $currency),
                ];
            }
        }

        return $changes;
    }

    /**
     * Get account balances by type as of a specific date.
     *
     * @param  array<int, AccountType>  $accountTypes
     * @return Collection<int, int>
     */
    private function getBalancesByAccountType(
        Company $company,
        Carbon $asOfDate,
        array $accountTypes
    ): Collection {
        $results = DB::table('journal_entry_lines')
            ->select([
                'accounts.id as account_id',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            ])
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('accounts.company_id', $company->id)
            ->whereIn('accounts.type', array_map(fn ($type) => $type->value, $accountTypes))
            ->where('journal_entries.state', JournalEntryState::Posted->value)
            ->where('journal_entries.entry_date', '<=', $asOfDate->toDateString())
            ->groupBy('accounts.id')
            ->get();

        return $results->mapWithKeys(function (object $result): array {
            $totalDebit = (int) ($result->total_debit ?: 0);
            $totalCredit = (int) ($result->total_credit ?: 0);

            return [(int) $result->account_id => $totalDebit - $totalCredit];
        });
    }

    /**
     * Sum all amounts in a collection of CashFlowLineDTO.
     *
     * @param  Collection<int, CashFlowLineDTO>  $lines
     */
    private function sumLines(Collection $lines, Money $zero): Money
    {
        return $lines->reduce(
            fn (Money $carry, CashFlowLineDTO $line) => $carry->plus($line->amount),
            $zero
        );
    }
}
