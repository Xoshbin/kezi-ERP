<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\Currency;
use App\Models\VendorBill;
use App\Services\AccountingValidationService;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;

class UpdateVendorBillAction
{
    public function __construct(
        private readonly AccountingValidationService $accountingValidationService = new AccountingValidationService()
    ) {}

    public function execute(UpdateVendorBillDTO $dto): VendorBill
    {
        $vendorBill = $dto->vendorBill;

        $this->accountingValidationService->checkIfPeriodIsLocked($vendorBill->company_id, $dto->bill_date);

        if ($vendorBill->status !== VendorBill::TYPE_DRAFT) {
            throw new UpdateNotAllowedException('Cannot update a posted vendor bill.');
        }

        return DB::transaction(function () use ($dto, $vendorBill) {
            $vendorBill->update([
                'vendor_id' => $dto->vendor_id,
                'currency_id' => $dto->currency_id,
                'bill_reference' => $dto->bill_reference,
                'bill_date' => $dto->bill_date,
                'accounting_date' => $dto->accounting_date,
                'due_date' => $dto->due_date,
            ]);

            // Sync the lines
            $vendorBill->lines()->delete();

            $currencyCode = Currency::find($dto->currency_id)->code;
            $linesToCreate = [];
            foreach ($dto->lines as $lineDto) {
                $linesToCreate[] = [
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => Money::of($lineDto->unit_price, $currencyCode),
                    'tax_id' => $lineDto->tax_id,
                    'expense_account_id' => $lineDto->expense_account_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                ];
            }

            if (!empty($linesToCreate)) {
                $vendorBill->lines()->createMany($linesToCreate);
            }

            // The model's saving observer will recalculate totals
            $vendorBill->save();

            return $vendorBill;
        });
    }
}
