<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityInspectionTemplateResource;

/**
 * @extends CreateRecord<\Kezi\QualityControl\Models\QualityInspectionTemplate>
 */
class CreateQualityInspectionTemplate extends CreateRecord
{
    protected static string $resource = QualityInspectionTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
