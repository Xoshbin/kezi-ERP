<?php

namespace App\Filament\Clusters\Accounting\Resources\BankStatements\Pages;

use App\Actions\Accounting\UpdateBankStatementAction;
use App\DataTransferObjects\Accounting\UpdateBankStatementDTO;
use App\DataTransferObjects\Accounting\UpdateBankStatementLineDTO;
use App\Filament\Actions\DocsAction;
use App\Filament\Clusters\Accounting\Resources\BankStatements\BankStatementResource;
use App\Models\Currency;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use Brick\Money\Money;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    public function getTitle(): string
    {
        return __('bank_statement.edit_bank_statement');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            DocsAction::make('bank-statements'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var \App\Models\BankStatement $record */
        $record = $this->record;
        $record->loadMissing('bankStatementLines', 'currency');

        /** @var \Illuminate\Database\Eloquent\Collection<int, BankStatementLine> $bankStatementLines */
        $bankStatementLines = $record->bankStatementLines;
        $linesData = $bankStatementLines->map(function (BankStatementLine $line) {
            return [
                'date' => $line->date->format('Y-m-d'),
                'description' => $line->description,
                'amount' => $line->amount,
                'partner_id' => $line->partner_id,
                'foreign_currency_id' => $line->foreign_currency_id,
                'amount_in_foreign_currency' => $line->amount_in_foreign_currency,
            ];
        })->toArray();
        $data['bankStatementLines'] = $linesData;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var BankStatement $record */
        $lineDTOs = [];
        foreach ($data['bankStatementLines'] as $line) {
            $foreignCurrency = null;
            $amountInForeignCurrency = null;

            if (! empty($line['foreign_currency_id']) && ! empty($line['amount_in_foreign_currency'])) {
                $foreignCurrency = Currency::findOrFail($line['foreign_currency_id']);
                // Ensure we have a single Currency model, not a collection
                if ($foreignCurrency instanceof \Illuminate\Database\Eloquent\Collection) {
                    $foreignCurrency = $foreignCurrency->first();
                    if (!$foreignCurrency) {
                        throw new \InvalidArgumentException('Foreign currency not found');
                    }
                }
                $amountInForeignCurrency = Money::of($line['amount_in_foreign_currency'], $foreignCurrency->code);
            }

            $lineDTOs[] = new UpdateBankStatementLineDTO(
                id: $line['id'] ?? null,
                date: $line['date'],
                description: $line['description'],
                amount: Money::of($line['amount'], ($record->relationLoaded('currency') ? $record->getRelation('currency') : $record->currency()->first())->code),
                partner_id: $line['partner_id'],
                foreign_currency_id: $line['foreign_currency_id'] ?? null,
                amount_in_foreign_currency: $amountInForeignCurrency
            );
        }

        $dto = new UpdateBankStatementDTO(
            bankStatement: $record,
            currency_id: $data['currency_id'],
            journal_id: $data['journal_id'],
            reference: $data['reference'],
            date: $data['date'],
            starting_balance: Money::of($data['starting_balance'], ($record->relationLoaded('currency') ? $record->getRelation('currency') : $record->currency()->first())->code),
            ending_balance: Money::of($data['ending_balance'], ($record->relationLoaded('currency') ? $record->getRelation('currency') : $record->currency()->first())->code),
            lines: $lineDTOs
        );

        return app(UpdateBankStatementAction::class)->execute($dto);
    }
}
