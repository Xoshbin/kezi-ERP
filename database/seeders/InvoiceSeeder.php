<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use App\Models\InvoiceLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'Jmeryar Solutions')->firstOrFail();
        $adminUser = User::firstOrFail();
        $partners = Partner::factory()->count(3)->create([
            'company_id' => $company->id,
        ]);

        if ($partners->count() < 3) {
            throw new \Exception('Not enough partners found to seed invoices.');
        }

        // Seed specific invoice for "Al-Mansour Trading Co."
        $alMansourPartner = Partner::first();

        $invoiceDate = Date::now();
        $dueDate = Date::now()->addDays(15);

        $invoice = Invoice::updateOrCreate(
            [
                'company_id' => $company->id,
                'customer_id' => $alMansourPartner->id
            ],
            [
                'currency_id' => $company->currency_id,
                'invoice_date' => $invoiceDate,
                'total_tax' => 0,
                'due_date' => $dueDate,
                'total_amount' => 5000000,
                'status' => Invoice::STATUS_DRAFT, // Set to posted as per story
            ]
        );

        // Create the specific invoice line for Al-Mansour Trading Co.
        $consultingRevenueAccount = Account::where('name', 'Consulting Revenue')->first();

        InvoiceLine::updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'description' => 'On-site IT Infrastructure Setup',
            ],
            [
                'quantity' => 1,
                'unit_price' => 5000000,
                'income_account_id' => $consultingRevenueAccount->id
            ]
        );

        /*
        // Existing seeding logic for other partners (optional, keep if needed)
        foreach ($partners as $index => $partner) {
            // Skip Al-Mansour Trading Co. if it's already seeded specifically
            if ($partner->name === 'Al-Mansour Trading Co.') {
                continue;
            }

            Invoice::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'customer_id' => $partner->id,
                    'reference' => 'INV-2025-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                ],
                [
                    'user_id' => $adminUser->id,
                    'invoice_date' => Date::now(),
                    'due_date' => Date::now()->addDays(30),
                    'status' => Invoice::TYPE_DRAFT,
                    'notes' => 'Sample invoice for testing',
                ]
            );
        }
        */
    }
}
