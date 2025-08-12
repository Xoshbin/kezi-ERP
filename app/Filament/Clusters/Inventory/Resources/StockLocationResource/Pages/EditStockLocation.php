<?php

namespace App\Filament\Clusters\Inventory\Resources\StockLocationResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockLocation extends EditRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_location.edit_title', ['name' => $this->record->name]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
