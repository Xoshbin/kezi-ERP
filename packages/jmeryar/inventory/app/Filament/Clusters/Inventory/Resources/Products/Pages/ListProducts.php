<?php

namespace Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('inventory-management'),
            CreateAction::make(),
        ];
    }
}
