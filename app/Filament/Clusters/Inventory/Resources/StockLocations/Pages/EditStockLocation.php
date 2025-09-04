<?php

namespace App\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

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
        return __('stock_location.edit_title', ['name' => $name]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
