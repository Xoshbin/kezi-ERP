<?php

namespace Jmeryar\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Jmeryar\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;

class ViewBillOfMaterial extends ViewRecord
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
