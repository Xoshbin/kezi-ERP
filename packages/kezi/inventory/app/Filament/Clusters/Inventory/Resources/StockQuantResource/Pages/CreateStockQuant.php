<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\StockQuantResource;

class CreateStockQuant extends CreateRecord
{
    protected static string $resource = StockQuantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;

        return $data;
    }
}
