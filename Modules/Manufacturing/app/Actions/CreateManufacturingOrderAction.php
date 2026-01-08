<?php

namespace Modules\Manufacturing\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Foundation\Services\SequenceService;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;

class CreateManufacturingOrderAction
{
    public function __construct(
        private readonly SequenceService $sequenceService,
    ) {}

    public function execute(CreateManufacturingOrderDTO $dto): ManufacturingOrder
    {
        return DB::transaction(function () use ($dto) {
            // Get the BOM to copy its lines
            $bom = BillOfMaterial::with('lines')->findOrFail($dto->bomId);

            // Generate MO number
            $company = \App\Models\Company::findOrFail($dto->companyId);
            $number = $this->sequenceService->getNextNumber($company, 'manufacturing_order', 'MO', 5);

            // Create the Manufacturing Order
            $mo = ManufacturingOrder::create([
                'company_id' => $dto->companyId,
                'number' => $number,
                'bom_id' => $dto->bomId,
                'product_id' => $dto->productId,
                'quantity_to_produce' => $dto->quantityToProduce,
                'quantity_produced' => 0,
                'status' => ManufacturingOrderStatus::Draft,
                'planned_start_date' => $dto->plannedStartDate,
                'planned_end_date' => $dto->plannedEndDate,
                'source_location_id' => $dto->sourceLocationId,
                'destination_location_id' => $dto->destinationLocationId,
                'notes' => $dto->notes,
            ]);

            // Create MO lines from BOM lines
            foreach ($bom->lines as $bomLine) {
                $mo->lines()->create([
                    'company_id' => $dto->companyId,
                    'product_id' => $bomLine->product_id,
                    'quantity_required' => $bomLine->quantity * $dto->quantityToProduce,
                    'quantity_consumed' => 0,
                    'unit_cost' => $bomLine->unit_cost,
                    'currency_code' => $bomLine->currency_code,
                ]);
            }

            return $mo->fresh(['lines', 'billOfMaterial', 'product']);
        });
    }
}
