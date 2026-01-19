<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityControlPointResource;

class CreateQualityControlPoint extends CreateRecord
{
    protected static string $resource = QualityControlPointResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->getKey();

        return $data;
    }
}
