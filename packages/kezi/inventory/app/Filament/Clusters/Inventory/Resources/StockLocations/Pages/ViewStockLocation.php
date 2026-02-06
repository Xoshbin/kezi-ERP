<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;

/**
 * @extends ViewRecord<\Kezi\Inventory\Models\StockLocation>
 */
class ViewStockLocation extends ViewRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon('heroicon-o-pencil-square'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $name = (string) $record->getAttribute('name');

        return __('inventory::stock_location.view_title', ['name' => $name]);
    }
}
