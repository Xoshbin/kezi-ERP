<?php

namespace App\Filament\Clusters\Inventory\Resources\Products\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
