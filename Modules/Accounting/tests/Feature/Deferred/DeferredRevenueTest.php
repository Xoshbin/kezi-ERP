<?php

namespace Modules\Accounting\Tests\Feature\Deferred;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\DeferredItem;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Services\DeferredItemService;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceLine;
use Modules\Sales\Services\InvoiceService;
use Tests\TestCase;

class DeferredRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_deferred_revenue_item_when_invoice_is_confirmed_with_deferral_dates()
    {
        $user = \App\Models\User::factory()->create();
        $company = \App\Models\Company::factory()->create();
        $currency = \Modules\Foundation\Models\Currency::factory()->create();
        $company->currency_id = $currency->id;
        $company->save();

        // Create Accounts
        $incomeAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'income']);
        $deferredAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'current_liabilities']);
        $receivableAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'receivable']);
        $taxAccount = Account::factory()->create(['company_id' => $company->id, 'type' => 'current_liabilities']);
        $journal = Journal::factory()->create(['company_id' => $company->id, 'type' => 'sale']);
        Journal::factory()->create(['company_id' => $company->id, 'type' => 'miscellaneous']);

        $company->default_accounts_receivable_id = $receivableAccount->id;
        $company->default_sales_journal_id = $journal->id;
        $company->default_tax_account_id = $taxAccount->id;
        $company->save();

        // Create Product with Income Account
        $product = Product::factory()->create([
            'company_id' => $company->id,
            'income_account_id' => $incomeAccount->id,
            'deferred_revenue_account_id' => $deferredAccount->id,
        ]);

        // Create Invoice with Line having Deferred Account (as income_account_id) and Dates
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'currency_id' => $currency->id,
            'status' => \Modules\Sales\Enums\Sales\InvoiceStatus::Draft,
        ]);

        $line = InvoiceLine::factory()->create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'income_account_id' => $deferredAccount->id, // Use Deferred Account here
            'deferred_start_date' => now(),
            'deferred_end_date' => now()->addMonths(11), // 1 Year
            'quantity' => 1,
            'unit_price' => 1200,
            'subtotal' => 1200,
            'subtotal_company_currency' => 1200, // Assuming 1:1
        ]);

        // Act: Confirm Invoice
        app(InvoiceService::class)->confirm($invoice, $user);

        // Assert: Deferred Item Created
        $deferredItem = DeferredItem::where('source_id', $line->id)->first();

        $this->assertNotNull($deferredItem);
        $this->assertEquals('revenue', $deferredItem->type);
        $this->assertTrue($deferredItem->original_amount->isEqualTo(1200));
        $this->assertEquals($deferredAccount->id, $deferredItem->deferred_account_id);
        $this->assertEquals($incomeAccount->id, $deferredItem->recognition_account_id);

        // Assert: Schedule Lines Created
        $this->assertCount(12, $deferredItem->lines);

        // Test Processing
        $service = app(DeferredItemService::class);
        // Process due entries (should be 1 for current month if start date is now)
        $this->travelTo(now()->endOfMonth());
        $processed = $service->processDueEntries();

        $this->assertGreaterThanOrEqual(1, $processed);

        $line = $deferredItem->lines()->first();
        $this->assertEquals('posted', $line->status);
        $this->assertNotNull($line->journal_entry_id);
    }
}
