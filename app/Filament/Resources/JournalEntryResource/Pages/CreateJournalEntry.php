<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use App\Models\Company;
use App\Models\LockDate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Replicate period lock check from your service
        $entryDate = \Carbon\Carbon::parse($data['entry_date']);
        $lockDate = LockDate::where('company_id', $data['company_id'])->first();
        if ($lockDate && $entryDate->lte($lockDate->locked_until)) {
            throw ValidationException::withMessages([
                'data.entry_date' => 'The accounting period is locked and cannot be modified.',
            ]);
        }

        // Get lines data from the form state
        $lines = $this->form->getState()['lines'] ?? [];

        // Perform balance validation
        $totalDebit = collect($lines)->sum(fn($line) => $line['debit'] ?? 0);
        $totalCredit = collect($lines)->sum(fn($line) => $line['credit'] ?? 0);

        if (bccomp((string)$totalDebit, (string)$totalCredit, 2) !== 0) {
            throw ValidationException::withMessages([
                'data.lines' => 'The total debits must equal the total credits.',
            ]);
        }

        // Add calculated totals to the main record's data
        $data['total_debit'] = $totalDebit;
        $data['total_credit'] = $totalCredit;

        // Handle default currency if not provided
        if (empty($data['currency_id']) && !empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            if ($company) {
                $data['currency_id'] = $company->currency_id;
            }
        }

        return $data;
    }
}
