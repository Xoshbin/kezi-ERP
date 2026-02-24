<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\InventoryDiscrepancyResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\InventoryDiscrepancyResource;

class ListInventoryDiscrepancies extends ListRecords
{
    protected static string $resource = InventoryDiscrepancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-adjustments'),
            // No create button for discrepancies - they are created by the system
        ];
    }
}
