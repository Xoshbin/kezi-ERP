<?php

namespace Jmeryar\Purchase\Database\Seeders;

use App\Models\Company;
use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Purchase\Actions\Purchases\CreatePurchaseOrderLineAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreatePurchaseOrderLineDTO;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Models\PurchaseOrder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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
            ['type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Vendor]
        );

        $createLineAction = resolve(CreatePurchaseOrderLineAction::class);

        // === Multi-Currency PO: USD Purchase ===
        $purchaseOrder = PurchaseOrder::updateOrCreate(
            ['company_id' => $company->id, 'vendor_id' => $vendor->id, 'po_number' => 'PO-2025-001'],
            [
                'po_date' => Carbon::parse('2025-01-10'),
                'created_by_user_id' => 1,
                'expected_delivery_date' => Carbon::parse('2025-01-25'),
                'status' => PurchaseOrderStatus::Draft,
                'currency_id' => $usdCurrency->id,
                'exchange_rate_at_creation' => 1310,
                'total_amount' => Money::of(0, $usdCurrency->code), // Observer will update
                'total_tax' => Money::of(0, $usdCurrency->code),
            ]
        );

        if ($purchaseOrder->wasRecentlyCreated) {
            // 1) GPU-RTX4090: 10 units @ $1,900 USD
            $createLineAction->execute(
                $purchaseOrder,
                new CreatePurchaseOrderLineDTO(
                    product_id: $gpuProduct->id,
                    description: $gpuProduct->name,
                    quantity: 10,
                    unit_price: Money::of('1900', $usdCurrency->code),
                    tax_id: $tax->id
                )
            );

            // 2) RAM-DDR5-32GB: 50 units @ $305 USD
            $createLineAction->execute(
                $purchaseOrder,
                new CreatePurchaseOrderLineDTO(
                    product_id: $ramProduct->id,
                    description: $ramProduct->name,
                    quantity: 50,
                    unit_price: Money::of('305', $usdCurrency->code),
                    tax_id: $tax->id
                )
            );

            // 3) SSD-2TB-NVME: 30 units @ $229 USD
            $createLineAction->execute(
                $purchaseOrder,
                new CreatePurchaseOrderLineDTO(
                    product_id: $ssdProduct->id,
                    description: $ssdProduct->name,
                    quantity: 30,
                    unit_price: Money::of('229', $usdCurrency->code),
                    tax_id: $tax->id
                )
            );
        }
    }
}
