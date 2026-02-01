<?php

namespace Jmeryar\Sales\Database\Seeders;

use App\Models\Company;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Product\Models\Product;
use Jmeryar\Sales\Actions\Sales\CreateInvoiceLineAction;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $currencyCode = $company->currency->code;

        // --- Fetch Products by SKU ---
        $consultingProduct = Product::where('sku', 'CONS-001')->firstOrFail();
        $routerProduct = Product::where('sku', 'PROD-ROUTER-01')->firstOrFail();
        $cableProduct = Product::where('sku', 'PROD-CABLE-01')->firstOrFail();
        $switchProduct = Product::where('sku', 'PROD-SWITCH-01')->firstOrFail();

        // --- Fetch Partners ---
        $hawrePartner = Partner::firstOrCreate(['name' => 'Hawre Trading Group', 'company_id' => $company->id], ['type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Customer]);
        $zryanPartner = Partner::firstOrCreate(['name' => 'Zryan Tech Store', 'company_id' => $company->id], ['type' => \Jmeryar\Foundation\Enums\Partners\PartnerType::Customer]);

        $createLineAction = resolve(CreateInvoiceLineAction::class);

        // === INVOICE 1: Service Invoice ===
        $invoice1 = Invoice::updateOrCreate(
            ['company_id' => $company->id, 'invoice_number' => 'INV-001'],
            [
                'customer_id' => $hawrePartner->id,
                'currency_id' => $company->currency_id,
                'invoice_date' => now()->subDays(20),
                'due_date' => now()->subDays(5),
                'status' => InvoiceStatus::Draft->value,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );
        if ($invoice1->wasRecentlyCreated) {
            $createLineAction->execute($invoice1, new CreateInvoiceLineDTO(description: $consultingProduct->name, quantity: 35, unit_price: $consultingProduct->unit_price, income_account_id: $consultingProduct->income_account_id, product_id: $consultingProduct->id, tax_id: null));
        }

        // === INVOICE 2: Product Invoice ===
        $invoice2 = Invoice::updateOrCreate(
            ['company_id' => $company->id, 'invoice_number' => 'INV-002'],
            [
                'customer_id' => $hawrePartner->id,
                'currency_id' => $company->currency_id,
                'invoice_date' => now()->subDays(18),
                'due_date' => now()->subDays(3),
                'status' => InvoiceStatus::Draft->value,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );
        if ($invoice2->wasRecentlyCreated) {
            $createLineAction->execute($invoice2, new CreateInvoiceLineDTO(description: $routerProduct->name, quantity: 2, unit_price: $routerProduct->unit_price, income_account_id: $routerProduct->income_account_id, product_id: $routerProduct->id, tax_id: null));
            $createLineAction->execute($invoice2, new CreateInvoiceLineDTO(description: $cableProduct->name, quantity: 5, unit_price: $cableProduct->unit_price, income_account_id: $cableProduct->income_account_id, product_id: $cableProduct->id, tax_id: null));
        }

        // === INVOICE 3: Product Invoice ===
        $invoice3 = Invoice::updateOrCreate(
            ['company_id' => $company->id, 'invoice_number' => 'INV-003'],
            [
                'customer_id' => $zryanPartner->id,
                'currency_id' => $company->currency_id,
                'invoice_date' => now()->subDays(15),
                'due_date' => now()->addDays(15),
                'status' => InvoiceStatus::Draft->value,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );
        if ($invoice3->wasRecentlyCreated) {
            $createLineAction->execute($invoice3, new CreateInvoiceLineDTO(description: $switchProduct->name, quantity: 1, unit_price: $switchProduct->unit_price, income_account_id: $switchProduct->income_account_id, product_id: $switchProduct->id, tax_id: null));
        }
    }
}
