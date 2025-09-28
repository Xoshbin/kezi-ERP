<?php

namespace Modules\Purchase\Actions\Purchases;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Modules\Accounting\Models\Tax;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

class CreateVendorBillLineAction
{
    public function execute(VendorBill $vendorBill, CreateVendorBillLineDTO $dto): VendorBillLine
    {
        $currency = $vendorBill->currency;

        // 1. Explicitly create the Money object from the DTO.
        $unitPrice = $dto->unit_price instanceof Money
            ? $dto->unit_price
            : Money::of($dto->unit_price, $currency->code);

        // 2. Perform calculations with full context.
        $subtotal = $unitPrice->multipliedBy($dto->quantity, RoundingMode::HALF_UP);

        $taxAmount = Money::of(0, $currency->code);
        if ($dto->tax_id) {
            $tax = Tax::findOrFail($dto->tax_id);
            $taxRate = $tax->rate / 100;
            $taxAmount = $subtotal->multipliedBy((string) $taxRate, RoundingMode::HALF_UP);
        }

        // 3. Create the model with pre-calculated values.
        return VendorBillLine::create([
            'company_id' => $vendorBill->company_id,
            'vendor_bill_id' => $vendorBill->id,
            'product_id' => $dto->product_id,
            'description' => $dto->description,
            'quantity' => $dto->quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'total_line_tax' => $taxAmount,
            'expense_account_id' => $dto->expense_account_id,
            'tax_id' => $dto->tax_id,
            'analytic_account_id' => $dto->analytic_account_id,
            'asset_category_id' => $dto->asset_category_id,
        ]);
    }
}
