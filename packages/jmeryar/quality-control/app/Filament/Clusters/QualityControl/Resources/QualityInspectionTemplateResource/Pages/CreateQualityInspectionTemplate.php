<?php

namespace Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Jmeryar\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

class CreateQualityInspectionTemplate extends CreateRecord
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
