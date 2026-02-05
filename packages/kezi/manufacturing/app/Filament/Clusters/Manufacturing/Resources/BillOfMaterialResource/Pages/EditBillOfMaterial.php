<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;

/**
 * @extends EditRecord<\Kezi\Manufacturing\Models\BillOfMaterial>
 */
class EditBillOfMaterial extends EditRecord
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
