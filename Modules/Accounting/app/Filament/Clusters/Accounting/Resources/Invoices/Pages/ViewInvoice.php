<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use App\Models\Company;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Widgets\SettlementSummaryWidget;
use Modules\Accounting\Models\Journal;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Foundation\Filament\Forms\Components\MoneyInput;
use Modules\Payment\Actions\Payments\CreatePaymentAction;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use Modules\Payment\DataTransferObjects\Payments\CreatePaymentDTO;
use Modules\Payment\Enums\Payments\PaymentMethod;
use Modules\Payment\Enums\Payments\PaymentType;
use Modules\Payment\Services\PaymentService;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // PDF Actions - Available for all invoices (draft and posted)
            ActionGroup::make([
                Action::make('viewPdf')
                    ->label(__('accounting::invoice.view_pdf'))
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('downloadPdf')
                    ->label(__('accounting::invoice.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
            ])
                ->label(__('accounting::invoice.pdf'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->button(),

            Action::make('register_payment')
                ->label(__('accounting::invoice.register_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading(__('accounting::invoice.register_payment'))
                ->modalDescription(__('accounting::invoice.payments_relation_manager.payment_details'))
                ->schema([
                    Select::make('journal_id')
                        ->label(__('payment::payment.form.journal_id'))
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
                        ->label(__('payment::payment.form.payment_date'))
                        ->default(now())
                        ->required(),
                    MoneyInput::make('amount')
                        ->label(__('payment::payment.form.amount'))
                        ->currencyField('currency_id')
                        ->default(fn (Invoice $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label(__('payment::payment.form.reference'))
                        ->placeholder(__('Optional reference')),
                    Hidden::make('currency_id')
                        ->default(fn (Invoice $record) => $record->currency_id),
                ])
                ->action(function (Invoice $record, array $data) {
                    try {
                        $currency = $record->currency;

                        // Create payment document link DTO
                        $documentLink = new CreatePaymentDocumentLinkDTO(
                            document_type: 'invoice',
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
                            payment_type: PaymentType::Inbound,
                            payment_method: PaymentMethod::BankTransfer,
                            paid_to_from_partner_id: $record->customer_id,
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
                            ->title(__('payment::payment.action.confirm.notification.success'))
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('payment::payment.action.confirm.notification.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(
                    fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            DocsAction::make('customer-invoices'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementSummaryWidget::class,
        ];
    }
}
