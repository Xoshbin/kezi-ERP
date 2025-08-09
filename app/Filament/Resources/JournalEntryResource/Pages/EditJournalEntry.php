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
                ->visible(fn(JournalEntry $record): bool => !$record->is_posted)
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
        // Eager-load the necessary relationships
        $this->record->load('currency', 'lines.journalEntry.currency');

        // Get the currency code for creating zero-value money objects
        $currencyCode = $this->record->currency->code;

        // Initialize totals
        $totalDebit = \Brick\Money\Money::zero($currencyCode);
        $totalCredit = \Brick\Money\Money::zero($currencyCode);

        $lines = $this->record->lines->map(function ($line) use (&$totalDebit, &$totalCredit, $currencyCode) {
            // Use the MoneyCast to get Money objects for debit and credit.
            $debitMoney = $line->debit;
            $creditMoney = $line->credit;

            if ($debitMoney) {
                $totalDebit = $totalDebit->plus($debitMoney);
            }
            if ($creditMoney) {
                $totalCredit = $totalCredit->plus($creditMoney);
            }

            return [
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'analytic_account_id' => $line->analytic_account_id,
                'description' => $line->description,
                // -- CHANGE IS HERE --
                // Pass the entire Money object, or a new zero-value one
                'debit' => $debitMoney ?? \Brick\Money\Money::zero($currencyCode),
                'credit' => $creditMoney ?? \Brick\Money\Money::zero($currencyCode),
            ];
        })->toArray();

        $data['lines'] = $lines;
        // The totals can remain strings for display-only fields
        $data['total_debit'] = $totalDebit->getAmount()->__toString();
        $data['total_credit'] = $totalCredit->getAmount()->__toString();
        $data['balance'] = $totalDebit->minus($totalCredit)->getAmount()->__toString();

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
