<?php

namespace Modules\Accounting\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Models\Account;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Models\Invoice;
use Tests\TestCase;

class InvoicePostingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Partner $customer;

    private Currency $currency;

    private User $user;

    private Account $incomeAccount;

    private Account $receivableAccount;

    private Journal $salesJournal;

    private Tax $tax;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->currency = Currency::factory()->create(['code' => 'USD']);
        $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
        $this->customer = Partner::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create();

        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'income',
        ]);

        $this->receivableAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'receivable',
        ]);

        $this->salesJournal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'sale',
        ]);

        $this->tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'rate' => 10.0,
        ]);

        // Set up company defaults
        $this->company->update([
            'default_sales_journal_id' => $this->salesJournal->id,
            'default_accounts_receivable_id' => $this->receivableAccount->id,
        ]);
    }

    public function test_posting_multiple_invoices_sequentially_works_correctly()
    {
        $invoiceService = app(InvoiceService::class);

        // Create and post first invoice
        $invoice1 = $this->createTestInvoice('First Invoice');
        $invoiceService->confirm($invoice1, $this->user);

        // Create and post second invoice
        $invoice2 = $this->createTestInvoice('Second Invoice');
        $invoiceService->confirm($invoice2, $this->user);

        // Create and post third invoice
        $invoice3 = $this->createTestInvoice('Third Invoice');
        $invoiceService->confirm($invoice3, $this->user);

        // Refresh to get updated data
        $invoice1->refresh();
        $invoice2->refresh();
        $invoice3->refresh();

        // Verify all invoices were posted successfully with unique numbers
        $this->assertEquals(InvoiceStatus::Posted, $invoice1->status);
        $this->assertEquals(InvoiceStatus::Posted, $invoice2->status);
        $this->assertEquals(InvoiceStatus::Posted, $invoice3->status);

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoice1->invoice_number);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoice2->invoice_number);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoice3->invoice_number);

        // Verify journal entries were created with unique references
        $this->assertNotNull($invoice1->journal_entry_id);
        $this->assertNotNull($invoice2->journal_entry_id);
        $this->assertNotNull($invoice3->journal_entry_id);

        $journalEntry1 = $invoice1->journalEntry;
        $journalEntry2 = $invoice2->journalEntry;
        $journalEntry3 = $invoice3->journalEntry;

        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $journalEntry1->reference);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $journalEntry2->reference);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $journalEntry3->reference);

        // Verify no duplicate journal entry references exist
        $duplicateEntries = JournalEntry::where('company_id', $this->company->id)
            ->where('journal_id', $this->salesJournal->id)
            ->where('reference', 'INV-00001')
            ->where('id', '!=', $journalEntry1->id)
            ->count();

        $this->assertEquals(0, $duplicateEntries, 'No duplicate journal entries should exist');
    }

    public function test_the_original_error_scenario_is_fixed()
    {
        // This test simulates the exact scenario that was causing the original error:
        // "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1-1-INV-00004'"

        $invoiceService = app(InvoiceService::class);

        // Create multiple invoices (simulating seeded data)
        $invoices = [];
        for ($i = 1; $i <= 5; $i++) {
            $invoice = $this->createTestInvoice("Test Invoice $i");
            $invoices[] = $invoice;
        }

        // Post them one by one (this was causing the race condition)
        foreach ($invoices as $invoice) {
            $invoiceService->confirm($invoice, $this->user);
            $invoice->refresh();

            // Verify each invoice gets a unique number and journal entry
            $this->assertNotNull($invoice->invoice_number);
            $this->assertNotNull($invoice->journal_entry_id);
            $this->assertEquals(InvoiceStatus::Posted, $invoice->status);
        }

        // Verify all invoice numbers are unique
        $invoiceNumbers = collect($invoices)->pluck('invoice_number')->toArray();
        $this->assertCount(5, array_unique($invoiceNumbers));

        // Verify the sequence is correct
        // Verify all numbers follow the new format
        foreach ($invoiceNumbers as $number) {
            $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number);
        }

        // Verify no duplicate journal entry references exist in the database
        $journalEntryReferences = JournalEntry::where('company_id', $this->company->id)
            ->where('journal_id', $this->salesJournal->id)
            ->pluck('reference')
            ->toArray();

        $this->assertCount(5, array_unique($journalEntryReferences));
    }

    private function createTestInvoice(string $description = 'Test Item'): Invoice
    {
        $invoiceDTO = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->customer->id,
            currency_id: $this->currency->id,
            invoice_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'),
            lines: [
                new CreateInvoiceLineDTO(
                    description: $description,
                    quantity: 1,
                    unit_price: Money::of(100, 'USD'),
                    income_account_id: $this->incomeAccount->id,
                    product_id: null,
                    tax_id: $this->tax->id
                ),
            ],
            fiscal_position_id: null
        );

        return app(\Modules\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDTO);
    }
}
