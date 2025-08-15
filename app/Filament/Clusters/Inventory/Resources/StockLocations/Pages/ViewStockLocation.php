<?php

namespace App\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;
use Filament\Actions;
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
        return __('stock_location.view_title', ['name' => $this->record->name]);
    }
}
