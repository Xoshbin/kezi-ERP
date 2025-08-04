<?php

use App\Models\User;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Invoice;
use Tests\Traits\MocksTime;
use App\Services\InvoiceService;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;
use Tests\Traits\WithConfiguredCompany;
use App\Enums\Accounting\JournalEntryState;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('cancelling a posted invoice creates a reversing journal entry and an audit log', function () {

    $invoice = Invoice::factory()->for($this->company)->create(['status' => 'draft']);
    $invoice->invoiceLines()->create([
        'description' => 'Consulting Services',
        'quantity' => 1,
        'unit_price' => \Brick\Money\Money::of(2500, $this->company->currency->code),
        'income_account_id' => \App\Models\Account::factory()->for($this->company)->create(['type' => 'Income'])->id,
    ]);

    $invoiceService = app(InvoiceService::class);

    // Act 1: Confirm the invoice to post it.
    $invoiceService->confirm($invoice, $this->user);
    $invoice->refresh();

    $originalEntry = $invoice->journalEntry;
    expect($invoice->status)->toBe(Invoice::STATUS_POSTED);
    expect($originalEntry)->not->toBeNull();

    // Act 2: Cancel the invoice with a reason.
    $cancellationReason = 'Invoice created for wrong client.';
    $invoiceService->cancel($invoice, $this->user, $cancellationReason);
    $invoice->refresh();
    $originalEntry->refresh();

    // Assert: Invoice status and journal entry reversal are correct
    expect($invoice->status)->toBe(Invoice::STATUS_CANCELLED);
    expect($originalEntry->state)->toBe(JournalEntryState::Reversed);
    expect($originalEntry->reversed_entry_id)->not->toBeNull();

    // Assert: Audit log was created with the correct details
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Invoice::class,
        'auditable_id' => $invoice->id,
        'user_id' => $this->user->id,
        'event_type' => 'cancellation',
        'description' => 'Invoice Cancelled: ' . $cancellationReason,
    ]);
});
