<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Models\Company;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Widgets\SettlementSummaryWidget;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;

class ViewVendorBill extends ViewRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('register_payment')
                ->label(__('accounting::bill.payments_relation_manager.create_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading(__('accounting::bill.payments_relation_manager.create_payment'))
                ->modalDescription(__('accounting::bill.register_payment.description'))
                ->schema([
                    Select::make('journal_id')
                        ->label(__('accounting::bill.register_payment.journal'))
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
                        }),
                    DatePicker::make('payment_date')
                        ->label(__('accounting::bill.register_payment.payment_date'))
                        ->default(now())
                        ->required(),
                    MoneyInput::make('amount')
                        ->label(__('accounting::bill.register_payment.amount'))
                        ->currencyField('currency_id')
                        ->default(fn (VendorBill $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label(__('accounting::bill.register_payment.reference'))
                        ->placeholder(__('accounting::bill.register_payment.optional_reference')),
                    Hidden::make('currency_id')
                        ->default(fn (VendorBill $record) => $record->currency_id),
                ])
                ->action(function (VendorBill $record, array $data): void {
                    try {
                        $currency = $record->currency;

                        // Create payment document link DTO
                        $documentLink = new CreatePaymentDocumentLinkDTO(
                            document_type: 'vendor_bill',
                            document_id: $record->id,
                            amount_applied: Money::of($data['amount'], $currency->code)
                        );

                        // Create payment DTO
                        $paymentDTO = new CreatePaymentDTO(
                            company_id: $record->company_id,
                            journal_id: $data['journal_id'],
                            currency_id: $record->currency_id,
                            payment_date: $data['payment_date'],
                            // settlement inferred by presence of document links
                            payment_type: PaymentType::Outbound,
                            payment_method: PaymentMethod::BankTransfer,
                            paid_to_from_partner_id: $record->vendor_id,
                            amount: Money::of($data['amount'], $currency->code),
                            document_links: [$documentLink],
                            reference: $data['reference']
                        );

                        // Create and confirm payment
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to create payment');
                        }
                        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $user);
                        app(PaymentService::class)->confirm($payment, $user);

                        Notification::make()
                            ->title(__('accounting::bill.notification_payment_registered'))
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('accounting::bill.notification_payment_error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(
                    fn (VendorBill $record) => $record->status === VendorBillStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DocsAction::make('vendor-bills'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementSummaryWidget::class,
        ];
    }
}
