<?php

namespace Modules\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use App\Actions\Purchases\CreatePurchaseOrderAction;
use App\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
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
