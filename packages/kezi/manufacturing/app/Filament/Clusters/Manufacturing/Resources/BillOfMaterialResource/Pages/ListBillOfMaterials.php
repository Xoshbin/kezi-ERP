<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;

class ListBillOfMaterials extends ListRecords
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('bill-of-materials'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
