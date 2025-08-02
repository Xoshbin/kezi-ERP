<?php

namespace App\Actions\Purchases;

use App\DataTransferObjects\Purchases\CreateVendorBillDTO;
use App\Models\Company;
use App\Models\Currency;
use App\Models\VendorBill;
use App\Services\Accounting\LockDateService;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateVendorBillAction
{
    public function __construct(private readonly LockDateService $lockDateService)
    {
    }

    public function execute(CreateVendorBillDTO $dto): VendorBill
    {
        $company = Company::findOrFail($dto->company_id);
        $this->lockDateService->enforce($company, Carbon::parse($dto->bill_date));

        return DB::transaction(function () use ($dto) {
            $currencyCode = Currency::find($dto->currency_id)->code;

            $vendorBill = VendorBill::create([
                'company_id' => $dto->company_id,
                'vendor_id' => $dto->vendor_id,
                'currency_id' => $dto->currency_id,
                'bill_reference' => $dto->bill_reference,
                'bill_date' => $dto->bill_date,
                'accounting_date' => $dto->accounting_date,
                'due_date' => $dto->due_date,
                'status' => VendorBill::STATUS_DRAFT,
                'total_amount' => Money::of(0, $currencyCode), // Initialize
                'total_tax' => Money::of(0, $currencyCode),    // Initialize
            ]);

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

            // The saving observer on the model will calculate totals.
            $vendorBill->save();

            return $vendorBill;
        });
    }
}
