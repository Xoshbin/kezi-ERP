<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

class CreateQualityInspectionTemplate extends CreateRecord
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;

        return $data;
    }
}
