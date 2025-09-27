<?php

namespace App\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockLocations extends ListRecords
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_location.plural_label');
    }
}
