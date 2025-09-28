<?php

namespace Modules\Accounting\Filament\Clusters\Settings\Resources\LockDates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Filament\Clusters\Settings\Resources\LockDates\LockDateResource;

class CreateLockDate extends CreateRecord
{
    protected static string $resource = LockDateResource::class;
}
