<?php

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
