<?php

namespace Modules\Purchase\Actions\Purchases;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\Tax;
use Modules\Purchase\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Models\VendorBillLine;

class UpdateVendorBillAction
{
    public function __construct(protected \Modules\Accounting\Services\Accounting\LockDateService $lockDateService) {}

    public function execute(UpdateVendorBillDTO $updateVendorBillDTO): VendorBill
    {
        $vendorBill = $updateVendorBillDTO->vendorBill;

        if ($vendorBill->status !== VendorBillStatus::Draft) {
            throw new \Modules\Foundation\Exceptions\UpdateNotAllowedException('Only draft vendor bills can be updated.');
        }

        $this->lockDateService->enforce($vendorBill->company, Carbon::parse($updateVendorBillDTO->bill_date));

        return DB::transaction(function () use ($updateVendorBillDTO) {
            $vendorBill = $updateVendorBillDTO->vendorBill;

            $vendorBill->update([
                'vendor_id' => $updateVendorBillDTO->vendor_id,
                'currency_id' => $updateVendorBillDTO->currency_id,
                'bill_date' => $updateVendorBillDTO->bill_date,
                'due_date' => $updateVendorBillDTO->due_date,
                'bill_reference' => $updateVendorBillDTO->bill_reference,
                'incoterm' => $updateVendorBillDTO->incoterm ?? $vendorBill->incoterm,
            ]);

            $vendorBill->lines()->delete();

            $lines = [];
            foreach ($updateVendorBillDTO->lines as $line) {
                // Calculate subtotal and tax amounts
                $unitPrice = $line->unit_price;
                $subtotal = $unitPrice->multipliedBy($line->quantity, RoundingMode::HALF_UP);

                $taxAmount = Money::of(0, $vendorBill->currency->code);
                if ($line->tax_id) {
                    $tax = Tax::find($line->tax_id);
                    if ($tax) {
                        $taxRate = $tax->rate / 100;
                        $taxAmount = $subtotal->multipliedBy((string) $taxRate, RoundingMode::HALF_UP);
                    }
                }

                $lines[] = new VendorBillLine([
                    'company_id' => $vendorBill->company_id,
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'subtotal' => $subtotal,
                    'total_line_tax' => $taxAmount,
                    'expense_account_id' => $line->expense_account_id,
                    'tax_id' => $line->tax_id,
                    'analytic_account_id' => $line->analytic_account_id,
                    'shipping_cost_type' => $line->shipping_cost_type,
                    'asset_category_id' => $line->asset_category_id ?? null,
                ]);
            }

            $vendorBill->setRelation('lines', collect($lines));
            $vendorBill->calculateTotalsFromLines();
            $vendorBill->save();

            foreach ($lines as $line) {
                $line->vendor_bill_id = $vendorBill->id;
                $line->save();
            }

            return $vendorBill;
        });
    }
}
