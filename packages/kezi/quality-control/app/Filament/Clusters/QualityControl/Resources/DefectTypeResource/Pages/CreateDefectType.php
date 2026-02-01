<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

class CreateDefectType extends CreateRecord
{
    protected static string $resource = DefectTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
