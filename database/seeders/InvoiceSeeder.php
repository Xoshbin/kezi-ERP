<?php

namespace Database\Seeders;

use App\Actions\Sales\CreateInvoiceLineAction; // <-- Import the Action
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO; // <-- Import the DTO
use App\Enums\Sales\InvoiceStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use Brick\Money\Money; // <-- Import Money
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $currencyCode = $company->currency->code;

        $hawrePartner = Partner::where('name', 'Hawre Trading Group')->first();
        if (!$hawrePartner) {
            // Create if it doesn't exist to make seeder more robust
            $hawrePartner = Partner::factory()->create(['name' => 'Hawre Trading Group', 'company_id' => $company->id]);
        }

        // 1. Create the parent Invoice
        $invoice = Invoice::updateOrCreate(
            [
                'company_id' => $company->id,
                'customer_id' => $hawrePartner->id
            ],
            [
                'currency_id' => $company->currency_id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(15),
                'status' => InvoiceStatus::Draft->value,
                'total_amount' => Money::of(0, $currencyCode), // Will be updated by observer
                'total_tax' => Money::of(0, $currencyCode),
            ]
        );

        // 2. Prepare the DTO for the line item
        $consultingRevenueAccount = Account::where('name->en', 'Consulting Revenue')->firstOrFail();

        $lineDto = new CreateInvoiceLineDTO(
            description: 'On-site IT Infrastructure Setup',
            quantity: 1,
            unit_price: Money::of('5000000', $currencyCode), // DTOs expect Money objects
            income_account_id: $consultingRevenueAccount->id,
            product_id: null,
            tax_id: null
        );

        // 3. Resolve the Action from the container and execute it
        $createLineAction = resolve(CreateInvoiceLineAction::class);
        $createLineAction->execute($invoice, $lineDto);
    }
}
