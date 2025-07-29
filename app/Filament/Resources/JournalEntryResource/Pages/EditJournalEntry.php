<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\UpdateJournalEntryAction;
use App\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use App\DataTransferObjects\Accounting\UpdateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntryResource;
use App\Services\JournalEntryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    // This method for loading data is correct and should remain as is.
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('lines');
        $linesData = $this->record->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'analytic_account_id' => $line->analytic_account_id,
                'description' => $line->description,
                'debit' => $line->debit?->getAmount()->toFloat(),
                'credit' => $line->credit?->getAmount()->toFloat(),
            ];
        })->toArray();
        $data['lines'] = $linesData;
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    // We can refactor this to an Action later if desired.
                    $journalEntryService = app(JournalEntryService::class);
                    $journalEntryService->delete($record);
                    $this->redirect(JournalEntryResource::getUrl('index'));
                }),
        ];
    }

    // --- REPLACE the old handleRecordUpdate with this new version ---
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. Create the DTOs from the raw form data.
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new UpdateJournalEntryLineDTO(
                account_id: $line['account_id'],
                debit: $line['debit'],
                credit: $line['credit'],
                description: $line['description'],
                partner_id: $line['partner_id'],
                analytic_account_id: $line['analytic_account_id']
            );
        }

        $updateDTO = new UpdateJournalEntryDTO(
            journalEntry: $record, // Pass the actual model to be updated
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'],
            description: $data['description'],
            is_posted: $data['is_posted'],
            lines: $lineDTOs
        );

        // 2. Execute the action with the DTO.
        $action = new UpdateJournalEntryAction();

        return $action->execute($updateDTO);
    }
}
