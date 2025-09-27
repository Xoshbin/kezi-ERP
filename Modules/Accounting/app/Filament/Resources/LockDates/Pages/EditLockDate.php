<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\LockDates\Pages;

use App\Filament\Clusters\Settings\Resources\LockDates\LockDateResource;
use Filament\Actions\DeleteAction;
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
