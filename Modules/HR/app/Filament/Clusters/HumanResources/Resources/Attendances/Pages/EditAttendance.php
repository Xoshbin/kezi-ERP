<?php

declare(strict_types=1);

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Attendances\AttendanceResource;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;
}
