<?php

namespace App\Filament\Resources\StockLocationResource\Pages;

use App\Filament\Resources\StockLocationResource;
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
