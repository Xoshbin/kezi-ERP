<?php

namespace App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockQuantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockQuant extends EditRecord
{
    protected static string $resource = StockQuantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
