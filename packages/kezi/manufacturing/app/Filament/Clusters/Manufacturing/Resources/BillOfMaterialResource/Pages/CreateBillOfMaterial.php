<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;

class CreateBillOfMaterial extends CreateRecord
{
    protected static string $resource = BillOfMaterialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\Company $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant->id;

        return $data;
    }
}
