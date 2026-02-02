<?php

declare(strict_types=1);

namespace Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Kezi\HR\Filament\Clusters\HumanResources\Resources\Attendances\AttendanceResource;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('attendance-management'),
            Actions\CreateAction::make(),
        ];
    }
}
