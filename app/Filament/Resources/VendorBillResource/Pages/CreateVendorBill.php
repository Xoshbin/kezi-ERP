<?php

namespace App\Filament\Resources\VendorBillResource\Pages;

use App\Filament\Resources\VendorBillResource;
use App\Services\VendorBillService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $vendorBillService = new VendorBillService();
        $vendorBill = static::getModel()::create(collect($data)->except('lines')->all());
        $vendorBillService->update($vendorBill, ['lines' => $data['lines'] ?? []]);
        return $vendorBill;
    }
}
