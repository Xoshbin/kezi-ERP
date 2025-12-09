<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

class ListStockQuants extends ListRecords
{
    protected static string $resource = StockQuantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
