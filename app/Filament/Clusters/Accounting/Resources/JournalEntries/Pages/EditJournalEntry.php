<?php

namespace App\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use App\Actions\Accounting\UpdateJournalEntryAction;
use App\DataTransferObjects\Accounting\UpdateJournalEntryDTO;
use App\DataTransferObjects\Accounting\UpdateJournalEntryLineDTO;
use App\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\JournalEntryService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * @property JournalEntry $record
 */
class EditJournalEntry extends EditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The "Post Entry" button
            Action::make('post')
                ->label(__('journal_entry.post_entry'))
                ->color('success')
                ->requiresConfirmation()
                // This action is only visible if the entry is a draft.
                ->visible(fn (JournalEntry $record): bool => ! $record->is_posted)
                ->action(function (JournalEntry $record): void {
                    // First, save any pending changes the user made in the form.
                    $this->save();

                    // Then, call the posting service.
                    $journalEntryService = app(JournalEntryService::class);
                    try {
                        $journalEntryService->post($record);
                        Notification::make()->title(__('journal_entry.entry_posted_successfully'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('journal_entry.error_posting_entry'))->body($e->getMessage())->danger()->send();
                    }
                }),

            DeleteAction::make()
                ->action(function (JournalEntry $record): void {
                    $journalEntryService = app(JournalEntryService::class);
                    $journalEntryService->delete($record);
                    $this->redirect(JournalEntryResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var JournalEntry $record */
        $record = $this->record;

        // Ensure necessary relationships are loaded
        $record->loadMissing('currency', 'company.currency', 'lines.journalEntry.currency', 'lines.originalCurrency');

        // Resolve currency code safely, prefer eager-loaded relation
        $currencyModel = $record->relationLoaded('currency') ? $record->getRelation('currency') : $record->currency()->first();
        $currencyCode = $currencyModel->code ?? ($record->company->currency->code ?? 'USD');

        // Initialize totals
        $totalDebit = Money::zero($currencyCode);
        $totalCredit = Money::zero($currencyCode);

        $lines = $record->lines->map(function (JournalEntryLine $line) use (&$totalDebit, &$totalCredit, $currencyCode, $record): array {
            // Ensure the line has proper currency context by setting missing fields if needed
            if (! $line->original_currency_id) {
                $line->original_currency_id = $record->currency_id;
                $line->currency_id = $record->currency_id;
                $line->exchange_rate_at_transaction = 1.0;
                $line->save();
            }

            // Determine the correct amounts to display based on currency context
            $debitMoney = Money::zero($currencyCode);
            $creditMoney = Money::zero($currencyCode);

            // Check if this is a multi-currency transaction with original amounts
            $hasOriginalAmounts = ($line->original_currency_amount ?? null) && ($line->original_currency_id ?? null);
            $isMultiCurrency = $hasOriginalAmounts && $line->original_currency_id != $record->company->currency_id;

            if ($isMultiCurrency) {
                // Multi-currency entry: use original amounts in transaction currency
                $originalCurrency = $line->original_currency_id ? Currency::find($line->original_currency_id) : null;
                if ($originalCurrency && $originalCurrency->code === $currencyCode) {
                    // Determine if this line was a debit or credit based on base currency amounts
                    $isDebit = $line->debit->isPositive();
                    if ($isDebit) {
                        $debitMoney = $line->original_currency_amount;
                    } else {
                        $creditMoney = $line->original_currency_amount;
                    }
                }
            } else {
                // Single currency entry: use the base currency amounts directly
                $debitMoney = $line->debit;
                $creditMoney = $line->credit;
            }

            // Ensure currency consistency before adding to totals
            if ($debitMoney->isPositive()) {
                if ($debitMoney->getCurrency()->getCurrencyCode() === $currencyCode) {
                    $totalDebit = $totalDebit->plus($debitMoney);
                }
            }
            if ($creditMoney->isPositive()) {
                if ($creditMoney->getCurrency()->getCurrencyCode() === $currencyCode) {
                    $totalCredit = $totalCredit->plus($creditMoney);
                }
            }

            return [
                'account_id' => $line->account_id,
                'partner_id' => $line->partner_id,
                'analytic_account_id' => $line->analytic_account_id,
                'description' => $line->description,
                'debit' => $debitMoney,
                'credit' => $creditMoney,
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
        if (! $record instanceof JournalEntry) {
            return $record;
        }
        /** @var JournalEntry $record */
        $lineDTOs = [];

        // Handle case where lines might not be present in the data (e.g., when calling actions)
        if (isset($data['lines']) && is_array($data['lines'])) {
            foreach ($data['lines'] as $line) {
                $lineDTOs[] = new UpdateJournalEntryLineDTO(
                    account_id: $line['account_id'],
                    debit: $this->convertMoneyToString($line['debit']),
                    credit: $this->convertMoneyToString($line['credit']),
                    description: $line['description'] ?? null,
                    partner_id: $line['partner_id'] ?? null,
                    analytic_account_id: $line['analytic_account_id'] ?? null
                );
            }
        } else {
            // If lines are not provided, use existing lines from the record
            $record->load('lines');
            foreach ($record->lines as $line) {
                $lineDTOs[] = new UpdateJournalEntryLineDTO(
                    account_id: $line->account_id,
                    debit: $this->convertMoneyToString($line->debit),
                    credit: $this->convertMoneyToString($line->credit),
                    description: $line->description,
                    partner_id: $line->partner_id,
                    analytic_account_id: $line->analytic_account_id
                );
            }
        }

        $updateDTO = new UpdateJournalEntryDTO(
            journalEntry: $record,
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
            is_posted: $record->is_posted,
            lines: $lineDTOs
        );

        return app(UpdateJournalEntryAction::class)->execute($updateDTO);
    }

    /**
     * Convert Money object or other value to string for DTO
     */
    private function convertMoneyToString(mixed $value): string
    {
        if ($value instanceof Money) {
            return $value->getAmount()->__toString();
        }

        if ($value === null || $value === '') {
            return '0';
        }

        return (string) $value;
    }
}
