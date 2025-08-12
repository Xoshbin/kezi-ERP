<?php

namespace App\Filament\Clusters\Inventory\Resources\StockLocationResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockLocation extends ViewRecord
{
    protected static string $resource = StockLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function getTitle(): string
    {
        return __('stock_location.view_title', ['name' => $this->record->name]);
    }
}
