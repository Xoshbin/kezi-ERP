<?php

namespace Modules\Manufacturing\Filament\Resources\BillOfMaterialResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Manufacturing\Filament\Resources\BillOfMaterialResource;

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
