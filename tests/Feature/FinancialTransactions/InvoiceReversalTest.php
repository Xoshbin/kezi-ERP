<?php

use App\Models\User;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Enums\Accounting\JournalEntryState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithUnlockedPeriod;

uses(RefreshDatabase::class, CreatesApplication::class, WithUnlockedPeriod::class);

beforeEach(function () {
    $this->setupWithUnlockedPeriod();
});

afterEach(function () {
    $this->tearDownWithUnlockedPeriod();
});

test('cancelling a posted invoice creates a reversing journal entry and an audit log', function () {
    // Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $this->actingAs($user);

    $invoice = Invoice::factory()->for($company)->create(['status' => 'draft']);
    $invoice->invoiceLines()->create([
        'description' => 'Consulting Services',
        'quantity' => 1,
        'unit_price' => \Brick\Money\Money::of(2500, $company->currency->code),
        'income_account_id' => \App\Models\Account::factory()->for($company)->create(['type' => 'Income'])->id,
    ]);

    $invoiceService = app(InvoiceService::class);

    // Act 1: Confirm the invoice to post it.
    $invoiceService->confirm($invoice, $user);
    $invoice->refresh();

    $originalEntry = $invoice->journalEntry;
    expect($invoice->status)->toBe(Invoice::STATUS_POSTED);
    expect($originalEntry)->not->toBeNull();

    // Act 2: Cancel the invoice with a reason.
    $cancellationReason = 'Invoice created for wrong client.';
    $invoiceService->cancel($invoice, $user, $cancellationReason);
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
        'user_id' => $user->id,
        'event_type' => 'cancellation',
        'description' => 'Invoice Cancelled: ' . $cancellationReason,
    ]);
});
