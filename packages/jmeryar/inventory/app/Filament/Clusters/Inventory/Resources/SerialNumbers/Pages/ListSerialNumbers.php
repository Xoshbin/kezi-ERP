<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\SerialNumberResource;

class ListSerialNumbers extends ListRecords
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('serial-number-tracking'),
            // Serial numbers are typically created automatically during GRN
            // Manual creation can be uncommented if needed:
            // Actions\CreateAction::make(),
        ];
    }
}
