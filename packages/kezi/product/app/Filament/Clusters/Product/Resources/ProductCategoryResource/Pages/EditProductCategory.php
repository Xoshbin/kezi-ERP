<?php

namespace Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kezi\Product\Filament\Clusters\Product\Resources\ProductCategoryResource;

/**
 * @extends EditRecord<\Kezi\Product\Models\ProductCategory>
 */
class EditProductCategory extends EditRecord
{
    protected static string $resource = ProductCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
