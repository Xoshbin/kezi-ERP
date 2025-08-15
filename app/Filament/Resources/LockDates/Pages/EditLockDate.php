<?php

namespace App\Filament\Resources\LockDates\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\LockDates\LockDateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLockDate extends EditRecord
{
    protected static string $resource = LockDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
