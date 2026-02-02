<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;

class ListManufacturingOrders extends ListRecords
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('manufacturing-orders'),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-work-orders'),
            \Kezi\Foundation\Filament\Actions\DocsAction::make('understanding-production-planning'),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
