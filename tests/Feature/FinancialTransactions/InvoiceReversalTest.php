<?php

use App\Actions\Sales\CreateInvoiceLineAction;
use App\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use App\Enums\Accounting\JournalEntryState;
use App\Enums\Sales\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\MocksTime;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

test('cancelling a posted invoice creates a reversing journal entry and an audit log', function () {

    $invoice = Invoice::factory()->for($this->company)->create(['status' => 'draft']);
    $incomeAccount = \App\Models\Account::factory()->for($this->company)->create(['type' => 'income']);
    $lineDto = new CreateInvoiceLineDTO(
        description: 'Consulting Services',
        quantity: 1,
        unit_price: \Brick\Money\Money::of('2500', $this->company->currency->code),
        income_account_id: $incomeAccount->id,
        product_id: null,
        tax_id: null,
    );
    app(CreateInvoiceLineAction::class)->execute($invoice, $lineDto);

    $invoiceService = app(InvoiceService::class);

    // Act 1: Confirm the invoice to post it.
    $invoiceService->confirm($invoice, $this->user);
    $invoice->refresh();

    $originalEntry = $invoice->journalEntry;
    expect($invoice->status)->toBe(InvoiceStatus::Posted);
    expect($originalEntry)->not->toBeNull();

    // Act 2: Cancel the invoice with a reason.
    $cancellationReason = 'Invoice created for wrong client.';
    $invoiceService->cancel($invoice, $this->user, $cancellationReason);
    $invoice->refresh();
    $originalEntry->refresh();

    // Assert: Invoice status and journal entry reversal are correct
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);
    expect($originalEntry->state)->toBe(JournalEntryState::Reversed);
    expect($originalEntry->reversed_entry_id)->not->toBeNull();

    // Assert: Audit log was created with the correct details
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Invoice::class,
        'auditable_id' => $invoice->id,
        'user_id' => $this->user->id,
        'event_type' => 'cancellation',
        'description' => 'Invoice Cancelled: '.$cancellationReason,
    ]);
});
