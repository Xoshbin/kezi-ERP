<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\Models\Company;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateVendorBillAction
{
    public function __construct(
        protected LockDateService $lockDateService,
        protected CreateVendorBillLineAction $createVendorBillLineAction
    ) {}

    public function execute(CreateVendorBillDTO $createVendorBillDTO): VendorBill
    {
        $this->lockDateService->enforce(Company::findOrFail($createVendorBillDTO->company_id), Carbon::parse($createVendorBillDTO->bill_date));

        return DB::transaction(function () use ($createVendorBillDTO) {
            $vendorBill = VendorBill::create([
                'company_id' => $createVendorBillDTO->company_id,
                'vendor_id' => $createVendorBillDTO->vendor_id,
                'currency_id' => $createVendorBillDTO->currency_id,
                'bill_reference' => $createVendorBillDTO->bill_reference,
                'bill_date' => $createVendorBillDTO->bill_date,
                'accounting_date' => $createVendorBillDTO->accounting_date,
                'due_date' => $createVendorBillDTO->due_date,
                'created_by_user_id' => $createVendorBillDTO->created_by_user_id,
                'payment_term_id' => $createVendorBillDTO->payment_term_id,
                // Add default zero values to satisfy NOT NULL constraints.
                // The VendorBillLineObserver will update these as lines are added.
                'subtotal' => 0,
                'total_tax' => 0,
                'total_amount' => 0,
            ]);

            foreach ($createVendorBillDTO->lines as $lineDTO) {
                $this->createVendorBillLineAction->execute($vendorBill, $lineDTO);
            }

            // The VendorBillLineObserver will handle recalculating totals.
            // We just need to reload the relationship to get the fresh data.
            $freshVendorBill = $vendorBill->fresh('lines');
            if (! $freshVendorBill) {
                throw new \Exception('Failed to refresh vendor bill after creation');
            }

            return $freshVendorBill;
        });
    }
}
