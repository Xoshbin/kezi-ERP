<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource;

/**
 * @extends CreateRecord<\Kezi\Inventory\Models\StockPicking>
 */
class CreateStockPicking extends CreateRecord
{
    protected static string $resource = StockPickingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}
