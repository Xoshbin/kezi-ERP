<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
