<?php

namespace Modules\Accounting\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Modules\Accounting\Models\JournalEntry;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\TestCase;

class WebInterfaceInvoicePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up the test environment
        $this->seed();

        // Set up tenant context for Filament
        $user = User::first();
        $company = $user->companies()->first();
        $this->actingAs($user);
        Filament::setTenant($company);

        // Create test data: 3 draft invoices with invoice lines for testing
        Invoice::factory()->count(3)->withLines()->create([
            'status' => InvoiceStatus::Draft,
            'company_id' => $company->id,
        ]);
    }

    public function test_posting_multiple_invoices_via_web_interface_works_correctly()
    {

        // Get some draft invoices from the seeded data
        $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

        $this->assertGreaterThanOrEqual(3, $draftInvoices->count(), 'Need at least 3 draft invoices from seeder');

        $postedInvoiceNumbers = [];
        $journalEntryReferences = [];

        // Post each invoice one by one (simulating the web interface scenario)
        foreach ($draftInvoices as $invoice) {
            // Simulate the web interface posting action using Livewire
            Livewire::test(EditInvoice::class, [
                'record' => $invoice->getRouteKey(),
            ])
                ->callAction('confirm')
                ->assertHasNoErrors();

            // Refresh the invoice to get updated data
            $invoice->refresh();

            // Verify the invoice was posted successfully
            $this->assertEquals(InvoiceStatus::Posted, $invoice->status);
            $this->assertNotNull($invoice->invoice_number);
            $this->assertNotNull($invoice->journal_entry_id);
            $this->assertNotNull($invoice->posted_at);

            $postedInvoiceNumbers[] = $invoice->invoice_number;

            // Get the journal entry and verify it has the correct reference
            $journalEntry = JournalEntry::find($invoice->journal_entry_id);
            $this->assertNotNull($journalEntry);
            $this->assertEquals($invoice->invoice_number, $journalEntry->reference);

            $journalEntryReferences[] = $journalEntry->reference;
        }

        // Verify all invoice numbers are unique
        $this->assertCount(
            count($postedInvoiceNumbers),
            array_unique($postedInvoiceNumbers),
            'All invoice numbers should be unique'
        );

        // Verify all journal entry references are unique
        $this->assertCount(
            count($journalEntryReferences),
            array_unique($journalEntryReferences),
            'All journal entry references should be unique'
        );

        // Verify the invoice numbers follow the new format
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $postedInvoiceNumbers[0]);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $postedInvoiceNumbers[1]);
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{7}$/', $postedInvoiceNumbers[2]);

        // Extract the numeric parts from the new format and verify they're sequential
        $numbers = array_map(function ($invoiceNumber) {
            // Extract the last part after the last slash (e.g., "0000001" from "INV/2025/08/0000001")
            return (int) substr($invoiceNumber, strrpos($invoiceNumber, '/') + 1);
        }, $postedInvoiceNumbers);

        sort($numbers);
        $this->assertEquals(1, $numbers[0]);
        $this->assertEquals(2, $numbers[1]);
        $this->assertEquals(3, $numbers[2]);
    }

    public function test_no_duplicate_journal_entry_constraint_violations_occur()
    {

        // Get multiple draft invoices
        $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

        $this->assertGreaterThanOrEqual(3, $draftInvoices->count(), 'Need at least 3 draft invoices from seeder');

        // Post all invoices rapidly (simulating the original error scenario)
        foreach ($draftInvoices as $invoice) {
            // Use Livewire to simulate the web interface action
            Livewire::test(EditInvoice::class, [
                'record' => $invoice->getRouteKey(),
            ])
                ->callAction('confirm')
                ->assertHasNoErrors();
        }

        // Verify all invoices were posted successfully
        foreach ($draftInvoices as $invoice) {
            $invoice->refresh();
            $this->assertEquals(InvoiceStatus::Posted, $invoice->status);
            $this->assertNotNull($invoice->invoice_number);
            $this->assertNotNull($invoice->journal_entry_id);
        }

        // Verify no duplicate journal entry references exist in the database
        $company = $draftInvoices->first()->company;
        $journalEntries = JournalEntry::where('company_id', $company->id)
            ->whereIn('source_id', $draftInvoices->pluck('id'))
            ->where('source_type', Invoice::class)
            ->get();

        $references = $journalEntries->pluck('reference')->toArray();
        $this->assertCount(
            count($references),
            array_unique($references),
            'No duplicate journal entry references should exist'
        );
    }
}
