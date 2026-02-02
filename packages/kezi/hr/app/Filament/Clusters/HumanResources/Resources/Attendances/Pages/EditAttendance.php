<?php

declare(strict_types=1);

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\AttendanceResource;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;
}
