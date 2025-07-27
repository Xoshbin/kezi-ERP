<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditVendorBill extends EditRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    $vendorBillService = app(VendorBillService::class);;
                    $vendorBillService->delete($record);
                    $this->redirect(VendorBillResource::getUrl('index'));
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $vendorBillService = app(VendorBillService::class);

        // If the status is being changed to 'Posted', use the confirm method.
        if (isset($data['status']) && $data['status'] === VendorBill::TYPE_POSTED && $record->status !== VendorBill::TYPE_POSTED) {
            $vendorBillService->confirm($record, Auth::user());
        } else {
            // Otherwise, just update the record.
            $vendorBillService->update($record, $data);
        }

        return $record;
    }
}
