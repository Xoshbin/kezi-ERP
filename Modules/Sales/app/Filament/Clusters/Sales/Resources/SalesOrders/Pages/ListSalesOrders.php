<?php

namespace Modules\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use App\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
