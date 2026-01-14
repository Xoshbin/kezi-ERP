<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\SerialNumberResource;

class ListSerialNumbers extends ListRecords
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Serial numbers are typically created automatically during GRN
            // Manual creation can be uncommented if needed:
            // Actions\CreateAction::make(),
        ];
    }
}
