<?php

namespace App\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
