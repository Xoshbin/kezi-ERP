<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntryResource;
use App\Models\Company;
use App\Models\LockDate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    // This method is now simplified, acting only as a validation guard before creation.
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Period Lock Validation
        $entryDate = \Carbon\Carbon::parse($data['entry_date']);
        $lockDate = LockDate::where('company_id', $data['company_id'])->first();
        if ($lockDate && $entryDate->lte($lockDate->locked_until)) {
            throw ValidationException::withMessages([
                'data.entry_date' => 'The accounting period is locked and cannot be modified.',
            ]);
        }

        // 2. Balance Validation
        $lines = $data['lines'] ?? [];
        $totalDebit = collect($lines)->sum(fn($line) => $line['debit'] ?? 0);
        $totalCredit = collect($lines)->sum(fn($line) => $line['credit'] ?? 0);

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'data.lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // 3. Ensure Currency is set
        if (empty($data['currency_id']) && !empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            if ($company) {
                $data['currency_id'] = $company->currency_id;
            }
        }

        return $data;
    }

    // This method now cleanly translates form data to DTOs and executes the Action.
    protected function handleRecordCreation(array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new CreateJournalEntryLineDTO(
                account_id: $line['account_id'],
                debit: $line['debit'],
                credit: $line['credit'],
                description: $line['description'],
                partner_id: $line['partner_id'],
                analytic_account_id: $line['analytic_account_id']
            );
        }

        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: $data['company_id'],
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'],
            description: $data['description'],
            created_by_user_id: $data['created_by_user_id'],
            is_posted: $data['is_posted'],
            lines: $lineDTOs
        );

        $action = new CreateJournalEntryAction();

        return $action->execute($journalEntryDTO);
    }
}
