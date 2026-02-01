<?php

namespace Jmeryar\Payment\Actions\LetterOfCredit;

use App\Models\Company;
use Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryAction;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Payment\Models\LCCharge;
use RuntimeException;

class CreateJournalEntryForLCChargeAction
{
    public function __construct(
        private readonly CreateJournalEntryAction $createJournalEntryAction
    ) {}

    public function execute(LCCharge $charge): JournalEntry
    {
        $company = Company::findOrFail($charge->company_id);
        $lc = $charge->letterOfCredit;

        $journalId = $company->default_bank_journal_id;
        $creditAccountId = $company->default_bank_account_id;

        if (! $journalId || ! $creditAccountId) {
            throw new RuntimeException('Default bank journal or bank account not configured for the company.');
        }

        $lineDTOs = [
            // Debit: LC Charge Expense
            new CreateJournalEntryLineDTO(
                account_id: $charge->account_id,
                debit: $charge->amount,
                credit: \Brick\Money\Money::zero($charge->currency->code),
                description: $charge->description ?: "LC Charge: {$charge->charge_type->value}",
                partner_id: null,
                analytic_account_id: null,
            ),
            // Credit: Bank Account
            new CreateJournalEntryLineDTO(
                account_id: $creditAccountId,
                debit: \Brick\Money\Money::zero($charge->currency->code),
                credit: $charge->amount,
                description: $charge->description ?: "LC Charge: {$charge->charge_type->value}",
                partner_id: null,
                analytic_account_id: null,
            ),
        ];

        $dto = new CreateJournalEntryDTO(
            company_id: $charge->company_id,
            journal_id: $journalId,
            currency_id: $charge->currency_id,
            entry_date: $charge->charge_date->format('Y-m-d'),
            reference: "LC Charge - {$lc->lc_number}",
            description: $charge->description ?: "Bank charge for LC {$lc->lc_number}",
            created_by_user_id: $lc->created_by_user_id,
            is_posted: true,
            lines: $lineDTOs,
            source_type: LCCharge::class,
            source_id: $charge->id,
        );

        return $this->createJournalEntryAction->execute($dto);
    }
}
