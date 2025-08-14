<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Enums\Sales\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
        if ($user && $user->companies->isNotEmpty()) {
            $this->actingAs($user); // Authenticate first
            \Filament\Facades\Filament::setTenant($user->companies->first());
        }
    }

    public function test_posting_multiple_invoices_via_web_interface_works_correctly()
    {
        // Get a user to authenticate as
        $user = User::first();
        $this->actingAs($user);

        // Get some draft invoices from the seeded data
        $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

        $this->assertGreaterThanOrEqual(3, $draftInvoices->count(), 'Need at least 3 draft invoices from seeder');

        $postedInvoiceNumbers = [];
        $journalEntryReferences = [];

        // Post each invoice one by one (simulating the web interface scenario)
        foreach ($draftInvoices as $invoice) {
            // Simulate the web interface posting action using Livewire
            Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
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

        // Verify the invoice numbers follow the expected sequence
        // (They should start from INV-00001 since we're using a fresh database)
        $this->assertStringStartsWith('INV-', $postedInvoiceNumbers[0]);
        $this->assertStringStartsWith('INV-', $postedInvoiceNumbers[1]);
        $this->assertStringStartsWith('INV-', $postedInvoiceNumbers[2]);

        // Extract the numeric parts and verify they're sequential
        $numbers = array_map(function($invoiceNumber) {
            return (int) substr($invoiceNumber, 4); // Remove 'INV-' prefix
        }, $postedInvoiceNumbers);

        sort($numbers);
        $this->assertEquals(1, $numbers[0]);
        $this->assertEquals(2, $numbers[1]);
        $this->assertEquals(3, $numbers[2]);
    }

    public function test_no_duplicate_journal_entry_constraint_violations_occur()
    {
        $user = User::first();
        $this->actingAs($user);

        // Get multiple draft invoices
        $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

        $this->assertGreaterThanOrEqual(3, $draftInvoices->count(), 'Need at least 3 draft invoices from seeder');

        // Post all invoices rapidly (simulating the original error scenario)
        foreach ($draftInvoices as $invoice) {
            // Use Livewire to simulate the web interface action
            Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
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
