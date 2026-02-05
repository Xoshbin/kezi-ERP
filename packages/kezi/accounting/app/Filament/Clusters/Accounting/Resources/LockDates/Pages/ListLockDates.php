<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\LockDateResource;

class ListLockDates extends ListRecords
{
    protected static string $resource = LockDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('lock-dates'),
        ];
    }
}
