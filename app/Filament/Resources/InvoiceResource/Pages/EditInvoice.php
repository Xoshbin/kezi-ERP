<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Actions\Sales\UpdateInvoiceAction;
use App\DataTransferObjects\Sales\UpdateInvoiceDTO;
use App\DataTransferObjects\Sales\UpdateInvoiceLineDTO;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Brick\Money\Money;
use Filament\Actions;
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
            Actions\ActionGroup::make([
                Actions\Action::make('viewPdf')
                    ->label(__('View PDF'))
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (Invoice $record) => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),

                Actions\Action::make('downloadPdf')
                    ->label(__('Download PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Invoice $record) => route('invoices.pdf.download', $record)),
            ])
                ->label(__('PDF'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->button(),

            Actions\Action::make('confirm')
                ->label(__('invoice.confirm_invoice'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::STATUS_DRAFT)
                ->action(function (Invoice $record): void {
                    $this->save();
                    $service = app(InvoiceService::class);
                    try {
                        $service->confirm($record, Auth::user());
                        Notification::make()->title(__('invoice.invoice_confirmed_successfully'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('invoice.error_confirming_invoice'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('registerPayment')
                ->label(__('invoice.register_payment'))
                ->color('info')
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::STATUS_POSTED)
                ->action(fn (Invoice $record) => redirect()->to(PaymentResource::getUrl('create', [
                    'invoice_id' => $record->id,
                    'amount' => $record->total_amount->getAmount()->toFloat(),
                    'partner_id' => $record->customer_id,
                    'currency_id' => $record->currency_id,
                ]))),

            Actions\Action::make('resetToDraft')
                ->label(__('invoice.reset_to_draft'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::STATUS_POSTED)
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
                ])
                ->action(function (Invoice $record, array $data): void {
                    $service = app(InvoiceService::class);
                    try {
                        $service->resetToDraft($record, Auth::user(), $data['reason']);
                        Notification::make()->title(__('invoice.invoice_reset_to_draft'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('invoice.error_resetting_invoice'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    app(InvoiceService::class)->delete($record);
                    $this->redirect(InvoiceResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('invoiceLines', 'currency');
        $linesData = $this->record->invoiceLines->map(function ($line) {
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
}
