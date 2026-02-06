<?php

namespace Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource;

/**
 * @extends CreateRecord<\Kezi\Product\Models\ProductCategory>
 */
class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
