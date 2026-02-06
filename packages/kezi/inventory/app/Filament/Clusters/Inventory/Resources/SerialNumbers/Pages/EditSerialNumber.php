<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\SerialNumberResource;

/**
 * @extends EditRecord<\Kezi\Inventory\Models\SerialNumber>
 */
class EditSerialNumber extends EditRecord
{
    protected static string $resource = SerialNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
