<?php

namespace App\Livewire\Accounting;

use Brick\Money\Money;
use App\Models\Payment;
use Livewire\Component;
use Filament\Tables\Table;
use App\Models\BankStatement;
use App\Enums\Payments\PaymentType;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use App\Filament\Tables\Columns\MoneyColumn;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class SystemPaymentsTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public BankStatement $bankStatement;
    public array $selectedPayments = [];

    public function mount(BankStatement $bankStatement): void
    {
        $this->bankStatement = $bankStatement;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->where('company_id', $this->bankStatement->company_id)
                    ->where('status', 'confirmed')
                    ->with(['partner'])
            )
            ->columns([
                ViewColumn::make('select')
                    ->label(__('bank_statement.select'))
                    ->view('components.payment-reconciliation-checkbox')
                    ->width('80px'),
                TextColumn::make('partner.name')
                    ->label(__('bank_statement.partner'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label(__('bank_statement.payment_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->label(__('bank_statement.type'))
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->badge()
                    ->color(fn ($state) => match($state->value) {
                        'inbound' => 'success',
                        'outbound' => 'danger',
                        default => 'gray',
                    }),
                MoneyColumn::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->sortable(),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('payment_date', 'desc')
            ->emptyStateHeading(__('bank_statement.no_unreconciled_payments'))
            ->emptyStateDescription(__('bank_statement.no_unreconciled_payments_description'));
    }

    public function togglePayment(int $paymentId): void
    {
        if (in_array($paymentId, $this->selectedPayments)) {
            $this->selectedPayments = array_filter($this->selectedPayments, fn($id) => $id !== $paymentId);
        } else {
            $this->selectedPayments[] = $paymentId;
        }

        $this->emitSelectionChanged();
    }

    protected function emitSelectionChanged(): void
    {
        $total = Money::of(0, 'IQD');

        if (!empty($this->selectedPayments)) {
            $payments = Payment::whereIn('id', $this->selectedPayments)->get();
            foreach ($payments as $payment) {
                if ($payment->payment_type === PaymentType::Outbound) {
                    $total = $total->minus($payment->amount);
                } else {
                    $total = $total->plus($payment->amount);
                }
            }
        }

        $this->dispatch('payment-selection-changed', [
            'selectedIds' => $this->selectedPayments,
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
        return view('livewire.accounting.system-payments-table');
    }
}
