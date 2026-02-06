<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;

/**
 * @extends ViewRecord<\Kezi\Manufacturing\Models\BillOfMaterial>
 */
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
