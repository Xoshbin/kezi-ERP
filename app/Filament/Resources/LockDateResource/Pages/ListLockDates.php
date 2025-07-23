<?php

namespace App\Filament\Resources\LockDateResource\Pages;

use App\Filament\Resources\LockDateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLockDates extends ListRecords
{
    protected static string $resource = LockDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
