<?php

namespace App\Actions\Adjustments;

use App\DataTransferObjects\Adjustments\CreateAdjustmentDocumentDTO;
use App\Models\AdjustmentDocument;
use App\Models\Company;
use App\Models\Currency;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAdjustmentDocumentAction
{
    public function __construct(private readonly LockDateService $lockDateService)
    {
    }

    public function execute(CreateAdjustmentDocumentDTO $dto): AdjustmentDocument
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->date));

        return DB::transaction(function () use ($dto) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            // Create the header first with zero totals
            $adjustmentDocument = AdjustmentDocument::create([
                'company_id' => $dto->company_id,
                'type' => $dto->type,
                'date' => $dto->date,
                'reference_number' => $dto->reference_number,
                'reason' => $dto->reason,
                'currency_id' => $dto->currency_id,
                'original_invoice_id' => $dto->original_invoice_id,
                'original_vendor_bill_id' => $dto->original_vendor_bill_id,
                'total_amount' => Money::of(0, $currencyCode), // Initialize with 0
                'total_tax' => Money::of(0, $currencyCode),    // Initialize with 0
                'status' => AdjustmentDocument::STATUS_DRAFT,
            ]);

            // Create the lines from the DTO
            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => $lineDto->unit_price, // Already a Money object from DTO
                    'tax_id' => $lineDto->tax_id,
                    'account_id' => $lineDto->account_id,
                ];
            }

            if (!empty($linesToCreate)) {
                $adjustmentDocument->lines()->createMany($linesToCreate);
            }

            // The observer on the Line model will have triggered the parent's total recalculation.
            // We just need to refresh the model to get the latest calculated totals.
            return $adjustmentDocument->fresh();
        });
    }
}
