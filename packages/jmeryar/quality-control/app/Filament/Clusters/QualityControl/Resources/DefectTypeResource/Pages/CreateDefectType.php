<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\DefectTypeResource;

class CreateDefectType extends CreateRecord
{
    protected static string $resource = DefectTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
