<?php

namespace App\Livewire\Accounting;

use App\Models\Account;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Services\BankReconciliationService;
use Brick\Money\Money;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BankTransactionsTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public BankStatement $bankStatement;
    public array $selectedBankLines = [];

    public function mount(BankStatement $bankStatement): void
    {
        $this->bankStatement = $bankStatement;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BankStatementLine::query()
                    ->where('bank_statement_id', $this->bankStatement->id)
                    ->where('is_reconciled', false)
            )
            ->columns([
                ViewColumn::make('select')
                    ->label('')
                    ->view('components.bank-reconciliation-checkbox')
                    ->width('50px'),
                TextColumn::make('date')
                    ->label(__('bank_statement.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('bank_statement.description'))
                    ->searchable()
                    ->limit(50),
                TextColumn::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->formatStateUsing(fn ($state) => $state->formatTo('en_US'))
                    ->sortable(),
            ])
            ->actions([
                Action::make('writeOff')
                    ->label(__('bank_statement.write_off'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Select::make('account_id')
                            ->label(__('bank_statement.write_off_account'))
                            ->options(function () {
                                return \App\Models\Account::where('company_id', $this->bankStatement->company_id)
                                    ->where('type', 'expense')
                                    ->pluck('name', 'id');
                            })
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('bank_statement.write_off_reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, BankStatementLine $record) {
                        $writeOffAccount = Account::findOrFail($data['account_id']);

                        app(BankReconciliationService::class)->createWriteOff(
                            $record,
                            $writeOffAccount,
                            Auth::user(),
                            $data['reason']
                        );

                        $this->dispatch('bank-line-written-off');
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('date', 'desc');
    }

    public function toggleBankLine(int $lineId): void
    {
        if (in_array($lineId, $this->selectedBankLines)) {
            $this->selectedBankLines = array_filter($this->selectedBankLines, fn($id) => $id !== $lineId);
        } else {
            $this->selectedBankLines[] = $lineId;
        }

        $this->emitSelectionChanged();
    }

    protected function emitSelectionChanged(): void
    {
        $total = Money::of(0, 'IQD');

        if (!empty($this->selectedBankLines)) {
            $lines = BankStatementLine::whereIn('id', $this->selectedBankLines)->get();
            foreach ($lines as $line) {
                $total = $total->plus($line->amount);
            }
        }

        $this->dispatch('bank-selection-changed', [
            'selectedIds' => $this->selectedBankLines,
            'total' => $total->getMinorAmount()->toInt(),
            'currency' => $total->getCurrency()->getCurrencyCode(),
        ]);
    }

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function render()
    {
        return view('livewire.accounting.bank-transactions-table');
    }
}
