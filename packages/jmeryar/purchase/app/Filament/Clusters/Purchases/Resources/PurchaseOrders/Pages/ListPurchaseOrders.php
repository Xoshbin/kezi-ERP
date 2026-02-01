<?php

namespace Jmeryar\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Jmeryar\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Jmeryar\Foundation\Filament\Actions\DocsAction::make('understanding-purchase-orders'),
            CreateAction::make(),
        ];
    }
}
