<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\LockDates\Pages;

use App\Filament\Clusters\Settings\Resources\LockDates\LockDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLockDates extends ListRecords
{
    protected static string $resource = LockDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
