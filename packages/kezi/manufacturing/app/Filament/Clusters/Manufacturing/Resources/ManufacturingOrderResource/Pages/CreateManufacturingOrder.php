<?php

namespace Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Kezi\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\ManufacturingOrderResource;
use Kezi\Manufacturing\Services\ManufacturingOrderService;

class CreateManufacturingOrder extends CreateRecord
{
    protected static string $resource = ManufacturingOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\Company $tenant */
        $tenant = Filament::getTenant();
        $data['company_id'] = $tenant->id;

        if (empty($data['product_id']) && ! empty($data['bom_id'])) {
            $bom = \Kezi\Manufacturing\Models\BillOfMaterial::find($data['bom_id']);
            if ($bom) {
                $data['product_id'] = $bom->product_id;
            }
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \App\Models\Company $tenant */
        $tenant = Filament::getTenant();

        if (empty($data['product_id']) && ! empty($data['bom_id'])) {
            $bom = \Kezi\Manufacturing\Models\BillOfMaterial::find($data['bom_id']);
            if ($bom) {
                $data['product_id'] = $bom->product_id;
            }
        }

        $dto = new CreateManufacturingOrderDTO(
            companyId: $tenant->id,
            bomId: $data['bom_id'],
            productId: $data['product_id'],
            quantityToProduce: (float) $data['quantity_to_produce'],
            sourceLocationId: $data['source_location_id'],
            destinationLocationId: $data['destination_location_id'],
            plannedStartDate: ! empty($data['planned_start_date']) ? \Carbon\Carbon::parse($data['planned_start_date']) : null,
            plannedEndDate: ! empty($data['planned_end_date']) ? \Carbon\Carbon::parse($data['planned_end_date']) : null,
            notes: $data['notes'] ?? null,
        );

        return app(ManufacturingOrderService::class)->create($dto);
    }
}
