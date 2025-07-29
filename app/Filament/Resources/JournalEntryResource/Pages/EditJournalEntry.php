<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use App\Services\JournalEntryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

     protected function mutateFormDataBeforeFill(array $data): array
    {
        // 1. Eager load the lines relationship from the database.
        $this->record->loadMissing('lines');

        // 2. Convert the collection of lines into a plain array for the Repeater.
        $linesData = $this->record->lines->map(function ($line) {
            return [
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'analytic_account_id' => $line->analytic_account_id,
                'description' => $line->description,
                // Convert Money objects back to plain numeric strings for the form fields.
                // The '?->' null-safe operator prevents errors if a value is null.
                'debit' => $line->debit?->getAmount()->toFloat(),
                'credit' => $line->credit?->getAmount()->toFloat(),
            ];
        })->toArray();

        // 3. Add this array to the form data under the 'lines' key.
        $data['lines'] = $linesData;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    $journalEntryService = app(JournalEntryService::class);
                    $journalEntryService->delete($record);
                    $this->redirect(JournalEntryResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $journalEntryService = app(JournalEntryService::class);
        return $journalEntryService->update($record, $data);
    }
}
