<?php

declare(strict_types=1);

namespace Modules\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\HR\Filament\Clusters\HumanResources\Resources\Attendances\AttendanceResource;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('attendance-management'),
            Actions\CreateAction::make(),
        ];
    }
}
