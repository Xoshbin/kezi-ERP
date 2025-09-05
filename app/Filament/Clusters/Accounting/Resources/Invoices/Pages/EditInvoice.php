<?php

namespace App\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use App\Actions\Accounting\BuildInvoicePostingPreviewAction;
use App\Actions\Payments\CreatePaymentAction;
use App\Actions\Sales\UpdateInvoiceAction;
use App\DataTransferObjects\Payments\CreatePaymentDocumentLinkDTO;
use App\DataTransferObjects\Payments\CreatePaymentDTO;
use App\DataTransferObjects\Sales\UpdateInvoiceDTO;
use App\DataTransferObjects\Sales\UpdateInvoiceLineDTO;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentType;
use App\Enums\Sales\InvoiceStatus;
use App\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use App\Filament\Clusters\Accounting\Resources\Invoices\Widgets\SettlementSummaryWidget;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Company;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // PDF Actions - Available for all invoices (draft and posted)
            ActionGroup::make([
                Action::make('viewPdf')
                    ->label(__('View PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('downloadPdf')
                    ->label(__('Download PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
            ])
                ->label(__('PDF'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->button(),

            Action::make('preview_posting')
                ->label(__('Preview Posting'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading(__('Posting Preview'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalWidth('7xl')
                ->modalContent(function (Invoice $record) {
                    $preview = app(BuildInvoicePostingPreviewAction::class)->execute($record);

                    return view('filament/accounting/invoices/preview-posting', [
                        'preview' => $preview,
                        'invoice' => $record,
                    ]);
                }),

            Action::make('export_preview_csv')
                ->label(__('Export Preview (CSV)'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (Invoice $record): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $preview = app(BuildInvoicePostingPreviewAction::class)->execute($record);
                    $rows = [];
                    $rows[] = ['Account Code', 'Account Name', 'Description', 'Debit', 'Credit'];
                    foreach ($preview['lines'] as $l) {
                        $rows[] = [
                            (string) ($l['account_code'] ?: ''),
                            (string) $l['account_name'],
                            (string) $l['description'],
                            number_format($l['debit_minor'] / 100, 2, '.', ''),
                            number_format($l['credit_minor'] / 100, 2, '.', ''),
                        ];
                    }
                    $csv = '';
                    foreach ($rows as $row) {
                        $csv .= implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row))."\n";
                    }
                    $filename = 'invoice-'.($record->invoice_number ?: ('DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT))).'-preview.csv';

                    return response()->streamDownload(function () use ($csv): void {
                        echo $csv;
                    }, $filename, [
                        'Content-Type' => 'text/csv',
                    ]);
                }),

            Action::make('export_preview_pdf')
                ->label(__('Export Preview (PDF)'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (Invoice $record): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $preview = app(BuildInvoicePostingPreviewAction::class)->execute($record);
                    $pdf = Pdf::loadView('filament/accounting/invoices/preview-posting-pdf', [
                        'preview' => $preview,
                        'invoice' => $record,
                    ]);
                    $filename = 'invoice-'.($record->invoice_number ?: ('DRAFT-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT))).'-preview.pdf';

                    return response()->streamDownload(function () use ($pdf): void {
                        echo $pdf->output();
                    }, $filename, [
                        'Content-Type' => 'application/pdf',
                    ]);
                }),

            Action::make('confirm')
                ->label(__('invoice.confirm_invoice'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft)
                ->action(function (Invoice $record): void {
                    $this->save();
                    $service = app(InvoiceService::class);
                    try {
                        $user = Auth::user();
                        if (!$user) {
                            throw new \Exception('User must be authenticated to confirm invoice');
                        }
                        $service->confirm($record, $user);
                        Notification::make()->title(__('invoice.invoice_confirmed_successfully'))->success()->send();
                    } catch (Exception $e) {
                        Notification::make()->title(__('invoice.error_confirming_invoice'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('register_payment')
                ->label(__('Register Payment'))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->modalHeading(__('Register Payment'))
                ->modalDescription(__('Register a payment for this invoice'))
                ->schema([
                    Select::make('journal_id')
                        ->label(__('payment.form.journal_id'))
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
                        ->label(__('payment.form.payment_date'))
                        ->default(now())
                        ->required(),
                    MoneyInput::make('amount')
                        ->label(__('payment.form.amount'))
                        ->currencyField('currency_id')
                        ->default(fn (Invoice $record) => $record->getRemainingAmount())
                        ->required(),
                    TextInput::make('reference')
                        ->label(__('payment.form.reference'))
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
                            payment_purpose: PaymentPurpose::Settlement,
                            payment_type: PaymentType::Inbound,
                            partner_id: $record->customer_id,
                            amount: Money::of($data['amount'], $currency->code),
                            counterpart_account_id: null,
                            document_links: [$documentLink],
                            reference: $data['reference']
                        );

                        // Create and confirm payment
                        $user = Auth::user();
                        if (!$user) {
                            throw new \Exception('User must be authenticated to create payment');
                        }
                        $payment = app(CreatePaymentAction::class)->execute($paymentDTO, $user);
                        app(PaymentService::class)->confirm($payment, $user);

                        Notification::make()
                            ->title(__('Payment registered successfully'))
                            ->success()
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title(__('Error registering payment'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

            // Actions\Action::make('resetToDraft')
            //     ->label(__('invoice.reset_to_draft'))
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Posted)
            //     ->form([
            //         \Filament\Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
            //     ])
            //     ->action(function (Invoice $record, array $data): void {
            //         $service = app(InvoiceService::class);
            //         try {
            //             $service->resetToDraft($record, Auth::user(), $data['reason']);
            //             Notification::make()->title(__('invoice.invoice_reset_to_draft'))->success()->send();
            //         } catch (\Exception $e) {
            //             Notification::make()->title(__('invoice.error_resetting_invoice'))->body($e->getMessage())->danger()->send();
            //         }
            //     }),

            DeleteAction::make()
                ->action(function (Model $record) {
                    if (!$record instanceof \App\Models\Invoice) {
                        throw new \Exception('Invalid record type');
                    }
                    app(InvoiceService::class)->delete($record);
                    $this->redirect(InvoiceResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        if (! $record instanceof Invoice) {
            return $data;
        }
        $record->loadMissing('invoiceLines', 'currency');
        $linesData = $record->invoiceLines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_id' => $line->tax_id,
                'income_account_id' => $line->income_account_id,
            ];
        })->toArray();
        $data['invoiceLines'] = $linesData;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Invoice) {
            // Filament guarantees the record is an Invoice, but add guard for Larastan
            $record = Invoice::findOrFail((int) $record->getKey());
        }

        $lineDTOs = [];
        foreach ($data['invoiceLines'] as $line) {
            $lineDTOs[] = new UpdateInvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: Money::of($line['unit_price'], $record->currency->code),
                income_account_id: $line['income_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null
            );
        }

        $invoiceDTO = new UpdateInvoiceDTO(
            invoice: $record,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $data['invoice_date'],
            due_date: $data['due_date'],
            lines: $lineDTOs,
            fiscal_position_id: $data['fiscal_position_id'] ?? null
        );

        return app(UpdateInvoiceAction::class)->execute($invoiceDTO);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // InvoiceResource\Widgets\AgingAnalysisWidget::class,
            SettlementSummaryWidget::class,
        ];
    }
}
