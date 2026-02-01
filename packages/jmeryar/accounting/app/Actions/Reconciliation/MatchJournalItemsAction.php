<?php

namespace Jmeryar\Accounting\Actions\Reconciliation;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Jmeryar\Accounting\Enums\Reconciliation\ReconciliationType;
use Jmeryar\Accounting\Exceptions\Reconciliation\AccountNotReconcilableException;
use Jmeryar\Accounting\Exceptions\Reconciliation\AlreadyReconciledException;
use Jmeryar\Accounting\Exceptions\Reconciliation\PartnerMismatchException;
use Jmeryar\Accounting\Exceptions\Reconciliation\ReconciliationDisabledException;
use Jmeryar\Accounting\Exceptions\Reconciliation\UnbalancedReconciliationException;
use Jmeryar\Accounting\Models\JournalEntryLine;
use Jmeryar\Accounting\Models\Reconciliation;

/**
 * Action for matching journal entry lines in manual reconciliation processes.
 *
 * This action handles the core business logic for reconciling journal entry lines,
 * particularly for Accounts Receivable and Accounts Payable reconciliation.
 * It enforces all business rules and maintains data integrity.
 */
class MatchJournalItemsAction
{
    /**
     * Execute the reconciliation of journal entry lines.
     *
     * @param  array<int, int>  $journalLineIds  Array of JournalEntryLine IDs to reconcile
     * @param  ReconciliationType  $reconciliationType  Type of reconciliation
     * @param  string|null  $reference  Optional reference for the reconciliation
     * @param  string|null  $description  Optional description for the reconciliation
     * @return Reconciliation The created reconciliation record
     *
     * @throws ReconciliationDisabledException
     * @throws AccountNotReconcilableException
     * @throws UnbalancedReconciliationException
     * @throws PartnerMismatchException
     * @throws AlreadyReconciledException
     * @throws InvalidArgumentException
     */
    public function execute(
        array $journalLineIds,
        ReconciliationType $reconciliationType = ReconciliationType::ManualArAp,
        ?string $reference = null,
        ?string $description = null,
    ): Reconciliation {
        // Validate input
        if (empty($journalLineIds)) {
            throw new InvalidArgumentException('No journal entry lines provided for reconciliation.');
        }

        // Fetch all journal entry lines with necessary relationships
        $journalLines = JournalEntryLine::whereIn('id', $journalLineIds)
            ->with([
                'journalEntry.company',
                'account',
                'partner',
                'reconciliations',
            ])
            ->get();

        if ($journalLines->count() !== count($journalLineIds)) {
            throw new InvalidArgumentException('One or more journal entry lines not found.');
        }

        // Get the company from the first line (all should belong to same company due to tenancy)
        /** @var JournalEntryLine $first */
        $first = $journalLines->first();
        $company = $first->journalEntry->company;

        // Perform all validations in order
        $this->validateGlobalReconciliationSetting($company);
        $this->validateLinesNotAlreadyReconciled($journalLines);
        $this->validateAccountsAllowReconciliation($journalLines);
        $this->validateLinesArePosted($journalLines);
        $this->validateBalance($journalLines);

        // For A/R and A/P reconciliation, validate partner consistency
        if ($reconciliationType === ReconciliationType::ManualArAp) {
            $this->validatePartnerConsistency($journalLines);
        }

        // Create the reconciliation record
        return DB::transaction(function () use ($journalLines, $company, $reconciliationType, $reference, $description) {
            $reconciliation = Reconciliation::create([
                'company_id' => $company->id,
                'reconciliation_type' => $reconciliationType,
                'reference' => $reference,
                'description' => $description,
            ]);

            // Attach the journal entry lines to the reconciliation
            $reconciliation->journalEntryLines()->attach($journalLines->pluck('id'));

            return $reconciliation;
        });
    }

