<?php

namespace App\Filament\Resources\VendorBills\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\VendorBills\VendorBillResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendorBills extends ListRecords
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
