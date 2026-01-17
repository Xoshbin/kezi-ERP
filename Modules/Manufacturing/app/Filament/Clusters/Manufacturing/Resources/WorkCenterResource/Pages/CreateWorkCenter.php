<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\WorkCenterResource;

class CreateWorkCenter extends CreateRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\Company $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant->id;
        $data['currency_code'] = $tenant->currency->code;

        return $data;
    }
}
