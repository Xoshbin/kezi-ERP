<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

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

        return __('stock_location.view_title', ['name' => $name]);
    }
}
