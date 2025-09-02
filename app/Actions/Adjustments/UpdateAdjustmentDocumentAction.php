<?php

namespace App\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentLineDTO;
use App\DataTransferObjects\Adjustments\UpdateAdjustmentDocumentDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\AdjustmentDocument;
use App\Enums\Adjustments\AdjustmentDocumentStatus;
use Illuminate\Support\Facades\DB;

class UpdateAdjustmentDocumentAction
{
    public function __construct(
        private readonly CreateAdjustmentDocumentLineAction $createAdjustmentDocumentLineAction
    ) {
    }

    public function execute(UpdateAdjustmentDocumentDTO $dto): AdjustmentDocument
    {
        $adjustmentDocument = $dto->adjustmentDocument;

        if ($adjustmentDocument->status !== AdjustmentDocumentStatus::Draft) {
            throw new UpdateNotAllowedException('Cannot modify a posted adjustment document.');
        }

        return DB::transaction(function () use ($dto, $adjustmentDocument) {
            $adjustmentDocument->update([
                'type' => $dto->type->value,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
            ]);

            // Sync the lines: delete old ones, create new ones.
            $adjustmentDocument->lines()->delete();

            // Create new lines using the dedicated line action
            foreach ($dto->lines as $lineDto) {
                // Convert UpdateAdjustmentDocumentLineDTO to CreateAdjustmentDocumentLineDTO
                $createLineDto = new CreateAdjustmentDocumentLineDTO(
                    description: $lineDto->description,
                    quantity: $lineDto->quantity,
                    unit_price: $lineDto->unit_price,
                    account_id: $lineDto->account_id,
                    product_id: $lineDto->product_id,
                    tax_id: $lineDto->tax_id
                );
                $this->createAdjustmentDocumentLineAction->execute($adjustmentDocument, $createLineDto);
            }

            // The model's saving observer will recalculate totals automatically.
            $adjustmentDocument->save();

            return $adjustmentDocument;
        });
    }
}
