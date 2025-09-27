<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockPickingResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockPickingResource;
use Filament\Resources\Pages\CreateRecord;

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
