<?php

namespace App\Livewire\Accounting;

use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\Account;
use App\Services\BankReconciliationService;
use Brick\Money\Money;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BankReconciliationMatcher extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $bankStatementId;
    public BankStatement $bankStatement;

    // These will hold the IDs of selected rows from each table
    public array $selectedBankLines = [];
    public array $selectedSystemPayments = [];

    public function mount(int $bankStatementId): void
    {
        $this->bankStatementId = $bankStatementId;
        $this->bankStatement = BankStatement::with(['currency', 'journal'])->findOrFail($bankStatementId);
    }

    public function table(Table $table): Table
    {
        // This is the primary table for bank statement lines
        return $table
            ->query(BankStatementLine::query()
                ->where('bank_statement_id', $this->bankStatementId)
                ->where('is_reconciled', false)
            )
            ->columns([
                ViewColumn::make('select')
                    ->label('')
                    ->view('components.bank-reconciliation-checkbox')
                    ->width('50px'),
                TextColumn::make('date')
                    ->label(__('bank_statement.line_date'))
                    ->date(),
                TextColumn::make('description')
                    ->label(__('bank_statement.description'))
                    ->limit(50),
                TextColumn::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->money($this->bankStatement->currency->code)
                    ->alignEnd(),
            ])
            ->actions([
                Action::make('writeOff')
                    ->label(__('bank_statement.write_off'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Select::make('write_off_account_id')
                            ->label(__('bank_statement.write_off_account'))
                            ->options(Account::where('company_id', Auth::user()->company_id)
                                ->where('is_deprecated', false)
                                ->where('type', 'expense')
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Textarea::make('description')
                            ->label(__('bank_statement.write_off_description'))
                            ->required()
                            ->maxLength(255)
                            ->default(fn($record) => "Write-off for: {$record->description}"),
                    ])
                    ->action(function (BankStatementLine $record, array $data) {
                        $writeOffAccount = Account::findOrFail($data['write_off_account_id']);

                        app(BankReconciliationService::class)->createWriteOff(
                            $record,
                            $writeOffAccount,
                            Auth::user(),
                            $data['description']
                        );

                        Notification::make()
                            ->title(__('bank_statement.write_off_created'))
                            ->success()
                            ->send();

                        // Refresh the component
                        $this->dispatch('$refresh');
                    }),
            ])
            ->heading(__('bank_statement.bank_transactions'))
            ->description(__('bank_statement.bank_transactions_description'));
    }

    #[Computed]
    public function systemPayments()
    {
        return Payment::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('status', 'confirmed')
            ->with(['partner', 'currency'])
            ->get();
    }

    #[Computed]
    public function summary(): array
    {
        // Use Brick\Money for all calculations to ensure precision
        $currencyCode = $this->bankStatement->currency->code;
        $bankTotal = Money::of(0, $currencyCode);

        foreach (BankStatementLine::find($this->selectedBankLines) as $line) {
            $bankTotal = $bankTotal->plus($line->amount);
        }

        $systemTotal = Money::of(0, $currencyCode);
        foreach (Payment::find($this->selectedSystemPayments) as $payment) {
            // Convert payment amount to bank statement currency if needed
            $amount = $payment->amount;
            if ($payment->currency->code !== $currencyCode) {
                // For now, assume same currency. In production, you'd need currency conversion
                $amount = Money::of($payment->amount->getAmount(), $currencyCode);
            }

            // Adjust for direction (inbound/outbound)
            $amount = $payment->payment_type === 'inbound' ? $amount : $amount->negated();
            $systemTotal = $systemTotal->plus($amount);
        }

        $difference = $bankTotal->minus($systemTotal);

        return [
            'bankTotal' => $bankTotal,
            'systemTotal' => $systemTotal,
            'difference' => $difference,
            'isBalanced' => $difference->isZero(),
            'bankTotalFormatted' => $bankTotal->formatTo('en_US'),
            'systemTotalFormatted' => $systemTotal->formatTo('en_US'),
            'differenceFormatted' => $difference->formatTo('en_US'),
        ];
    }

    public function reconcile()
    {
        if (!$this->summary()['isBalanced']) {
            Notification::make()
                ->title(__('bank_statement.reconciliation_not_balanced'))
                ->danger()
                ->send();
            return;
        }

        if (empty($this->selectedBankLines) || empty($this->selectedSystemPayments)) {
            Notification::make()
                ->title(__('bank_statement.select_transactions_to_reconcile'))
                ->warning()
                ->send();
            return;
        }

        // Delegate to the service
        app(BankReconciliationService::class)->reconcileMultiple(
            $this->selectedBankLines,
            $this->selectedSystemPayments,
            Auth::user()
        );

        // Clear selections
        $this->selectedBankLines = [];
        $this->selectedSystemPayments = [];

        Notification::make()
            ->title(__('bank_statement.reconciliation_successful'))
            ->success()
            ->send();

        // Refresh the component
        $this->dispatch('$refresh');
    }

    public function toggleBankLine(int $bankLineId): void
    {
        if (in_array($bankLineId, $this->selectedBankLines)) {
            $this->selectedBankLines = array_filter(
                $this->selectedBankLines,
                fn($id) => $id !== $bankLineId
            );
        } else {
            $this->selectedBankLines[] = $bankLineId;
        }
    }

    public function toggleSystemPayment(int $paymentId): void
    {
        if (in_array($paymentId, $this->selectedSystemPayments)) {
            $this->selectedSystemPayments = array_filter(
                $this->selectedSystemPayments,
                fn($id) => $id !== $paymentId
            );
        } else {
            $this->selectedSystemPayments[] = $paymentId;
        }
    }

    public function render()
    {
        return view('livewire.accounting.bank-reconciliation-matcher');
    }
}
