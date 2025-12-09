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
                \Modules\Foundation\Database\Seeders\CurrencySeeder::class,
                CompanySeeder::class,
                \Modules\Foundation\Database\Seeders\CurrencyRateSeeder::class,
                \Modules\Accounting\Database\Seeders\AccountSeeder::class,
                \Modules\Accounting\Database\Seeders\JournalSeeder::class,
                UpdateCompanyDefaultsSeeder::class,
                UserSeeder::class,

                // 2. Payment terms
                // Common payment terms for all companies
                \Modules\Foundation\Database\Seeders\PaymentTermsSeeder::class,

                // 3. Operational data
                // Basic data for transactions (customers, vendors, products).
                // \Modules\Foundation\Database\Seeders\PartnerSeeder::class,
                // \Modules\Foundation\Database\Seeders\PartnerCustomFieldSeeder::class,
                \Modules\Accounting\Database\Seeders\TaxSeeder::class,
                // \Modules\Product\Database\Seeders\ProductSeeder::class,

                // 4. Fiscal positions and mappings
                // Rules for applying taxes and mapping accounts based on partner location.
                \Modules\Accounting\Database\Seeders\FiscalPositionSeeder::class,
                // \Modules\Accounting\Database\Seeders\FiscalPositionTaxMappingSeeder::class,
                // \Modules\Accounting\Database\Seeders\FiscalPositionAccountMappingSeeder::class,

                // 5. Analytic accounting
                // For cost accounting and tracking profitability.
                \Modules\Accounting\Database\Seeders\AnalyticPlanSeeder::class,
                // \Modules\Accounting\Database\Seeders\AnalyticAccountSeeder::class,
                // \Modules\Accounting\Database\Seeders\AnalyticAccountPlanPivotSeeder::class,

                // 6. Assets and budgets
                // For tracking fixed assets and financial planning.
                // \Modules\Accounting\Database\Seeders\AssetSeeder::class,
                // \Modules\Accounting\Database\Seeders\DepreciationEntrySeeder::class,
                // \Modules\Accounting\Database\Seeders\BudgetSeeder::class,
                // \Modules\Accounting\Database\Seeders\BudgetLineSeeder::class,

                // 7. Financial documents
                // The primary transactional records.
                // \Modules\Accounting\Database\Seeders\JournalEntrySeeder::class,
                // \Modules\Purchase\Database\Seeders\PurchaseOrderSeeder::class,
                // \Modules\Purchase\Database\Seeders\VendorBillSeeder::class,
                // \Modules\Sales\Database\Seeders\InvoiceSeeder::class,
                // InvoiceLineSeeder::class, // Removed as logic is now in InvoiceSeeder
                // PaymentSeeder::class,
                // PaymentDocumentLinkSeeder::class,

                // 8. Adjustments and statements
                // For reconciliations and manual adjustments.
                // \Modules\Accounting\Database\Seeders\AdjustmentDocumentSeeder::class,
                // \Modules\Accounting\Database\Seeders\BankStatementSeeder::class,
                // \Modules\Accounting\Database\Seeders\BankStatementLineSeeder::class,

                // 9. Finally
                // Lock dates to prevent changes to closed periods.
                // \Modules\Accounting\Database\Seeders\LockDateSeeder::class,

                // Note: PostTransactionsSeeder is available for manual execution
                // Run: php artisan db:seed --class=PostTransactionsSeeder
                // This posts some transactions to demonstrate Partner Ledger functionality
            ]);
        });
    }
}
