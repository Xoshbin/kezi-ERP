<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

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
