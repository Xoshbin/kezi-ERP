<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnboardingWizardSeeder extends Seeder
{
    /**
     * Run the database seeds for a fresh installation.
     * These are essential, company-agnostic records required for the onboarding wizard.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->call([
                // Currencies for the wizard's currency dropdown
                \Kezi\Foundation\Database\Seeders\CurrencySeeder::class,

                // Permissions for RBAC (roles are created per-company during onboarding)
                \Kezi\Foundation\Database\Seeders\PermissionSeeder::class,

                // Generic payment terms used by all companies
                \Kezi\Foundation\Database\Seeders\PaymentTermsSeeder::class,

                // Generic withholding tax types
                \Kezi\Accounting\Database\Seeders\WithholdingTaxTypeSeeder::class,
            ]);
        });
    }
}
