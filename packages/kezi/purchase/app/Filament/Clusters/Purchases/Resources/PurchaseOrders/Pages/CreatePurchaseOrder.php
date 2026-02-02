<?php

namespace Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Kezi\Purchase\Actions\Purchases\CreatePurchaseOrderAction;
use Kezi\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderDTO;
use Kezi\Purchase\Filament\Clusters\Purchases\Resources\PurchaseOrders\PurchaseOrderResource;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        \Illuminate\Support\Facades\Log::info('DEBUG_CREATE_PO: Starting handleRecordCreation', ['data_keys' => array_keys($data)]);

        // Add tenant company_id and current user_id to the data
        $data['company_id'] = filament()->getTenant()->getKey();
        $data['created_by_user_id'] = auth()->id();

        \Illuminate\Support\Facades\Log::info('DEBUG_CREATE_PO: Prepared data', ['company_id' => $data['company_id'], 'user_id' => $data['created_by_user_id']]);

        $dto = CreatePurchaseOrderDTO::fromArray($data);

        \Illuminate\Support\Facades\Log::info('DEBUG_CREATE_PO: Executing Action');

        $result = app(CreatePurchaseOrderAction::class)->execute($dto);

        \Illuminate\Support\Facades\Log::info('DEBUG_CREATE_PO: Action successful', ['id' => $result->id]);

        return $result;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
