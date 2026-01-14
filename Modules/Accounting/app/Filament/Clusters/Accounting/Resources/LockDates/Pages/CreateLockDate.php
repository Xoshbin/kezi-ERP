<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\LockDates\LockDateResource;

class CreateLockDate extends CreateRecord
{
    protected static string $resource = LockDateResource::class;
}
