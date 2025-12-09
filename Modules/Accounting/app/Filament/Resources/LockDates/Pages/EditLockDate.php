<?php

namespace Modules\Accounting\Filament\Resources\LockDates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Accounting\Filament\Resources\LockDates\LockDateResource;

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
