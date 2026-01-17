<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;
}
