<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Foundation\Filament\Actions\DocsAction;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\VendorBillResource;

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
