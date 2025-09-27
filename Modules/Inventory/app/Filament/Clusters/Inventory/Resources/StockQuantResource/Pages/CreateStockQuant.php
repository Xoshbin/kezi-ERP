<?php

namespace App\Filament\Clusters\Inventory\Resources\StockQuantResource\Pages;

use App\Filament\Clusters\Inventory\Resources\StockQuantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockQuant extends CreateRecord
{
    protected static string $resource = StockQuantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }
}
