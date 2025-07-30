<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Actions\Purchases\UpdateVendorBillAction;
use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\DataTransferObjects\Purchases\UpdateVendorBillLineDTO;
use App\Filament\Resources\VendorBillResource;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label(__('vendor_bill.confirm_bill'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBill::STATUS_DRAFT)
                ->action(function (VendorBill $record): void {
                    $this->save();

                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $vendorBillService->confirm($record, Auth::user());
                        Notification::make()->title(__('vendor_bill.notification_bill_confirmed_success'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('vendor_bill.notification_confirm_bill_error'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('resetToDraft')
                ->label(__('vendor_bill.reset_to_draft'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBill::STATUS_POSTED)
                ->form([
                    Forms\Components\Textarea::make('reason')->label(__('vendor_bill.reason'))->required(),
                ])
                ->action(function (VendorBill $record, array $data): void {
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $vendorBillService->resetToDraft($record, Auth::user(), $data['reason']);
                        Notification::make()->title(__('vendor_bill.notification_bill_reset_success'))->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title(__('vendor_bill.notification_reset_bill_error'))->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    app(VendorBillService::class)->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $lineDTOs = [];
        foreach ($data['lines'] as $line) {
            $lineDTOs[] = new UpdateVendorBillLineDTO(
                description: $line['description'],
                quantity: $line['quantity'],
                unit_price: $line['unit_price'],
                expense_account_id: $line['expense_account_id'],
                product_id: $line['product_id'] ?? null,
                tax_id: $line['tax_id'] ?? null,
                analytic_account_id: $line['analytic_account_id'] ?? null
            );
        }

        $vendorBillDTO = new UpdateVendorBillDTO(
            vendorBill: $record,
            vendor_id: $data['vendor_id'],
            currency_id: $data['currency_id'],
            bill_reference: $data['bill_reference'],
            bill_date: $data['bill_date'],
            accounting_date: $data['accounting_date'],
            due_date: $data['due_date'] ?? null,
            lines: $lineDTOs
        );

        return (new UpdateVendorBillAction())->execute($vendorBillDTO);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('lines');
        $linesData = $this->record->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price?->getAmount()->toFloat(),
                'tax_id' => $line->tax_id,
                'expense_account_id' => $line->expense_account_id,
                'analytic_account_id' => $line->analytic_account_id,
            ];
        })->toArray();
        $data['lines'] = $linesData;
        $data['total_amount'] = $this->record->total_amount?->getAmount()->toFloat();
        $data['total_tax'] = $this->record->total_tax?->getAmount()->toFloat();
        return $data;
    }
}
