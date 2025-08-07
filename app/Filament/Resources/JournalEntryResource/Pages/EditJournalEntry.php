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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Eager-load the necessary relationships on the record
        $this->record->load('lines.journalEntry.currency');

        $totalDebit = 0;
        $totalCredit = 0;

        $lines = $this->record->lines->map(function ($line) use (&$totalDebit, &$totalCredit) {
            $currency = $line->journalEntry->currency;
            $debitRaw = $line->getRawOriginal('debit');
            $creditRaw = $line->getRawOriginal('credit');

            $debit = $debitRaw > 0 ? (string) ($debitRaw / (10 ** $currency->decimal_places)) : '0';
            $credit = $creditRaw > 0 ? (string) ($creditRaw / (10 ** $currency->decimal_places)) : '0';

            $totalDebit += (float)$debit;
            $totalCredit += (float)$credit;

            return [
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'analytic_account_id' => $line->analytic_account_id,
                'description' => $line->description,
                'debit' => $debit,
                'credit' => $credit,
            ];
        })->toArray();

        $data['lines'] = $lines;
        $data['total_debit'] = (string) $totalDebit;
        $data['total_credit'] = (string) $totalCredit;
        $data['balance'] = (string) ($totalDebit - $totalCredit);

        return $data;
    }

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
            is_posted: $record->is_posted,
            lines: $lineDTOs
        );

        return app(UpdateJournalEntryAction::class)->execute($updateDTO);
    }
}
