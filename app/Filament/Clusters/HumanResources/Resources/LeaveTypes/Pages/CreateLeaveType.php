<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Pages;

use App\Filament\Clusters\HumanResources\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveType extends CreateRecord
{
    protected static string $resource = LeaveTypeResource::class;
}
