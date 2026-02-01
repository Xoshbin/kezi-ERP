<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

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
