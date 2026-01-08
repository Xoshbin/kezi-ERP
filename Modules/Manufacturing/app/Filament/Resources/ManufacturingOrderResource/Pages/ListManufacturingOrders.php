<?php

namespace Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;
}
