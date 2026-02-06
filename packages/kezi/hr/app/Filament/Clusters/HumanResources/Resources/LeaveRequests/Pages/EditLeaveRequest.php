<?php

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;

/**
 * @extends EditRecord<\Kezi\HR\Models\LeaveRequest>
 */
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
