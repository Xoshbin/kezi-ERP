<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

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
