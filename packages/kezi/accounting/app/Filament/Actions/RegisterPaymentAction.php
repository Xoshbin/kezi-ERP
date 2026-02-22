<?php

namespace Kezi\Accounting\Filament\Actions;

use App\Models\Company;
use Brick\Money\Money;
use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Filament\Forms\Components\MoneyInput;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Services\CurrencyConverterService;
use Kezi\Payment\Actions\Payments\CreatePaymentAction;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Kezi\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Kezi\Payment\Enums\Payments\PaymentMethod;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Payment\Services\PaymentService;
use Kezi\Purchase\Models\VendorBill;
use Kezi\Sales\Models\Invoice;

class RegisterPaymentAction extends Action
{
    protected string|Closure|null $documentType = null;

    protected PaymentType|Closure|null $paymentType = null;

    protected int|Closure|null $partnerId = null;

    public static function getDefaultName(): ?string
    {
        return 'register_payment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('accounting::invoice.register_payment'))
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->modalHeading(__('accounting::invoice.register_payment'))
            ->modalDescription(__('accounting::invoice.payments_relation_manager.payment_details'))
            ->form([
                Select::make('journal_id')
                    ->label(__('accounting::payment.form.journal_id'))
                    ->options(function (): array {
                        $tenant = Filament::getTenant();
                        if (! $tenant instanceof Company) {
                            return [];
                        }

                        return Journal::where('company_id', $tenant->getKey())
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->required()
                    ->default(function (): ?int {
                        $tenant = Filament::getTenant();
                        if (! $tenant instanceof Company) {
                            return null;
                        }

                        return Journal::where('company_id', $tenant->getKey())
                            ->where('type', 'bank')
                            ->value('id');
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state, Invoice|VendorBill $record) {
                        if (! $state) {
                            return;
                        }

                        $journal = Journal::find($state);
                        if ($journal?->currency_id) {
                            $set('currency_id', $journal->currency_id);
                            static::recalculateAmount($get, $set, $record, $journal->currency_id);
                        }
                    }),
                DatePicker::make('payment_date')
                    ->label(__('accounting::payment.form.payment_date'))
                    ->default(now())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, Invoice|VendorBill $record) {
                        static::recalculateAmount($get, $set, $record);
                    }),
                Select::make('currency_id')
                    ->label(__('accounting::payment.form.currency_id'))
                    ->options(Currency::pluck('code', 'id'))
                    ->default(fn (Invoice|VendorBill $record) => $record->currency_id)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, Invoice|VendorBill $record) {
                        static::recalculateAmount($get, $set, $record);
                    }),
                MoneyInput::make('amount')
                    ->label(__('accounting::payment.form.amount'))
                    ->currencyField('currency_id')
                    ->default(fn (Invoice|VendorBill $record) => $record->getRemainingAmount()->getAmount()->toFloat())
                    ->required()
                    ->live(),
                TextInput::make('reference')
                    ->label(__('accounting::payment.form.reference'))
                    ->placeholder(__('Optional reference')),
            ])
            ->action(function (Model $record, array $data): void {
                /** @var Invoice|VendorBill $record */
                try {
                    /** @var Currency $currency */
                    $currency = Currency::findOrFail($data['currency_id']);

                    // Create payment document link DTO
                    $documentLink = new CreatePaymentDocumentLinkDTO(
                        document_type: $this->evaluate($this->documentType),
                        document_id: $record->getKey(),
                        amount_applied: Money::of($data['amount'], $currency->code)
                    );

                    // Create payment DTO
                    $paymentDTO = new CreatePaymentDTO(
                        company_id: $record->company_id,
                        journal_id: $data['journal_id'],
                        currency_id: $data['currency_id'],
                        payment_date: $data['payment_date'],
                        payment_type: $this->evaluate($this->paymentType),
                        payment_method: PaymentMethod::BankTransfer,
                        paid_to_from_partner_id: $this->evaluate($this->partnerId),
                        amount: Money::of($data['amount'], $currency->code),
                        document_links: [$documentLink],
                        reference: $data['reference']
                    );

                    // Create and confirm payment
                    $user = Auth::user();
                    if (! $user) {
                        throw new Exception('User must be authenticated to create payment');
                    }
                    /** @phpstan-ignore-next-line */
                    $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $user);
                    /** @phpstan-ignore-next-line */
                    app(PaymentService::class)->confirm($payment, $user);

                    Notification::make()
                        ->title(__('accounting::payment.action.confirm.notification.success'))
                        ->success()
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title(__('accounting::payment.action.confirm.notification.error'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    public function documentType(string|Closure|null $type): static
    {
        $this->documentType = $type;

        return $this;
    }

    public function paymentType(PaymentType|Closure|null $type): static
    {
        $this->paymentType = $type;

        return $this;
    }

    public function partnerId(int|Closure|null $id): static
    {
        $this->partnerId = $id;

        return $this;
    }

    protected static function recalculateAmount(Get $get, Set $set, Invoice|VendorBill $record, ?int $currencyId = null): void
    {
        $currencyId ??= $get('currency_id');
        $paymentDate = $get('payment_date');

        if (! $currencyId || ! $paymentDate) {
            return;
        }

        $targetCurrency = Currency::find($currencyId);
        if (! $targetCurrency instanceof Currency) {
            return;
        }

        $remainingAmount = $record->getRemainingAmount(); // Money in document currency

        // If same currency, use the original remaining amount
        if ($targetCurrency->getKey() === $record->currency_id) {
            $set('amount', $remainingAmount->getAmount()->toFloat());

            return;
        }

        /** @var CurrencyConverterService $converter */
        $converter = app(CurrencyConverterService::class);

        try {
            $convertedAmount = $converter->convert(
                $remainingAmount,
                $targetCurrency,
                $paymentDate,
                $record->company
            );

            $set('amount', $convertedAmount->getAmount()->toFloat());
        } catch (Exception $e) {
            // Log or handle error if conversion is not possible
        }
    }
}
