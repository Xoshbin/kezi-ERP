<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
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
        $adminUser = User::where('name', 'Admin User')->firstOrFail();
        $partners = Partner::take(3)->get();

        if ($partners->count() < 3) {
            throw new \Exception('Not enough partners found to seed invoices.');
        }

        foreach ($partners as $index => $partner) {
            Invoice::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'partner_id' => $partner->id,
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
    }
}
