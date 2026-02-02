<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;
        $data['requested_by_user_id'] = auth()->id();
        $data['submitted_at'] = now();

        return $data;
    }
}
