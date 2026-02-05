<?php

namespace Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource;

/**
 * @extends EditRecord<\Kezi\Accounting\Models\DeferredItem>
 */
class EditDeferredItem extends EditRecord
{
    protected static string $resource = DeferredItemResource::class;
}
