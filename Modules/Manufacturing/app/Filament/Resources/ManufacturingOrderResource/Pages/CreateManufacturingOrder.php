<?php

namespace Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Filament\Resources\ManufacturingOrderResource;
use Modules\Manufacturing\Services\ManufacturingOrderService;

class CreateManufacturingOrder extends CreateRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreateManufacturingOrderDTO(
            companyId: $data['company_id'],
            bomId: $data['bom_id'],
            productId: $data['product_id'],
            quantityToProduce: (float) $data['quantity_to_produce'],
            sourceLocationId: $data['source_location_id'],
            destinationLocationId: $data['destination_location_id'],
            plannedStartDate: $data['planned_start_date'] ?? null,
            plannedEndDate: $data['planned_end_date'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return app(ManufacturingOrderService::class)->create($dto);
    }
}
