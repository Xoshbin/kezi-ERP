<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Models\Product; // Make sure Product is imported
use App\Models\Tax;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Brick\Money\Money;
// It seems RoundingMode was used here before, let's keep it for consistency
use Brick\Math\RoundingMode;

class CreateVendorBillLineAction
{
    public function execute(VendorBill $vendorBill, CreateVendorBillLineDTO $dto): VendorBillLine
    {
        // CORRECTED: This logic handles both Money objects and strings for unit_price.
        $unitPrice = $dto->unit_price instanceof Money
            ? $dto->unit_price
            : Money::of($dto->unit_price, $vendorBill->currency->code);

        // All subsequent calculations will now work correctly.
        $currency = $unitPrice->getCurrency();
        $product = Product::find($dto->product_id); // Find is fine for single action context
        $description = $dto->description ?? $product?->name;
        $subtotal = $unitPrice->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

        $taxAmount = Money::zero($currency);
        if ($dto->tax_id && $tax = Tax::find($dto->tax_id)) {
            $taxAmount = $subtotal->multipliedBy($tax->rate, RoundingMode::HALF_UP);
        }

        return $vendorBill->lines()->create([
            'product_id' => $dto->product_id,
            'description' => $description,
            'quantity' => $dto->quantity,
            'unit_price' => $unitPrice,
            'tax_id' => $dto->tax_id,
            'expense_account_id' => $dto->expense_account_id,
            'analytic_account_id' => $dto->analytic_account_id,
            'subtotal' => $subtotal,
            'total_line_tax' => $taxAmount,
        ]);
    }
}
