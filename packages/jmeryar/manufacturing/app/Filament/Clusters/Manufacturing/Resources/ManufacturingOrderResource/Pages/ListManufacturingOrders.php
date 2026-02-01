<?php

namespace Jmeryar\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Jmeryar\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('manufacturing-orders'),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-work-orders'),
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-production-planning'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
