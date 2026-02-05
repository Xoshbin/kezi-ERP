<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

/**
 * @extends EditRecord<\Kezi\Inventory\Models\StockQuant>
 */
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
