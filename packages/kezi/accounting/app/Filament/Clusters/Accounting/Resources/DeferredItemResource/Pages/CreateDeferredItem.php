<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource;

/**
 * @extends CreateRecord<\Kezi\Accounting\Models\DeferredItem>
 */
class CreateDeferredItem extends CreateRecord
{
    protected static string $resource = DeferredItemResource::class;
}
