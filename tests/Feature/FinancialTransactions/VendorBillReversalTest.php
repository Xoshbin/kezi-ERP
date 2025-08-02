<?php

use App\Models\User;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use App\Enums\Accounting\JournalEntryState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

test('cancelling a posted vendor bill creates a reversing journal entry and an audit log', function () {
    // Arrange
    $company = $this->createConfiguredCompany();
    $user = User::factory()->for($company)->create();
    $this->actingAs($user);

    $vendorBill = VendorBill::factory()->for($company)->create(['status' => 'draft']);
    $vendorBill->lines()->create([
        'description' => 'Test Service',
        'quantity' => 1,
        'unit_price' => \Brick\Money\Money::of(1000, $company->currency->code),
        'expense_account_id' => \App\Models\Account::factory()->for($company)->create(['type' => 'Expense'])->id,
    ]);

    $vendorBillService = app(VendorBillService::class);

    // Act 1: Confirm the bill
    $vendorBillService->confirm($vendorBill, $user);
    $vendorBill->refresh();
    $originalEntry = $vendorBill->journalEntry;

    // Act 2: Cancel the posted bill with a specific reason
    $cancellationReason = 'User requested cancellation due to incorrect PO.';
    $vendorBillService->cancel($vendorBill, $user, $cancellationReason);
    $vendorBill->refresh();
    $originalEntry->refresh();

    // Assert: Bill status and reversal entry are correct
    expect($vendorBill->status)->toBe(VendorBill::STATUS_CANCELED);
    expect($originalEntry->state)->toBe(JournalEntryState::Reversed);
    expect($originalEntry->reversed_entry_id)->not->toBeNull();

    // --- NEW ASSERTION FOR AUDIT LOG ---
    // Assert that a specific audit log entry was created for this cancellation.
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => VendorBill::class,
        'auditable_id' => $vendorBill->id,
        'user_id' => $user->id,
        'event_type' => 'cancellation',
        'description' => 'Vendor Bill Cancelled: ' . $cancellationReason,
    ]);
});
