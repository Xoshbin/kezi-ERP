<?php

namespace App\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use App\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use App\Filament\Actions\DocsAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorBills extends ListRecords
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            DocsAction::make('vendor-bills'),
        ];
    }
}
