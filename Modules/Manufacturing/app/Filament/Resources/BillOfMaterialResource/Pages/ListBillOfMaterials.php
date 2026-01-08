<?php

namespace Modules\Manufacturing\Filament\Resources\BillOfMaterialResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Manufacturing\Filament\Resources\BillOfMaterialResource;

class ListBillOfMaterials extends ListRecords
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
