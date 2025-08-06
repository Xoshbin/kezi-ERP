<?php

namespace App\Actions\Purchases;

use App\Models\Tax;
use Brick\Money\Money;
use App\Models\Currency;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;
use App\DataTransferObjects\Purchases\CreateVendorBillDTO;

class CreateVendorBillAction
{
    public function execute(CreateVendorBillDTO $dto): VendorBill
    {
        return DB::transaction(function () use ($dto) {
            // 1. Fetch the currency model from the ID in the DTO
            $currency = Currency::findOrFail($dto->currency_id);

            $vendorBill = VendorBill::create([
                'company_id' => $dto->company_id,
                'vendor_id' => $dto->vendor_id,
                'currency_id' => $dto->currency_id,
                'bill_reference' => $dto->bill_reference,
                'bill_date' => $dto->bill_date,
                'due_date' => $dto->due_date,
                'status' => 'draft',
                'accounting_date' => $dto->accounting_date,
                'total_amount' => Money::of(0, $currency->code), // Initialize
                'total_tax' => Money::of(0, $currency->code),

            ]);

            foreach ($dto->lines as $lineDto) {
                // 2. Use the currency code from the fetched model
                $unitPrice = Money::of($lineDto->unit_price, $currency->code);
                $subtotal = $unitPrice->multipliedBy($lineDto->quantity);
                $totalLineTax = Money::of(0, $currency->code);

                if ($lineDto->tax_id) {
                    $tax = Tax::find($lineDto->tax_id);
                    if ($tax) {
                        $totalLineTax = $subtotal->multipliedBy($tax->rate / 100);
                    }
                }

                $vendorBill->lines()->create([
                    'product_id' => $lineDto->product_id,
                    'description' => $lineDto->description,
                    'quantity' => $lineDto->quantity,
                    'unit_price' => $unitPrice,
                    'tax_id' => $lineDto->tax_id,
                    'expense_account_id' => $lineDto->expense_account_id,
                    'analytic_account_id' => $lineDto->analytic_account_id,
                    'subtotal' => $subtotal,
                    'total_line_tax' => $totalLineTax,
                ]);
            }

            return $vendorBill->fresh();
        });
    }
}
