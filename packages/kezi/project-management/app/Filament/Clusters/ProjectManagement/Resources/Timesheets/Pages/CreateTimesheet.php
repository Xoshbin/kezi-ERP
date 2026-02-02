<?php

namespace Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\ProjectManagement\Filament\Clusters\ProjectManagement\Resources\Timesheets\TimesheetResource;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $companyId = \Filament\Facades\Filament::getTenant()->id;
        $data['company_id'] = $companyId;

        if (isset($data['lines'])) {
            foreach ($data['lines'] as $key => $line) {
                $data['lines'][$key]['company_id'] = $companyId;
            }
        }

        return $data;
    }
}
