<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockPickings extends ListRecords
{
    protected static string $resource = StockPickingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }
}
