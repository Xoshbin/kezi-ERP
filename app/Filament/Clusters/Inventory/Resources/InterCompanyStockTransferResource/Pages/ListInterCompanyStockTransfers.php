<?php

namespace App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource\Pages;

use App\Filament\Clusters\Inventory\Resources\InterCompanyStockTransferResource;
use Filament\Resources\Pages\ListRecords;

class ListInterCompanyStockTransfers extends ListRecords
{
    protected static string $resource = InterCompanyStockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are defined in the resource table headerActions
        ];
    }
}
