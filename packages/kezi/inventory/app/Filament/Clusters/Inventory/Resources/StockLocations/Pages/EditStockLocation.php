<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use \Filament\Actions\DeleteAction;
use \Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;

/**
 * @extends EditRecord<\Kezi\Inventory\Models\StockLocation>
 */
class EditStockLocation extends EditRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        $name = (string) $record->getAttribute('name');

        return __('inventory::stock_location.edit_title', ['name' => $name]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
