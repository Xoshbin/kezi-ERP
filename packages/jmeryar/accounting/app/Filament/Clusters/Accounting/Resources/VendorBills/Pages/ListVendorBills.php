<?php

namespace Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;
use Jmeryar\Foundation\Filament\Actions\DocsAction;

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
