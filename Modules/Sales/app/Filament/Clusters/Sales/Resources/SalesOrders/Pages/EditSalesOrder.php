<?php

namespace App\Filament\Clusters\Sales\Resources\SalesOrders\Pages;

use App\Filament\Clusters\Sales\Resources\SalesOrders\SalesOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
