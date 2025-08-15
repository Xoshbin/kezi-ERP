<?php

namespace App\Filament\Resources\LockDates\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LockDates\LockDateResource;
use Filament\Actions;
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
