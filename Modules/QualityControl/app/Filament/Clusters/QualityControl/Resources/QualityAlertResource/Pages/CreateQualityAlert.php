<?php

namespace Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\QualityControl\Enums\QualityAlertStatus;
use Modules\QualityControl\Filament\Clusters\QualityControl\Resources\QualityAlertResource;

class CreateQualityAlert extends CreateRecord
{
    protected static string $resource = QualityAlertResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;
        $data['reported_by_user_id'] = auth()->id();
        $data['status'] = QualityAlertStatus::New->value;

        return $data;
    }
}
