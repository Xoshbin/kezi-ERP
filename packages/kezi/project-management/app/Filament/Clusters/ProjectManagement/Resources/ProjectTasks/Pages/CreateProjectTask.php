<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\ProjectTasks\ProjectTaskResource;

class CreateProjectTask extends CreateRecord
{
    protected static string $resource = ProjectTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
