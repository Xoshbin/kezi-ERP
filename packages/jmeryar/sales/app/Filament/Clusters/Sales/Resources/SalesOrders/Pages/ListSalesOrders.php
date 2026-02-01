<?php

namespace Jmeryar\Sales\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Foundation\Filament\Actions\DocsAction;
use Jmeryar\Sales\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;

class ListSalesOrders extends ListRecords
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DocsAction::make('understanding-sales-orders'),
            Actions\CreateAction::make(),
        ];
    }
}
