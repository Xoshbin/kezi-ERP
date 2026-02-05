<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use \Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('inventory-management'),
            CreateAction::make(),
        ];
    }
}
