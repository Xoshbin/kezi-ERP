<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\SerialNumberResource;

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
