<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Invoices\Pages\EditInvoice;
use Kezi\Accounting\Models\JournalEntry;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);

    // Create test data: 3 draft invoices with invoice lines for testing
    Invoice::factory()->count(3)->withLines()->create([
        'status' => InvoiceStatus::Draft,
        'company_id' => $this->company->id,
    ]);
});

test('posting multiple invoices via web interface works correctly', function () {
    // Get some draft invoices from the seeded data
    $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

    expect($draftInvoices)->toHaveCount(3);

    $postedInvoiceNumbers = [];
    $journalEntryReferences = [];

    // Post each invoice one by one (simulating the web interface scenario)
    foreach ($draftInvoices as $invoice) {
        // Simulate the web interface posting action using Livewire
        livewire(EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ])
            ->callAction('post')
            ->assertHasNoErrors();

        // Refresh the invoice to get updated data
        $invoice->refresh();

        // Verify the invoice was posted successfully
        expect($invoice->status)->toBe(InvoiceStatus::Posted);
        expect($invoice->invoice_number)->not->toBeNull();
        expect($invoice->journal_entry_id)->not->toBeNull();
        expect($invoice->posted_at)->not->toBeNull();

        $postedInvoiceNumbers[] = $invoice->invoice_number;

        // Get the journal entry and verify it has the correct reference
        $journalEntry = JournalEntry::find($invoice->journal_entry_id);
        expect($journalEntry)->not->toBeNull();
        expect($journalEntry->reference)->toBe($invoice->invoice_number);

        $journalEntryReferences[] = $journalEntry->reference;
    }

    // Verify all invoice numbers are unique
    expect(array_unique($postedInvoiceNumbers))->toHaveCount(count($postedInvoiceNumbers));

    // Verify all journal entry references are unique
    expect(array_unique($journalEntryReferences))->toHaveCount(count($journalEntryReferences));

    // Verify the invoice numbers follow the new format
    expect($postedInvoiceNumbers[0])->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    expect($postedInvoiceNumbers[1])->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');
    expect($postedInvoiceNumbers[2])->toMatch('/^INV\/\d{4}\/\d{2}\/\d{7}$/');

    // Extract the numeric parts from the new format and verify they're sequential
    $numbers = array_map(function ($invoiceNumber) {
        // Extract the last part after the last slash (e.g., "0000001" from "INV/2025/08/0000001")
        return (int) substr($invoiceNumber, strrpos($invoiceNumber, '/') + 1);
    }, $postedInvoiceNumbers);

    sort($numbers);
    expect($numbers[0])->toBe(1);
    expect($numbers[1])->toBe(2);
    expect($numbers[2])->toBe(3);
});

test('no duplicate journal entry constraint violations occur', function () {
    // Get multiple draft invoices
    $draftInvoices = Invoice::where('status', InvoiceStatus::Draft)->take(3)->get();

    expect($draftInvoices)->toHaveCount(3);

    // Post all invoices rapidly (simulating the original error scenario)
    foreach ($draftInvoices as $invoice) {
        // Use Livewire to simulate the web interface action
        livewire(EditInvoice::class, [
            'record' => $invoice->getRouteKey(),
        ])
            ->callAction('post')
            ->assertHasNoErrors();
    }

    // Verify all invoices were posted successfully
    foreach ($draftInvoices as $invoice) {
        $invoice->refresh();
        expect($invoice->status)->toBe(InvoiceStatus::Posted);
        expect($invoice->invoice_number)->not->toBeNull();
        expect($invoice->journal_entry_id)->not->toBeNull();
    }

    // Verify no duplicate journal entry references exist in the database
    $company = $draftInvoices->first()->company;
    $journalEntries = JournalEntry::where('company_id', $company->id)
        ->whereIn('source_id', $draftInvoices->pluck('id'))
        ->where('source_type', Invoice::class)
        ->get();

    $references = $journalEntries->pluck('reference')->toArray();
    expect(array_unique($references))->toHaveCount(count($references));
});
