<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;

/**
 * @extends EditRecord<\Kezi\Manufacturing\Models\ManufacturingOrder>
 */
class EditManufacturingOrder extends EditRecord
{
    protected static string $resource = ManufacturingOrderResource::class;
}
