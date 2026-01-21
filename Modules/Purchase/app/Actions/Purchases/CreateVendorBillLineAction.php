<?php

namespace Modules\Purchase\Actions\Purchases;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

class CreateVendorBillLineAction
{
    public function execute(VendorBill $vendorBill, CreateVendorBillLineDTO $dto): VendorBillLine
    {
        if ($dto->product_id) {
            $product = \Modules\Product\Models\Product::find($dto->product_id);
            if ($product && $product->is_template) {
                throw new \InvalidArgumentException('Cannot create vendor bill lines for template products');
            }
        }

        $currency = $vendorBill->currency;

        // 1. Explicitly create the Money object from the DTO.
        $unitPrice = $dto->unit_price instanceof Money
            ? $dto->unit_price
            : Money::of($dto->unit_price, $currency->code, null, RoundingMode::HALF_UP);

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
            'shipping_cost_type' => $dto->shipping_cost_type,
            'analytic_account_id' => $dto->analytic_account_id,
            'asset_category_id' => $dto->asset_category_id,
            'deferred_start_date' => $dto->deferred_start_date,
            'deferred_end_date' => $dto->deferred_end_date,
        ]);
    }
}