    /**
     * Validate that reconciliation is enabled globally for the company.
     */
    private function validateGlobalReconciliationSetting(Company $company): void
    {
        if (! $company->enable_reconciliation) {
            throw new ReconciliationDisabledException;
        }
    }

    /**
     * Validate that none of the journal entry lines are already reconciled.
     *
     * @param  Collection<int, JournalEntryLine>  $journalLines
     */
    private function validateLinesNotAlreadyReconciled(Collection $journalLines): void
    {
        $reconciledLineIds = $journalLines->filter(fn (JournalEntryLine $line) => $line->isReconciled())
            ->pluck('id')
            ->toArray();

        if (! empty($reconciledLineIds)) {
            throw new AlreadyReconciledException($reconciledLineIds);
        }
    }

    /**
     * Validate that all accounts allow reconciliation.
     *
     * @param  Collection<int, JournalEntryLine>  $journalLines
     */
    private function validateAccountsAllowReconciliation(Collection $journalLines): void
    {
        $nonReconcilableAccounts = $journalLines->filter(
            fn (JournalEntryLine $line) => ! $line->account->allow_reconciliation
        )->pluck('account.code')->unique()->toArray();

        if (! empty($nonReconcilableAccounts)) {
            throw new AccountNotReconcilableException($nonReconcilableAccounts);
        }
    }

    /**
     * Validate that all journal entry lines belong to posted journal entries.
     *
     * @param  Collection<int, JournalEntryLine>  $journalLines
     */
    private function validateLinesArePosted(Collection $journalLines): void
    {
        $unpostedLines = $journalLines->filter(
            fn (JournalEntryLine $line) => ! $line->journalEntry->is_posted
        );

        if ($unpostedLines->isNotEmpty()) {
            throw new InvalidArgumentException(
                'Cannot reconcile journal entry lines from unposted journal entries.'
            );
        }
    }

    /**
     * Validate that the sum of debits equals the sum of credits.
     *
     * @param  Collection<int, JournalEntryLine>  $journalLines
     */
    private function validateBalance(Collection $journalLines): void
    {
        // Get the first line to determine the currency for zero amounts
        /** @var JournalEntryLine $firstLine */
        $firstLine = $journalLines->first();
        $currency = $firstLine->journalEntry->company->currency->code;

        // Sum the Money objects properly
        $totalDebits = $journalLines->reduce(function (Money $carry, JournalEntryLine $line): Money {
            return $carry->plus($line->debit);
        }, Money::of(0, $currency));

        $totalCredits = $journalLines->reduce(function (Money $carry, JournalEntryLine $line): Money {
            return $carry->plus($line->credit);
        }, Money::of(0, $currency));

        if (! $totalDebits->isEqualTo($totalCredits)) {
            throw new UnbalancedReconciliationException($totalDebits, $totalCredits);
        }
    }

    /**
     * Validate that all journal entry lines belong to the same partner.
     * This is required for A/R and A/P reconciliation.
     *
     * @param  Collection<int, JournalEntryLine>  $journalLines
     */
    private function validatePartnerConsistency(Collection $journalLines): void
    {
        $partnerIds = $journalLines->pluck('partner_id')->filter()->unique();

        // Allow reconciliation of lines with no partner (e.g., bank fees) if all lines have no partner
        if ($partnerIds->isEmpty()) {
            return;
        }

        // If there are partners, all lines must have the same partner
        if ($partnerIds->count() > 1) {
            $partnerNames = $journalLines->whereIn('partner_id', $partnerIds)
                ->pluck('partner.name')
                ->unique()
                ->toArray();

            throw new PartnerMismatchException($partnerNames);
        }

        // Check for mixed partner/no-partner lines
        $linesWithoutPartner = $journalLines->whereNull('partner_id');
        if ($linesWithoutPartner->isNotEmpty() && $partnerIds->isNotEmpty()) {
            throw new PartnerMismatchException(['Mixed partner and non-partner lines']);
        }
    }
}
