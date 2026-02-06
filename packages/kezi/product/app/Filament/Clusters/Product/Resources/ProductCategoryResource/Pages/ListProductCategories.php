<?php

namespace Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Kezi\Foundation\Filament\Actions\DocsAction::make('product-category'),
            CreateAction::make(),
        ];
    }
}
