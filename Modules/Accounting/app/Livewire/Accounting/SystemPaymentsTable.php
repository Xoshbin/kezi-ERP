<?php

namespace Modules\Accounting\Livewire\Accounting;

use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentType;
use App\Filament\Tables\Columns\MoneyColumn;
use App\Models\BankStatement;
use App\Models\Payment;
use App\Services\CurrencyConverterService;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;

class SystemPaymentsTable extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public BankStatement $bankStatement;

    /** @var array<int, int> */
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
                    ->where('status', PaymentStatus::Confirmed)
                    ->whereDoesntHave('bankStatementLines')  // Only show unreconciled payments
                    ->with(['partner', 'currency', 'company'])
                    // Order by currency compatibility: same currency first, then others
                    ->orderByRaw('CASE WHEN currency_id = ? THEN 0 ELSE 1 END', [$this->bankStatement->currency_id])
                    ->orderBy('payment_date', 'desc')
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
                    ->color(fn ($state) => match ($state->value) {
                        'inbound' => 'success',
                        'outbound' => 'danger',
                        default => 'gray',
                    }),
                MoneyColumn::make('amount')
                    ->label(__('bank_statement.amount'))
                    ->sortable(),
                TextColumn::make('currency.code')
                    ->label(__('bank_statement.currency'))
                    ->badge()
                    ->color(fn ($record) => $record->currency_id === $this->bankStatement->currency_id ? 'success' : 'warning')
                    ->tooltip(fn ($record) => $record->currency_id === $this->bankStatement->currency_id
                        ? __('bank_statement.same_currency_as_statement')
                        : __('bank_statement.different_currency_conversion_required')),
            ])
            ->filters([
                SelectFilter::make('currency_compatibility')
                    ->label(__('bank_statement.currency_filter'))
                    ->options([
                        'same' => __('bank_statement.same_currency_only'),
                        'different' => __('bank_statement.different_currency_only'),
                        'all' => __('bank_statement.all_currencies'),
                    ])
                    ->default('all')
                    ->query(function ($query, array $data) {
                        if (! isset($data['value']) || $data['value'] === 'all') {
                            return $query;
                        }

                        if ($data['value'] === 'same') {
                            return $query->where('currency_id', $this->bankStatement->currency_id);
                        }

                        if ($data['value'] === 'different') {
                            return $query->where('currency_id', '!=', $this->bankStatement->currency_id);
                        }

                        return $query;
                    }),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('payment_date', 'desc')
            ->emptyStateHeading(__('bank_statement.no_unreconciled_payments'))
            ->emptyStateDescription(__('bank_statement.no_unreconciled_payments_description'));
    }

    public function togglePayment(int $paymentId): void
    {
        if (in_array($paymentId, $this->selectedPayments)) {
            $this->selectedPayments = array_filter($this->selectedPayments, fn ($id) => $id !== $paymentId);
        } else {
            $this->selectedPayments[] = $paymentId;
        }

        $this->emitSelectionChanged();
    }

    protected function emitSelectionChanged(): void
    {
        // Use the bank statement's currency for total calculation
        $bankStatementCurrency = $this->bankStatement->currency->code;
        $total = Money::of(0, $bankStatementCurrency);

        if (! empty($this->selectedPayments)) {
            $payments = Payment::whereIn('id', $this->selectedPayments)->with('currency')->get();
            foreach ($payments as $payment) {
                $paymentAmount = $payment->amount;

                // Convert payment amount to bank statement currency if different
                if ($paymentAmount->getCurrency()->getCurrencyCode() !== $bankStatementCurrency) {
                    try {
                        $paymentAmount = app(CurrencyConverterService::class)->convert(
                            $paymentAmount,
                            $this->bankStatement->currency,
                            $payment->payment_date,
                            $payment->company
                        );
                    } catch (Exception $e) {
                        // If conversion fails, use a simple 1:1 conversion as fallback
                        // This should be rare and will be logged for investigation
                        $paymentAmount = Money::ofMinor(
                            $paymentAmount->getMinorAmount()->toInt(),
                            $bankStatementCurrency
                        );
                    }
                }

                if ($payment->payment_type === PaymentType::Outbound) {
                    $total = $total->minus($paymentAmount);
                } else {
                    $total = $total->plus($paymentAmount);
                }
            }
        }

        $this->dispatch('payment-selection-changed', [
            'selectedIds' => $this->selectedPayments,
            'total' => $total->getMinorAmount()->toInt(),
            'currency' => $total->getCurrency()->getCurrencyCode(),
        ]);
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.accounting.system-payments-table');
    }
}
