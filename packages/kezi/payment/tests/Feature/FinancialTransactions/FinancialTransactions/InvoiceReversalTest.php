<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\JournalEntryState;
use Kezi\Accounting\Models\Account;
use Kezi\Sales\Actions\Sales\CreateInvoiceLineAction;
use Kezi\Sales\DataTransferObjects\Sales\CreateInvoiceLineDTO;
use Kezi\Sales\Enums\Sales\InvoiceStatus;
use Kezi\Sales\Models\Invoice;
use Kezi\Sales\Services\InvoiceService;
use Tests\Traits\MocksTime;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class, MocksTime::class);

beforeEach(function () {
    $this->seed(\Kezi\Foundation\Database\Seeders\RolesAndPermissionsSeeder::class);
    setPermissionsTeamId($this->company->id);
    $this->user->assignRole('super_admin');
});

test('cancelling a posted invoice creates a reversing journal entry and an audit log', function () {

    $invoice = Invoice::factory()->for($this->company)->create(['status' => 'draft']);
    $incomeAccount = Account::factory()->for($this->company)->create(['type' => 'income']);
    $lineDto = new CreateInvoiceLineDTO(
        description: 'Consulting Services',
        quantity: 1,
        unit_price: Money::of('2500', $this->company->currency->code),
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
