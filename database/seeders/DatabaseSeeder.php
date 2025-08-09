<?php

namespace Database\Seeders;

use App\Models\Invoice;
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
                CurrencySeeder::class,
                CompanySeeder::class,
                AccountSeeder::class,
                JournalSeeder::class,
                UpdateCompanyDefaultsSeeder::class,
                UserSeeder::class,
                // JournalEntrySeeder::class,

                // 3. Operational data
                // Basic data for transactions (customers, vendors, products).
                // PartnerSeeder::class,
                // TaxSeeder::class,
                // ProductSeeder::class,

                // 4. Fiscal positions and mappings
                // Rules for applying taxes and mapping accounts based on partner location.
                // FiscalPositionSeeder::class,
                // FiscalPositionTaxMappingSeeder::class,
                // FiscalPositionAccountMappingSeeder::class,

                // 5. Analytic accounting
                // For cost accounting and tracking profitability.
                // AnalyticPlanSeeder::class,
                // AnalyticAccountSeeder::class,
                // AnalyticAccountPlanPivotSeeder::class,

                // 6. Assets and budgets
                // For tracking fixed assets and financial planning.
                // AssetSeeder::class,
                // DepreciationEntrySeeder::class,
                // BudgetSeeder::class,
                // BudgetLineSeeder::class,

                // 7. Financial documents
                // The primary transactional records.
                //  VendorBillSeeder::class,
                //  InvoiceSeeder::class,
                // InvoiceLineSeeder::class, // Removed as logic is now in InvoiceSeeder
                // PaymentSeeder::class,
                // PaymentDocumentLinkSeeder::class,

                // 8. Adjustments and statements
                // For reconciliations and manual adjustments.
                // AdjustmentDocumentSeeder::class,
                // BankStatementSeeder::class,
                // BankStatementLineSeeder::class,

                // 9. Finally
                // Lock dates to prevent changes to closed periods.
                // LockDateSeeder::class,
            ]);
        });
    }
}
