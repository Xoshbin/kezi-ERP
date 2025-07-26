<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Services\VendorBillService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
        $vendorBillService->update($record, $data);
        return $record;
    }
}
