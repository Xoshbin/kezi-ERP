<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;

class EditProduct extends EditRecord
{
    use Translatable;

    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }
}
