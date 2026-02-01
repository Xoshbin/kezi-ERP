<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->call([
                // 1. Core entities
                // These are the fundamental records required for the system to operate.
                \Kezi\Foundation\Database\Seeders\CurrencySeeder::class,
                CompanySeeder::class,
                \Kezi\Foundation\Database\Seeders\CurrencyRateSeeder::class,
                \Kezi\Accounting\Database\Seeders\AccountGroupSeeder::class,
                \Kezi\Accounting\Database\Seeders\AccountSeeder::class,
                \Kezi\Accounting\Database\Seeders\JournalSeeder::class,
                \Kezi\Accounting\Database\Seeders\UpdateCompanyDefaultsSeeder::class,
                UserSeeder::class,
                \Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class,

                // 2. Payment terms
                // Common payment terms for all companies
                \Kezi\Foundation\Database\Seeders\PaymentTermsSeeder::class,

                // 3. Operational data
                // Basic data for transactions (customers, vendors, products).
                \Kezi\Foundation\Database\Seeders\PartnerSeeder::class,
                \Kezi\Foundation\Database\Seeders\PartnerCustomFieldSeeder::class,
                \Kezi\Accounting\Database\Seeders\WithholdingTaxTypeSeeder::class,
                \Kezi\Accounting\Database\Seeders\TaxSeeder::class,
                \Kezi\Product\Database\Seeders\ProductSeeder::class,

                // 4. Fiscal positions and mappings
                // Rules for applying taxes and mapping accounts based on partner location.
                \Kezi\Accounting\Database\Seeders\FiscalPositionSeeder::class,
                // \Kezi\Accounting\Database\Seeders\FiscalPositionTaxMappingSeeder::class,
                // \Kezi\Accounting\Database\Seeders\FiscalPositionAccountMappingSeeder::class,

                // 5. Analytic accounting
                // For cost accounting and tracking profitability.
                \Kezi\Accounting\Database\Seeders\AnalyticPlanSeeder::class,
                // \Kezi\Accounting\Database\Seeders\AnalyticAccountSeeder::class,
                // \Kezi\Accounting\Database\Seeders\AnalyticAccountPlanPivotSeeder::class,

                // 6. Assets and budgets
                // For tracking fixed assets and financial planning.
                // \Kezi\Accounting\Database\Seeders\AssetSeeder::class,
                // \Kezi\Accounting\Database\Seeders\DepreciationEntrySeeder::class,
                // \Kezi\Accounting\Database\Seeders\BudgetSeeder::class,
                // \Kezi\Accounting\Database\Seeders\BudgetLineSeeder::class,

                // 7. Financial documents
                // The primary transactional records.
                // \Kezi\Accounting\Database\Seeders\JournalEntrySeeder::class,
                // \Kezi\Purchase\Database\Seeders\PurchaseOrderSeeder::class,
                // \Kezi\Purchase\Database\Seeders\VendorBillSeeder::class,
                // \Kezi\Sales\Database\Seeders\InvoiceSeeder::class,
                // InvoiceLineSeeder::class, // Removed as logic is now in InvoiceSeeder
                // PaymentSeeder::class,
                // PaymentDocumentLinkSeeder::class,

                // 8. Adjustments and statements
                // For reconciliations and manual adjustments.
                // \Kezi\Accounting\Database\Seeders\AdjustmentDocumentSeeder::class,
                // \Kezi\Accounting\Database\Seeders\BankStatementSeeder::class,
                // \Kezi\Accounting\Database\Seeders\BankStatementLineSeeder::class,

                // 9. Finally
                // Lock dates to prevent changes to closed periods.
                // \Kezi\Accounting\Database\Seeders\LockDateSeeder::class,

                // Note: PostTransactionsSeeder is available for manual execution
                // Run: php artisan db:seed --class="\Kezi\Accounting\Database\Seeders\PostTransactionsSeeder"
                // This posts some transactions to demonstrate Partner Ledger functionality
            ]);
        });
    }
}
