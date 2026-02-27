<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Brick\Money\Money;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Kezi\Accounting\Actions\Accounting\BuildInvoicePostingPreviewAction;
use Kezi\Accounting\Filament\Actions\RegisterPaymentAction;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\InvoiceResource;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Widgets\SettlementSummaryWidget;
use Kezi\Foundation\Enums\Incoterm;
use Kezi\Foundation\Filament\Actions\DocsAction;
use Kezi\Payment\Enums\Payments\PaymentType;
use Kezi\Sales\Actions\Sales\UpdateInvoiceAction;
use Kezi\Sales\DataTransferObjects\Sales\UpdateInvoiceDTO;
use Kezi\Sales\DataTransferObjects\Sales\UpdateInvoiceLineDTO;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Services\InvoiceService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @extends EditRecord<\Kezi\Sales\Models\Invoice>
 */
class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RegisterPaymentAction::make()
                ->documentType('invoice')
                ->paymentType(PaymentType::Inbound)
                ->partnerId(fn (Invoice $record) => $record->customer_id)
                ->visible(
                    fn (Invoice $record) => $record->status === InvoiceStatus::Posted &&
                    ! $record->getRemainingAmount()->isZero()
                ),

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

            Action::make('preview_posting')
                ->label(__('accounting::invoice.preview_posting'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft)
                ->requiresConfirmation()
                ->modalHeading(__('accounting::invoice.posting_preview'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('accounting::invoice.close'))
                ->modalWidth('7xl')
                ->modalContent(function (Invoice $record) {
                    $preview = app(BuildInvoicePostingPreviewAction::class)->execute($record);

                    return view('filament/accounting/invoices/preview-posting', [
                        'preview' => $preview,
                        'invoice' => $record,
                    ]);
                }),

            Action::make('export_preview_csv')
                ->label(__('accounting::invoice.export_preview_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (Invoice $record): StreamedResponse {
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
                ->label(__('accounting::invoice.export_preview_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft && config('app.debug') && ! app()->environment('production'))
                ->action(function (Invoice $record): StreamedResponse {
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

            Action::make('post')
                ->label(__('accounting::invoice.confirm_invoice'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft)
                ->disabled(fn (Invoice $record): bool => $record->invoiceLines->isEmpty() || $record->total_amount->isZero())
                ->action(function (Invoice $record): void {
                    $this->save();
                    $record = $record->fresh(['invoiceLines']);
                    $service = app(InvoiceService::class);
                    try {
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to confirm invoice');
                        }
                        $service->confirm($record, $user);
                        Notification::make()->title(__('accounting::invoice.invoice_confirmed_successfully'))->success()->send();
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $record]));
                    } catch (ValidationException $e) {
                        Notification::make()
                            ->title(__('accounting::invoice.error_confirming_invoice'))
                            ->body(implode("\n", Arr::flatten($e->errors())))
                            ->danger()
                            ->send();
                    } catch (Throwable $e) {
                        Log::error('Invoice confirmation failed', [
                            'invoice_id' => $record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        Notification::make()->title(__('accounting::invoice.error_confirming_invoice'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('resetToDraft')
                ->label(__('accounting::invoice.reset_to_draft'))
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Posted && $record->isNotPaid())
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label(__('accounting::invoice.reason'))
                        ->required(),
                ])
                ->action(function (Invoice $record, array $data): void {
                    $service = app(InvoiceService::class);
                    try {
                        $user = Auth::user();
                        if (! $user) {
                            throw new Exception('User must be authenticated to reset invoice');
                        }
                        $service->resetToDraft($record, $user, $data['reason']);
                        Notification::make()
                            ->title(__('accounting::invoice.notification.reset_success'))
                            ->success()
                            ->send();
                        $this->redirect(InvoiceResource::getUrl('edit', ['record' => $record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('accounting::invoice.notification.reset_error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            DeleteAction::make()
                ->action(function (Model $record) {
                    if (! $record instanceof Invoice) {
                        throw new Exception('Invalid record type');
                    }
                    app(InvoiceService::class)->delete($record);
                    $this->redirect(InvoiceResource::getUrl('index'));
                }),

            DocsAction::make('customer-invoices'),
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
                'deferred_start_date' => $line->deferred_start_date,
                'deferred_end_date' => $line->deferred_end_date,
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
                tax_id: $line['tax_id'] ?? null,
                deferred_start_date: $line['deferred_start_date'] ?? null,
                deferred_end_date: $line['deferred_end_date'] ?? null
            );
        }

        $invoiceDTO = new UpdateInvoiceDTO(
            invoice: $record,
            customer_id: $data['customer_id'],
            currency_id: $data['currency_id'],
            invoice_date: $data['invoice_date'],
            due_date: $data['due_date'],
            lines: $lineDTOs,
            fiscal_position_id: $data['fiscal_position_id'] ?? null,
            incoterm: $data['incoterm'] instanceof Incoterm ? $data['incoterm'] : (isset($data['incoterm']) ? Incoterm::tryFrom($data['incoterm']) : null)
        );

        $updatedInvoice = app(UpdateInvoiceAction::class)->execute($invoiceDTO);

        // Handle exchange_rate_at_creation separately since it's not in the DTO
        if (isset($data['exchange_rate_at_creation'])) {
            $updatedInvoice->update([
                'exchange_rate_at_creation' => $data['exchange_rate_at_creation'],
            ]);
        }

        return $updatedInvoice;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // InvoiceResource\Widgets\AgingAnalysisWidget::class,
            SettlementSummaryWidget::class,
        ];
    }
}
