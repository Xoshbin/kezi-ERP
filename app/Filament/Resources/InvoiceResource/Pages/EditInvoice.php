<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Actions\Sales\UpdateInvoiceAction;
use App\DataTransferObjects\Sales\UpdateInvoiceDTO;
use App\DataTransferObjects\Sales\UpdateInvoiceLineDTO;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label(__('invoice.confirm_invoice'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::TYPE_DRAFT)
                ->action(function (Invoice $record): void {
                    $this->save();
                    $service = app(InvoiceService::class);
                    try {
                        $service->confirm($record, auth()->user());
                        Notification::make()->title(__('invoice.invoice_confirmed_successfully'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('invoice.error_confirming_invoice'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('registerPayment')
                ->label(__('invoice.register_payment'))
                ->color('info')
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::TYPE_POSTED)
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
                ->visible(fn (Invoice $record): bool => $record->status === Invoice::TYPE_POSTED)
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')->label(__('invoice.reason'))->required(),
                ])
                ->action(function (Invoice $record, array $data): void {
                    $service = app(InvoiceService::class);
                    try {
                        $service->resetToDraft($record, auth()->user(), $data['reason']);
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
        $this->record->loadMissing('invoiceLines');
        $linesData = $this->record->invoiceLines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price?->getAmount()->toFloat(),
                'tax_id' => $line->tax_id,
                'income_account_id' => $line->income_account_id,
            ];
        })->toArray();
        $data['invoiceLines'] = $linesData;
        $data['total_amount'] = $this->record->total_amount?->getAmount()->toFloat();
        $data['total_tax'] = $this->record->total_tax?->getAmount()->toFloat();
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['invoiceLines'] as $line) {
            $lineDTOs[] = new UpdateInvoiceLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: $line['unit_price'],
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

        return (new UpdateInvoiceAction())->execute($invoiceDTO);
    }
}
