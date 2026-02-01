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
                \Jmeryar\Foundation\Database\Seeders\CurrencySeeder::class,
                CompanySeeder::class,
                \Jmeryar\Foundation\Database\Seeders\CurrencyRateSeeder::class,
                \Jmeryar\Accounting\Database\Seeders\AccountGroupSeeder::class,
                \Jmeryar\Accounting\Database\Seeders\AccountSeeder::class,
                \Jmeryar\Accounting\Database\Seeders\JournalSeeder::class,
                UpdateCompanyDefaultsSeeder::class,
                UserSeeder::class,
                \Database\Seeders\RolesAndPermissionsSeeder::class,

                // 2. Payment terms
                // Common payment terms for all companies
                \Jmeryar\Foundation\Database\Seeders\PaymentTermsSeeder::class,

                // 3. Operational data
                // Basic data for transactions (customers, vendors, products).
                \Jmeryar\Foundation\Database\Seeders\PartnerSeeder::class,
                \Jmeryar\Foundation\Database\Seeders\PartnerCustomFieldSeeder::class,
                \Jmeryar\Accounting\Database\Seeders\WithholdingTaxTypeSeeder::class,
                \Jmeryar\Accounting\Database\Seeders\TaxSeeder::class,
                \Jmeryar\Product\Database\Seeders\ProductSeeder::class,

                // 4. Fiscal positions and mappings
                // Rules for applying taxes and mapping accounts based on partner location.
                \Jmeryar\Accounting\Database\Seeders\FiscalPositionSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\FiscalPositionTaxMappingSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\FiscalPositionAccountMappingSeeder::class,

                // 5. Analytic accounting
                // For cost accounting and tracking profitability.
                \Jmeryar\Accounting\Database\Seeders\AnalyticPlanSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\AnalyticAccountSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\AnalyticAccountPlanPivotSeeder::class,

                // 6. Assets and budgets
                // For tracking fixed assets and financial planning.
                // \Jmeryar\Accounting\Database\Seeders\AssetSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\DepreciationEntrySeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\BudgetSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\BudgetLineSeeder::class,

                // 7. Financial documents
                // The primary transactional records.
                // \Jmeryar\Accounting\Database\Seeders\JournalEntrySeeder::class,
                // \Jmeryar\Purchase\Database\Seeders\PurchaseOrderSeeder::class,
                // \Jmeryar\Purchase\Database\Seeders\VendorBillSeeder::class,
                // \Jmeryar\Sales\Database\Seeders\InvoiceSeeder::class,
                // InvoiceLineSeeder::class, // Removed as logic is now in InvoiceSeeder
                // PaymentSeeder::class,
                // PaymentDocumentLinkSeeder::class,

                // 8. Adjustments and statements
                // For reconciliations and manual adjustments.
                // \Jmeryar\Accounting\Database\Seeders\AdjustmentDocumentSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\BankStatementSeeder::class,
                // \Jmeryar\Accounting\Database\Seeders\BankStatementLineSeeder::class,

                // 9. Finally
                // Lock dates to prevent changes to closed periods.
                // \Jmeryar\Accounting\Database\Seeders\LockDateSeeder::class,

                // Note: PostTransactionsSeeder is available for manual execution
                // Run: php artisan db:seed --class=PostTransactionsSeeder
                // This posts some transactions to demonstrate Partner Ledger functionality
            ]);
        });
    }
}
