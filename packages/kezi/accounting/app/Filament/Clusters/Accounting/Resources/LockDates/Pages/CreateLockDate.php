<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\LockDates\LockDateResource;

class CreateLockDate extends CreateRecord
{
    protected static string $resource = LockDateResource::class;
}
