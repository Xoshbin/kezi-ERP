<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Accounting\Enums\Accounting\JournalEntryState;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Purchase\Actions\Purchases\CreateVendorBillLineAction;
use Jmeryar\Purchase\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use Jmeryar\Purchase\Enums\Purchases\VendorBillStatus;
use Jmeryar\Purchase\Models\VendorBill;
use Jmeryar\Purchase\Services\VendorBillService;
use Tests\Traits\WithConfiguredCompany;

// Import the Action
// Import the DTO

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    setPermissionsTeamId($this->company->id);
    $this->user->assignRole('super_admin');
});

test('cancelling a posted vendor bill creates a reversing journal entry and an audit log', function () {
    // Arrange: Create a draft vendor bill to set up the test scenario.
    $vendorBill = VendorBill::factory()->for($this->company)->create(['status' => 'draft']);

    // Act: Create the vendor bill line using our robust, established pattern.
    $lineDto = new CreateVendorBillLineDTO(
        description: 'Test Service',
        quantity: 1,
        unit_price: '1000.00',
        expense_account_id: Account::factory()->for($this->company)->create(['type' => 'expense'])->id,
        product_id: null,
        tax_id: null,
        analytic_account_id: null
    );

    app(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);
    // --- END OF FIX ---

    $vendorBillService = app(VendorBillService::class);

    // Act 1b: Confirm the bill to make it 'posted'.
    $vendorBillService->post($vendorBill, $this->user);
    $vendorBill->refresh();
    $originalEntry = $vendorBill->journalEntry;

    // Act 2: Cancel the posted bill with a specific reason.
    $cancellationReason = 'User requested cancellation due to incorrect PO.';
    $vendorBillService->cancel($vendorBill, $this->user, $cancellationReason);
    $vendorBill->refresh();
    $originalEntry->refresh();

    // Assert: Bill status and reversal entry are correct.
    expect($vendorBill->status)->toBe(VendorBillStatus::Cancelled);
    expect($originalEntry->state)->toBe(JournalEntryState::Reversed);
    expect($originalEntry->reversed_entry_id)->not->toBeNull();

    // Assert: A specific audit log entry was created for this cancellation.
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => VendorBill::class,
        'auditable_id' => $vendorBill->id,
        'user_id' => $this->user->id,
        'event_type' => 'cancellation',
        'description' => 'Vendor Bill Cancelled: '.$cancellationReason,
    ]);
});
