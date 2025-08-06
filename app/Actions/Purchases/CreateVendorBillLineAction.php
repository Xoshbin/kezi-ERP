<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Models\Tax;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CreateVendorBillLineAction
{
    public function execute(VendorBill $vendorBill, CreateVendorBillLineDTO $dto): VendorBillLine
    {
        $currencyCode = $dto->currency ?? $vendorBill->currency->code;
        $unitPrice = Money::of($dto->unit_price, $currencyCode);

        // Perform all calculations *before* creating the model.
        $subtotal = $unitPrice->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

        $taxAmount = Money::zero($currencyCode);
        if ($dto->tax_id && $tax = Tax::find($dto->tax_id)) {
            $taxAmount = $subtotal->multipliedBy($tax->rate, RoundingMode::HALF_UP);
        }

        // The create method now receives a complete, valid array of attributes.
        return $vendorBill->lines()->create([
            'product_id' => $dto->product_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit_price' => $unitPrice,
            'tax_id' => $dto->tax_id,
            'expense_account_id' => $dto->expense_account_id,
            'analytic_account_id' => $dto->analytic_account_id,
            'subtotal' => $subtotal, // Now explicitly provided
            'total_line_tax' => $taxAmount, // Now explicitly provided
        ]);
    }
}
