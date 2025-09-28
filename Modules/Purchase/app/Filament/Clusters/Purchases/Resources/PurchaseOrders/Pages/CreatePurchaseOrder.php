<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use Modules\Purchase\Actions\Purchases\CreatePurchaseOrderAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Add tenant company_id and current user_id to the data
        $data['company_id'] = filament()->getTenant()->getKey();
        $data['created_by_user_id'] = auth()->id();

        $dto = CreatePurchaseOrderDTO::fromArray($data);

        return app(CreatePurchaseOrderAction::class)->execute($dto);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
