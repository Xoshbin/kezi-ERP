<?php

namespace Jmeryar\Accounting\Actions\Deferred;

use App\Models\Company;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Jmeryar\Accounting\Models\DeferredLine;
use Jmeryar\Foundation\Services\CurrencyConverterService;

class ProcessDeferredEntriesAction
{
    public function __construct(
        protected CreateJournalEntryAction $createJournalEntryAction,
        protected CurrencyConverterService $currencyConverter // Assuming needed even if same currency
    ) {}

    public function execute(DeferredLine $line): void
    {
        if ($line->status !== 'draft') {
            return;
        }

        $item = $line->deferredItem;
        $company = $item->company;

        // Determine Accounts based on Type
        // Revenue: Dr Deferred (Liability), Cr Recognition (Revenue)
        // Expense: Dr Recognition (Expense), Cr Deferred (Asset)

        if ($item->type === 'revenue') {
            $debitAccount = $item->deferred_account_id;
            $creditAccount = $item->recognition_account_id;
        } else {
            $debitAccount = $item->recognition_account_id;
            $creditAccount = $item->deferred_account_id;
        }

        // Create Journal Entry
        // We use the item's amount currency. Assuming it matches company currency for now (BaseCurrencyMoneyCast)
        // If not, we'd need exchange rates. But deferred items are usually fixed in base currency upon creation.

        $line1 = new CreateJournalEntryLineDTO(
            account_id: $debitAccount,
            debit: $line->amount,
            credit: $line->amount->multipliedBy(0), // Zero money object
            description: "Deferred Recognition: {$item->name}",
            partner_id: null, // Could link to source partner?
            analytic_account_id: null,
        );

        $line2 = new CreateJournalEntryLineDTO(
            account_id: $creditAccount,
            debit: $line->amount->multipliedBy(0),
            credit: $line->amount,
            description: "Deferred Recognition: {$item->name}",
            partner_id: null,
            analytic_account_id: null,
        );

        // Find Journal? We need a specific Journal for Deferrals or use General?
        // Ideally, configuration on Company/Settings.
        // For now, let's look for a Journal of type 'general' or 'miscellaneous'.
        $journal = $company->journals()->where('type', 'miscellaneous')->first(); // Fallback

        if (! $journal) {
            // Create or throw. Using ID 1 or failsafe?
            // Ideally passed in or configured.
            // Leaving generic ID or throwing for now if strict.
            throw new \Exception('No General Journal found for Deferral posting.');
        }

        $dto = new CreateJournalEntryDTO(
            company_id: $company->id,
            journal_id: $journal->id,
            currency_id: $company->currency_id,
            entry_date: $line->date,
            reference: "Deferred Recog {$line->date->format('Y-m')}",
            description: "Deferred Recognition: {$item->name} ({$line->date->format('M Y')})",
            lines: [$line1, $line2],
            is_posted: true, // Auto-post
            source_type: DeferredLine::class,
            source_id: $line->id,
            created_by_user_id: 1 // System user or auth user?
        );

        $journalEntry = $this->createJournalEntryAction->execute($dto);

        // Update Line
        $line->update([
            'status' => 'posted',
            'journal_entry_id' => $journalEntry->id,
        ]);
    }
}
