<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

class CreateDefectType extends CreateRecord
{
    protected static string $resource = DefectTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;

        return $data;
    }
}
