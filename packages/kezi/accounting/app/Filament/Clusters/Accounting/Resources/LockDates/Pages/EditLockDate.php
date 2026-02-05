<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages;

use \Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\LockDateResource;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\LockDate>
 */
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
