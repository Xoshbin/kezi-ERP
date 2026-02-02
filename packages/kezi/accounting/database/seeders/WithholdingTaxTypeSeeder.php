<?php

namespace Kezi\Accounting\Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Kezi\Accounting\Models\WithholdingTaxType;

class WithholdingTaxTypeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Find WHT Payable account
            $whtAccount = \Kezi\Accounting\Models\Account::where('company_id', $company->id)
                ->where('code', '220150')
                ->first();

            // Fallback if not found (should be seeded by AccountSeeder if run before)
            if (! $whtAccount) {
                // Try finding by name or create one? Better to fail or skip?
                // Or find any liability account.
                $whtAccount = \Kezi\Accounting\Models\Account::where('company_id', $company->id)
                    ->where('type', \Kezi\Accounting\Enums\Accounting\AccountType::CurrentLiabilities)
                    ->first();
            }

            if (! $whtAccount) {
                continue; // Cannot seed WHT types without account
            }

            $types = [
                [
                    'name' => ['en' => 'Services (5%)', 'ku' => 'خزمەتگوزاری (٥٪)'],
                    'rate' => 0.05,
                    'applicable_to' => 'services',
                    'threshold_amount' => 0,
                    'is_active' => true,
                ],
                [
                    'name' => ['en' => 'Legal & Consulting (10%)', 'ku' => 'یاسایی و ڕاوێژکاری (١٠٪)'],
                    'rate' => 0.10,
                    'applicable_to' => 'services',
                    'threshold_amount' => 0,
                    'is_active' => true,
                ],
                [
                    'name' => ['en' => 'Rent (10%)', 'ku' => 'کرێ (١٠٪)'],
                    'rate' => 0.10,
                    'applicable_to' => 'services', // Rent is treated as a service for applicability categorization
                    'threshold_amount' => 0,
                    'is_active' => true,
                ],
                [
                    'name' => ['en' => 'Goods (0%)', 'ku' => 'کەلوپەل (٠٪)'],
                    'rate' => 0.00,
                    'applicable_to' => 'goods',
                    'threshold_amount' => 0,
                    'is_active' => true, // Exempt types are often needed to explicitly show no tax
                ],
            ];

            foreach ($types as $type) {
                WithholdingTaxType::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'name->en' => $type['name']['en'], // Check by English name to avoid dupes
                    ],
                    [
                        'name' => $type['name'],
                        'rate' => $type['rate'],
                        'applicable_to' => $type['applicable_to'],
                        'threshold_amount' => $type['threshold_amount'],
                        'withholding_account_id' => $whtAccount->id,
                        'is_active' => $type['is_active'],
                    ]
                );
            }
        }
    }
}
