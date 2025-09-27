<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockQuantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
