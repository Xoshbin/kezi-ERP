<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\QualityControl\Enums\QualityAlertStatus;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

/**
 * @extends CreateRecord<\Kezi\QualityControl\Models\QualityAlert>
 */
class CreateQualityAlert extends CreateRecord
{
    protected static string $resource = QualityAlertResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;
        $data['reported_by_user_id'] = auth()->id();
        $data['status'] = QualityAlertStatus::New->value;

        return $data;
    }
}
