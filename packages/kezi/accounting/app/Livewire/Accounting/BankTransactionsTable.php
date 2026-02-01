<?php

namespace Kezi\Accounting\Livewire\Accounting;

use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\BankStatement;
use Kezi\Accounting\Models\BankStatementLine;
use Kezi\Foundation\Filament\Tables\Columns\MoneyColumn;

class BankTransactionsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public BankStatement $bankStatement;

    /** @var array<int, int> */
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
                    ->view('accounting::components.bank-reconciliation-checkbox')
                    ->width('50px'),
                TextColumn::make('date')
                    ->label(__('accounting::bank_statement.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('description')
                    ->label(__('accounting::bank_statement.description'))
                    ->searchable()
                    ->limit(50),
                MoneyColumn::make('amount')
                    ->label(__('accounting::bank_statement.amount'))
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('writeOff')
                    ->label(__('accounting::bank_statement.write_off'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->schema([
                        Select::make('account_id')
                            ->label(__('accounting::bank_statement.write_off_account'))
                            ->options(function () {
                                return Account::where('company_id', $this->bankStatement->company_id)
                                    ->where('type', 'expense')
                                    ->pluck('name', 'id');
                            })
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('accounting::bank_statement.write_off_reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, BankStatementLine $record) {
                        $writeOffAccount = Account::findOrFail($data['account_id']);
                        // Ensure we have a single Account model, not a collection
                        if ($writeOffAccount instanceof Collection) {
                            $writeOffAccount = $writeOffAccount->first();
                            if (! $writeOffAccount) {
                                throw new Exception('Write-off account not found');
                            }
                        }

                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to create write-off');
                        }
                        app(\Kezi\Accounting\Services\BankReconciliationService::class)->createWriteOff(
                            $record,
                            $writeOffAccount,
                            $user,
                            $data['reason']
                        );

                        $this->dispatch('bank-line-written-off');
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading(__('accounting::bank_statement.no_bank_statement_lines'))
            ->emptyStateDescription(__('accounting::bank_statement.no_bank_statement_lines_description'));
    }

    public function toggleBankLine(int $lineId): void
    {
        if (in_array($lineId, $this->selectedBankLines)) {
            $this->selectedBankLines = array_filter($this->selectedBankLines, fn ($id) => $id !== $lineId);
        } else {
            $this->selectedBankLines[] = $lineId;
        }

        $this->emitSelectionChanged();
    }

    protected function emitSelectionChanged(): void
    {
        // Use the bank statement's currency instead of hardcoded 'IQD'
        $total = Money::of(0, $this->bankStatement->currency->code);

        if (! empty($this->selectedBankLines)) {
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

    public function render(): View
    {
        return view('accounting::livewire.accounting.bank-transactions-table');
    }
}
