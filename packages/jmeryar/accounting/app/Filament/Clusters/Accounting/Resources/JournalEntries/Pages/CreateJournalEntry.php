<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\Pages;

use Brick\Money\Money;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Jmeryar\Accounting\DataTransferObjects\Accounting\CreateJournalEntryLineDTO;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\JournalEntries\JournalEntryResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\Foundation\Models\Currency;
use PDOException;

class CreateJournalEntry extends CreateRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lineDTOs = [];
        if (isset($data['lines']) && is_array($data['lines'])) {
            $currency = Currency::findOrFail($data['currency_id']);
            // Ensure we have a single Currency model, not a collection
            if ($currency instanceof Collection) {
                $currency = $currency->first();
                if (! $currency) {
                    throw new InvalidArgumentException('Currency not found');
                }
            }
            $company = Filament::getTenant();
            $baseCurrency = $company->currency;

            $rate = (float) ($data['exchange_rate'] ?? 1);

            foreach ($data['lines'] as $line) {
                $inputDebit = (float) ($line['debit'] ?? 0);
                $inputCredit = (float) ($line['credit'] ?? 0);

                // Calculate Base Amounts
                $baseDebit = $inputDebit * $rate;
                $baseCredit = $inputCredit * $rate;

                $originalAmount = ($inputDebit > 0) ? $inputDebit : $inputCredit;

                $lineDTOs[] = new CreateJournalEntryLineDTO(
                    account_id: $line['account_id'],
                    debit: Money::of($baseDebit, $baseCurrency->code),
                    credit: Money::of($baseCredit, $baseCurrency->code),
                    description: $line['description'] ?? null,
                    partner_id: $line['partner_id'] ?? null,
                    analytic_account_id: $line['analytic_account_id'] ?? null,
                    original_currency_amount: Money::of($originalAmount, $currency->code),
                    exchange_rate_at_transaction: $rate,
                );
            }
        }
        $data['lines'] = $lineDTOs;
        $data['created_by_user_id'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $journalEntryDTO = new CreateJournalEntryDTO(
            company_id: (int) (Filament::getTenant()?->getKey() ?? 0),
            journal_id: $data['journal_id'],
            currency_id: $data['currency_id'],
            entry_date: $data['entry_date'],
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
            created_by_user_id: $data['created_by_user_id'],
            is_posted: false,
            lines: $data['lines']
        );

        try {
            return app(\Jmeryar\Accounting\Actions\Accounting\CreateJournalEntryAction::class)->execute($journalEntryDTO);
        } catch (QueryException $e) {
            // Check if it's a database constraint violation for duplicate reference
            // MySQL error code 1062 for duplicate entry
            // SQLite error code 19 for UNIQUE constraint failed
            $errorCode = $e->errorInfo[1] ?? null;
            $isDuplicateEntry = ($errorCode === 1062 && str_contains($e->getMessage(), 'reference_unique')) ||
                ($errorCode === 19 && str_contains($e->getMessage(), 'UNIQUE constraint failed') && str_contains($e->getMessage(), 'reference'));

            if ($isDuplicateEntry) {
                throw ValidationException::withMessages([
                    'reference' => __('accounting::journal_entry.reference_already_exists', ['reference' => $data['reference']]),
                ]);
            }

            // Re-throw other database exceptions
            throw $e;
        } catch (PDOException $e) {
            // Handle PDO exceptions that might not be wrapped in QueryException
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'reference_unique')) {
                throw ValidationException::withMessages([
                    'reference' => __('accounting::journal_entry.reference_already_exists', ['reference' => $data['reference']]),
                ]);
            }

            // Re-throw other PDO exceptions
            throw $e;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('opening-balances'),
        ];
    }
}
