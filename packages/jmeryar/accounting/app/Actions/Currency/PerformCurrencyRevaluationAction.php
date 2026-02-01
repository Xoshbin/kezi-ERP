<?php

namespace Jmeryar\Accounting\Actions\Currency;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Jmeryar\Accounting\DataTransferObjects\Currency\ForeignCurrencyBalanceDTO;
use Jmeryar\Accounting\DataTransferObjects\Currency\PerformRevaluationDTO;
use Jmeryar\Accounting\Enums\Currency\RevaluationStatus;
use Jmeryar\Accounting\Models\CurrencyRevaluation;
use Jmeryar\Accounting\Models\CurrencyRevaluationLine;
use Jmeryar\Accounting\Services\CurrencyRevaluationService;
use Jmeryar\Foundation\Services\SequenceService;
use RuntimeException;

/**
 * PerformCurrencyRevaluationAction
 *
 * Creates a currency revaluation record with calculated unrealized gains/losses
 * for foreign currency balances as of a specific date.
 */
class PerformCurrencyRevaluationAction
{
    public function __construct(
        protected CurrencyRevaluationService $revaluationService,
        protected CreateJournalEntryAction $createJournalEntryAction,
        protected SequenceService $sequenceService,
    ) {}

    public function execute(PerformRevaluationDTO $dto): CurrencyRevaluation
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::findOrFail($dto->company_id);
            $baseCurrencyCode = $company->currency->code;
            $zero = Money::zero($baseCurrencyCode);

            // Create the revaluation header
            $revaluation = CurrencyRevaluation::create([
                'company_id' => $dto->company_id,
                'created_by_user_id' => $dto->created_by_user_id,
                'revaluation_date' => $dto->revaluation_date,
                'reference' => $this->generateReference($company),
                'description' => $dto->description ?? 'Period-end currency revaluation',
                'status' => RevaluationStatus::Draft,
                'total_gain' => $zero,
                'total_loss' => $zero,
                'net_adjustment' => $zero,
            ]);

            $totalGain = $zero;
            $totalLoss = $zero;

            // Get eligible accounts
            $accounts = $this->revaluationService->getEligibleAccounts($company, $dto->account_ids);

            foreach ($accounts as $account) {
                // Get foreign currency balances for each account
                $balances = $this->revaluationService->getForeignCurrencyBalances(
                    $account,
                    $company,
                    $dto->revaluation_date,
                    $dto->currency_ids,
                );

                foreach ($balances as $balance) {
                    /** @var ForeignCurrencyBalanceDTO $balance */
                    $result = $this->revaluationService->calculateUnrealizedGainLoss(
                        $balance,
                        $company,
                        $dto->revaluation_date,
                    );

                    // Skip if no adjustment needed
                    if ($result['adjustment']->isZero()) {
                        continue;
                    }

                    // Create revaluation line
                    CurrencyRevaluationLine::create([
                        'currency_revaluation_id' => $revaluation->id,
                        'account_id' => $balance->account_id,
                        'currency_id' => $balance->currency_id,
                        'partner_id' => $balance->partner_id,
                        'foreign_currency_balance' => $balance->foreign_balance,
                        'historical_rate' => $balance->weighted_avg_rate,
                        'current_rate' => $result['current_rate'],
                        'book_value' => $balance->book_value,
                        'revalued_amount' => $result['revalued_amount'],
                        'adjustment_amount' => $result['adjustment'],
                    ]);

                    // Track totals
                    if ($result['adjustment']->isPositive()) {
                        $totalGain = $totalGain->plus($result['adjustment']);
                    } else {
                        $totalLoss = $totalLoss->plus($result['adjustment']->abs());
                    }
                }
            }

            // Update totals
            $netAdjustment = $totalGain->minus($totalLoss);
            $revaluation->update([
                'total_gain' => $totalGain,
                'total_loss' => $totalLoss,
                'net_adjustment' => $netAdjustment,
            ]);

            // Auto-post if requested and there are adjustments
            if ($dto->auto_post && $revaluation->lines()->exists()) {
                $this->postRevaluation($revaluation, $company, $dto->created_by_user_id);
            }

            return $revaluation->fresh(['lines']);
        });
    }

    protected function generateReference(Company $company): string
    {
        $count = CurrencyRevaluation::where('company_id', $company->id)
            ->whereYear('revaluation_date', now()->year)
            ->count();

        return 'REVAL-'.now()->format('Y').'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Post the revaluation by creating the journal entry.
     */
    public function postRevaluation(CurrencyRevaluation $revaluation, Company $company, int $userId): void
    {
        if (! $revaluation->canBePosted()) {
            throw new RuntimeException('This revaluation cannot be posted.');
        }

        $baseCurrencyCode = $company->currency->code;
        $lines = [];

        $gainLossAccountId = $company->default_gain_loss_account_id;
        if (! $gainLossAccountId) {
            throw new RuntimeException('Company must have a default gain/loss account configured.');
        }

        $bankJournalId = $company->default_bank_journal_id;
        if (! $bankJournalId) {
            throw new RuntimeException('Company must have a default bank journal configured.');
        }

        $zero = Money::zero($baseCurrencyCode);
        $totalGainLossAdjustment = $zero;

        // Create journal entry lines for each revaluation line
        foreach ($revaluation->lines as $line) {
            $adjustment = $line->adjustment_amount;

            if ($adjustment->isZero()) {
                continue;
            }

            // For assets (receivables): positive adjustment = gain (debit asset, credit gain)
            // For liabilities (payables): positive adjustment = loss (debit loss, credit liability)
            $isDebit = $adjustment->isPositive();
            $absAmount = $adjustment->abs();

            // Adjust the asset/liability account
            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $line->account_id,
                debit: $isDebit ? $absAmount : $zero,
                credit: $isDebit ? $zero : $absAmount,
                description: "FX revaluation - {$line->currency->code}",
                partner_id: $line->partner_id,
                analytic_account_id: null,
            );

            $totalGainLossAdjustment = $totalGainLossAdjustment->plus($adjustment);
        }

        // Create the offsetting entry to the gain/loss account
        if (! $totalGainLossAdjustment->isZero()) {
            $isNetGain = $totalGainLossAdjustment->isPositive();
            $absTotal = $totalGainLossAdjustment->abs();

            $lines[] = new CreateJournalEntryLineDTO(
                account_id: $gainLossAccountId,
                debit: $isNetGain ? $zero : $absTotal,
                credit: $isNetGain ? $absTotal : $zero,
                description: 'Unrealized FX '.($isNetGain ? 'gain' : 'loss'),
                partner_id: null,
                analytic_account_id: null,
            );
        }

        if (empty($lines)) {
            return;
        }

        // Create the journal entry
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $bankJournalId,
            currency_id: $company->currency_id,
            entry_date: $revaluation->revaluation_date->toDateString(),
            reference: $revaluation->reference,
            description: $revaluation->description ?? 'Currency revaluation',
            created_by_user_id: $userId,
            is_posted: true,
            lines: $lines,
            source_type: CurrencyRevaluation::class,
            source_id: $revaluation->id,
        );

        $journalEntry = $this->createJournalEntryAction->execute($journalEntryDTO);

        // Update revaluation status
        $revaluation->update([
            'journal_entry_id' => $journalEntry->id,
            'status' => RevaluationStatus::Posted,
            'posted_at' => now(),
        ]);
    }
}
