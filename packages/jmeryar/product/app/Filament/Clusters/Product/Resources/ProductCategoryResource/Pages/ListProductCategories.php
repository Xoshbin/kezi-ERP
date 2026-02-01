<?php

namespace Jmeryar\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Product\Filament\Clusters\Product\Resources\ProductCategoryResource;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('product-category'),
            CreateAction::make(),
        ];
    }
}
