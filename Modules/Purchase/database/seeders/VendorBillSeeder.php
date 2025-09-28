<?php

namespace Modules\Purchase\Database\Seeders;

use Carbon\Carbon;
use Brick\Money\Money;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Tax;
use Modules\Product\Models\Product;

use Modules\Foundation\Models\Partner;
use Modules\Foundation\Models\Currency;
use Modules\Purchase\Models\VendorBill;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Modules\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;

class VendorBillSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $usdCurrency = Currency::where('code', 'USD')->firstOrFail();
        $tax = Tax::where('company_id', $company->id)->where('rate', 5)->firstOrFail();

        // --- Fetch Products (assuming they are created by ProductSeeder) ---
        $gpuProduct = Product::where('company_id', $company->id)->where('sku', 'GPU-RTX4090')->firstOrFail();
        $ramProduct = Product::where('company_id', $company->id)->where('sku', 'RAM-DDR5-32GB')->firstOrFail();
        $ssdProduct = Product::where('company_id', $company->id)->where('sku', 'SSD-2TB-NVME')->firstOrFail();

        // --- Fetch/Create Vendor ---
        $vendor = Partner::firstOrCreate(
            ['name' => 'TechGlobal Suppliers', 'company_id' => $company->id],
            ['type' => \Modules\Foundation\Enums\Partners\PartnerType::Vendor]
        );

        $createLineAction = resolve(CreateVendorBillLineAction::class);

        // === Multi-Currency BILL: USD Purchase ===
        $bill = VendorBill::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $vendor->id, 'bill_reference' => 'VB-2025-001'],
            [
                'bill_date' => Carbon::parse('2025-01-15'),
                'accounting_date' => Carbon::parse('2025-01-15'),
                'due_date' => Carbon::parse('2025-01-15')->addDays(30),
                'status' => 'draft',
                'currency_id' => $usdCurrency->id,
                'exchange_rate_at_creation' => 1310,
                'total_amount' => Money::of(0, $usdCurrency->code), // Observer will update
                'total_tax' => Money::of(0, $usdCurrency->code),
            ]
        );

        if ($bill->wasRecentlyCreated) {
            // 1) GPU-RTX4090: 10 units @ $1,900 USD
            $createLineAction->execute(
                $bill,
                new CreateVendorBillLineDTO(
                    product_id: $gpuProduct->id,
                    description: $gpuProduct->name,
                    quantity: 10,
                    unit_price: Money::of('1900', $usdCurrency->code),
                    expense_account_id: $gpuProduct->expense_account_id,
                    tax_id: $tax->id,
                    analytic_account_id: null
                )
            );

            // 2) RAM-DDR5-32GB: 50 units @ $305 USD
            $createLineAction->execute(
                $bill,
                new CreateVendorBillLineDTO(
                    product_id: $ramProduct->id,
                    description: $ramProduct->name,
                    quantity: 50,
                    unit_price: Money::of('305', $usdCurrency->code),
                    expense_account_id: $ramProduct->expense_account_id,
                    tax_id: $tax->id,
                    analytic_account_id: null
                )
            );

            // 3) SSD-2TB-NVME: 30 units @ $229 USD
            $createLineAction->execute(
                $bill,
                new CreateVendorBillLineDTO(
                    product_id: $ssdProduct->id,
                    description: $ssdProduct->name,
                    quantity: 30,
                    unit_price: Money::of('229', $usdCurrency->code),
                    expense_account_id: $ssdProduct->expense_account_id,
                    tax_id: $tax->id,
                    analytic_account_id: null
                )
            );
        }
    }
}
