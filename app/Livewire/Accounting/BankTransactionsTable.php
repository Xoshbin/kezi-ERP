<?php

namespace App\Livewire\Accounting;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Support\Contracts\TranslatableContentDriver;
use Brick\Money\Money;
use App\Models\Account;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use App\Services\BankReconciliationService;
use App\Filament\Tables\Columns\MoneyColumn;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class BankTransactionsTable extends Component implements HasTable, HasForms, HasActions
{
    use InteractsWithActions;
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
                MoneyColumn::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('writeOff')
                    ->label(__('bank_statement.write_off'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([
                        Select::make('account_id')
                            ->label(__('bank_statement.write_off_account'))
                            ->options(function () {
                                return Account::where('company_id', $this->bankStatement->company_id)
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
            ->defaultSort('date', 'desc')
            ->emptyStateHeading(__('bank_statement.no_bank_statement_lines'))
            ->emptyStateDescription(__('bank_statement.no_bank_statement_lines_description'));
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

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function render()
    {
        return view('livewire.accounting.bank-transactions-table');
    }
}
