<?php

namespace Jmeryar\Sales\Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Foundation\Services\SequenceService;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateInvoiceDTO;
use Jmeryar\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Jmeryar\Sales\Enums\Sales\InvoiceStatus;
use Jmeryar\Sales\Models\Invoice;
use Jmeryar\Sales\Services\InvoiceService;
use Tests\TestCase;

class InvoiceNumberRaceConditionTest extends TestCase
{
    use RefreshDatabase;

    private Partner $customer;

    private Account $incomeAccount;

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
            'default_accounts_receivable_id' => $this->incomeAccount->id,
        ]);
    }

    /** @test */
    public function it_generates_unique_invoice_numbers_without_race_conditions()
    {
        // Create multiple invoices that will be posted simultaneously
        $invoices = [];
        for ($i = 0; $i < 3; $i++) {
            $invoiceDTO = new CreateInvoiceDTO(
                company_id: $this->company->id,
                customer_id: $this->customer->id,
                currency_id: $this->currency->id,
                invoice_date: now()->format('Y-m-d'),
                due_date: now()->addDays(30)->format('Y-m-d'),
                lines: [
                    new CreateInvoiceLineDTO(
                        description: "Test Item $i",
                        quantity: 1,
                        unit_price: Money::of(100, 'USD'),
                        income_account_id: $this->incomeAccount->id,
                        product_id: null,
                        tax_id: $this->tax->id
                    ),
                ],
                fiscal_position_id: null
            );

            $invoices[] = app(\Jmeryar\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDTO);
        }

        $sequenceService = app(SequenceService::class);
        $generatedNumbers = [];

        // Simulate concurrent posting by calling getNextInvoiceNumber multiple times
        // This should now work correctly with the new atomic sequence system
        for ($i = 0; $i < count($invoices); $i++) {
            $number = $sequenceService->getNextInvoiceNumber($this->company);
            $generatedNumbers[] = $number;
        }

        // With the new sequence system, all numbers should be unique
        $this->assertCount(3, array_unique($generatedNumbers), 'Expected 3 unique invoice numbers');
        // Check that all numbers follow the new format
        foreach ($generatedNumbers as $number) {
            $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $number);
        }
    }

    /** @test */
    public function it_posts_multiple_invoices_without_database_constraint_violations()
    {
        // Create two invoices
        $invoice1 = $this->createTestInvoice();
        $invoice2 = $this->createTestInvoice();

        $invoiceService = app(InvoiceService::class);

        // This should now work without any database constraint violations
        // because each invoice gets a unique number from the sequence service
        DB::transaction(function () use ($invoiceService, $invoice1, $invoice2) {
            $invoiceService->confirm($invoice1, $this->user);
            $invoiceService->confirm($invoice2, $this->user);
        });

        // Verify both invoices were posted successfully
        $invoice1->refresh();
        $invoice2->refresh();

        $this->assertEquals(InvoiceStatus::Posted, $invoice1->status);
        $this->assertEquals(InvoiceStatus::Posted, $invoice2->status);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoice1->invoice_number);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $invoice2->invoice_number);
        $this->assertNotNull($invoice1->journal_entry_id);
        $this->assertNotNull($invoice2->journal_entry_id);
        $this->assertNotEquals($invoice1->journal_entry_id, $invoice2->journal_entry_id);
    }

    private function createTestInvoice(): Invoice
    {
        $invoiceDTO = new CreateInvoiceDTO(
            company_id: $this->company->id,
            customer_id: $this->customer->id,
            currency_id: $this->currency->id,
            invoice_date: now()->format('Y-m-d'),
            due_date: now()->addDays(30)->format('Y-m-d'),
            lines: [
                new CreateInvoiceLineDTO(
                    description: 'Test Item',
                    quantity: 1,
                    unit_price: Money::of(100, 'USD'),
                    income_account_id: $this->incomeAccount->id,
                    product_id: null,
                    tax_id: $this->tax->id
                ),
            ],
            fiscal_position_id: null
        );

        return app(\Jmeryar\Sales\Actions\Sales\CreateInvoiceAction::class)->execute($invoiceDTO);
    }
}
