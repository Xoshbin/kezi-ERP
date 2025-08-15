<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use App\Actions\Accounting\CreateJournalEntryAction;
use App\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use App\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Models\Company;
use App\Models\Journal;
use App\Models\LockDate;
use Brick\Money\Money;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lineDTOs = [];
        if (isset($data['lines']) && is_array($data['lines'])) {
            $currency = Currency::find($data['currency_id']);
            if ($currency) {
                foreach ($data['lines'] as $line) {
                    $lineDTOs[] = new CreateJournalEntryLineDTO(
                        account_id: $line['account_id'],
                        debit: Money::of($line['debit'] ?? 0, $currency->code),
                        credit: Money::of($line['credit'] ?? 0, $currency->code),
                        description: $line['description'],
                        partner_id: $line['partner_id'],
                        analytic_account_id: $line['analytic_account_id']
                    );
                }
            }
        }
        $data['lines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: \Filament\Facades\Filament::getTenant()->id,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'],
            description: $data['description'],
            created_by_user_id: $data['created_by_user_id'],
            is_posted: false,
            lines: $data['lines']
        );

        return app(CreateJournalEntryAction::class)->execute($journalEntryDTO);
    }
}
