<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Actions\Accounting\UpdateJournalEntryAction;
use App\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use App\DataTransferObjects\Accounting\UpdateJournalEntryLineDTO;
use App\Filament\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The "Post Entry" button
            Actions\Action::make('post')
                ->label(__('journal_entry.post_entry'))
                ->color('success')
                ->requiresConfirmation()
                // This action is only visible if the entry is a draft.
                ->visible(fn (JournalEntry $record): bool => !$record->is_posted)
                ->action(function (JournalEntry $record): void {
                    // First, save any pending changes the user made in the form.
                    $this->save();

                    // Then, call the posting service.
                    $journalEntryService = app(JournalEntryService::class);
                    try {
                        $journalEntryService->post($record);
                        Notification::make()->title(__('journal_entry.entry_posted_successfully'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('journal_entry.error_posting_entry'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    $journalEntryService = app(JournalEntryService::class);
                    $journalEntryService->delete($record);
                    $this->redirect(JournalEntryResource::getUrl('index'));
                }),
        ];
    }

    // This method is for loading the line data into the form. It is correct.
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

    // This method now correctly only handles updating a DRAFT entry's data.
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
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
            journalEntry: $record,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'],
            description: $data['description'],
            // The status is no longer controlled by the form, so we pass its existing state.
            is_posted: $record->is_posted,
            lines: $lineDTOs
        );

        return (new UpdateJournalEntryAction())->execute($updateDTO);
    }
}
