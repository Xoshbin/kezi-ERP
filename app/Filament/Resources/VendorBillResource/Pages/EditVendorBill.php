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

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The "Confirm" button to post the bill
            Actions\Action::make('confirm')
                ->label('Confirm Bill')
                ->color('success')
                ->requiresConfirmation()
                // This action is only visible if the bill is a draft.
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBill::TYPE_DRAFT)
                ->action(function (VendorBill $record): void {
                    // First, save any pending changes the user made in the form.
                    $this->save();

                    // Then, call the confirmation service.
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $vendorBillService->confirm($record, auth()->user());
                        Notification::make()->title('Bill confirmed successfully')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Error confirming bill')->body($e->getMessage())->danger()->send();
                    }
                }),

            // The "Reset to Draft" button
            Actions\Action::make('resetToDraft')
                ->label('Reset to Draft')
                ->color('warning')
                ->requiresConfirmation()
                // This action is only visible if the bill is already posted.
                ->visible(fn (VendorBill $record): bool => $record->status === VendorBill::TYPE_POSTED)
                ->form([
                    Forms\Components\Textarea::make('reason')->required(),
                ])
                ->action(function (VendorBill $record, array $data): void {
                    $vendorBillService = app(VendorBillService::class);
                    try {
                        $vendorBillService->resetToDraft($record, auth()->user(), $data['reason']);
                        Notification::make()->title('Bill reset to draft')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Error resetting bill')->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    app(VendorBillService::class)->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),
        ];
    }

    // This method now has a single, simple responsibility:
    // to save changes to the data of a DRAFT bill.
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

    // The mutateFormDataBeforeFill method remains unchanged and is correct.
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
