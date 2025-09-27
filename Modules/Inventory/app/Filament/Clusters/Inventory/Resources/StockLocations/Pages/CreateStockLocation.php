<?php

namespace Modules\Inventory\Filament\Clusters\Inventory\Resources\StockLocations\Pages;

use App\Filament\Clusters\Inventory\Resources\StockLocations\StockLocationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockLocation extends CreateRecord
{
    protected static string $resource = StockLocationResource::class;

    public function getTitle(): string
    {
        return __('stock_location.create_title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
