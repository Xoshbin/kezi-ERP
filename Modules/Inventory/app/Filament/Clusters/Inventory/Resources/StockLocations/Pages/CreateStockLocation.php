<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;

class CreateStockLocation extends CreateRecord
{
    protected static string $resource = StockLocationResource::class;

    public function getTitle(): string
    {
        return __('inventory::stock_location.create_title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = \Filament\Facades\Filament::getTenant()->id;

        return $data;
    }
}
