<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use \Filament\Actions\DeleteAction;
use \Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;

/**
 * @extends EditRecord<\Kezi\Inventory\Models\StockPicking>
 */
class EditStockPicking extends EditRecord
{
    protected static string $resource = StockPickingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon('heroicon-o-eye'),
            DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }
}
