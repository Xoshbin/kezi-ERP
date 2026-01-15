<?php

namespace Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Modules\Foundation\Filament\Actions\DocsAction::make('manufacturing-orders'),
            \Modules\Foundation\Filament\Actions\DocsAction::make('understanding-work-orders'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
