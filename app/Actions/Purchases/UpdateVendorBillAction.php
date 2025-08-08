<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\UpdateVendorBillDTO;
use App\Enums\Purchases\VendorBillStatus;
use App\Exceptions\UpdateNotAllowedException;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use Illuminate\Support\Facades\DB;

class UpdateVendorBillAction
{
    public function __construct(protected LockDateService $lockDateService)
    {
    }

    public function execute(UpdateVendorBillDTO $updateVendorBillDTO): VendorBill
    {
        $vendorBill = $updateVendorBillDTO->vendorBill;

        if ($vendorBill->status !== VendorBillStatus::Draft) {
            throw new UpdateNotAllowedException('Only draft vendor bills can be updated.');
        }

        $this->lockDateService->enforce($vendorBill->company, \Carbon\Carbon::parse($updateVendorBillDTO->bill_date));


        return DB::transaction(function () use ($updateVendorBillDTO) {
            $vendorBill = $updateVendorBillDTO->vendorBill;

            $vendorBill->update([
                'vendor_id' => $updateVendorBillDTO->vendor_id,
                'currency_id' => $updateVendorBillDTO->currency_id,
                'bill_date' => $updateVendorBillDTO->bill_date,
                'due_date' => $updateVendorBillDTO->due_date,
                'bill_reference' => $updateVendorBillDTO->bill_reference,
            ]);

            $vendorBill->lines()->delete();

            $lines = [];
            foreach ($updateVendorBillDTO->lines as $line) {
                $lines[] = new \App\Models\VendorBillLine([
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'expense_account_id' => $line->expense_account_id,
                    'tax_id' => $line->tax_id,
                    'analytic_account_id' => $line->analytic_account_id,
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