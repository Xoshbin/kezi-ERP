<?php

use App\Enums\Purchases\VendorBillStatus;
use App\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

describe('Vendor Bill Confirmation Business Rules', function () {
    it('prevents confirming vendor bill without line items via UI', function () {
        // Test Setup: Create a vendor bill in draft status with zero line items
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        // Ensure no lines exist
        expect($vendorBill->lines)->toHaveCount(0);

        // UI Validation: Verify that the confirmation action button is disabled in the Filament interface
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        // The confirm action should be visible for draft bills
        $editWire->assertActionVisible('confirm');

        // The confirm action should be disabled when no line items exist
        $editWire->assertActionDisabled('confirm');

        // Attempt to call the confirm action - should show error notification
        $editWire->callAction('confirm')
            ->assertNotified(); // Should show error notification

        // Verify the bill was NOT confirmed
        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
        expect($vendorBill->posted_at)->toBeNull();
        expect($vendorBill->journalEntry)->toBeNull();
    });

    it('prevents confirming vendor bill without line items via backend service', function () {
        // Test Setup: Create a vendor bill in draft status with zero line items
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        // Ensure no lines exist
        expect($vendorBill->lines)->toHaveCount(0);

        // Backend Validation: Attempt to programmatically trigger the confirmation action
        $vendorBillService = app(VendorBillService::class);

        // Assert that it throws a validation error with an appropriate message
        expect(function () use ($vendorBillService, $vendorBill) {
            $vendorBillService->confirm($vendorBill, $this->user);
        })->toThrow(RuntimeException::class, __('vendor_bill.validation_no_line_items'));

        // Verify the bill was NOT confirmed
        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
        expect($vendorBill->posted_at)->toBeNull();
        expect($vendorBill->journalEntry)->toBeNull();
    });

    it('allows confirming vendor bill with line items', function () {
        // Create vendor and product with proper setup
        $vendor = \App\Models\Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $product = \App\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'type' => \App\Enums\Products\ProductType::Service, // Use service to avoid inventory account requirement
        ]);

        // Create vendor bill manually to ensure proper setup
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'total_tax' => \Brick\Money\Money::of(0, $this->company->currency->code),
        ]);

        // Create line with proper currency
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Test service line',
            'quantity' => 1,
            'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'subtotal' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'total_line_tax' => \Brick\Money\Money::of(0, $this->company->currency->code),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $vendorBill->refresh();

        // Ensure lines exist
        expect($vendorBill->lines)->toHaveCount(1);

        // Should be able to confirm successfully
        $vendorBillService = app(VendorBillService::class);
        $vendorBillService->confirm($vendorBill, $this->user);

        // Verify the bill was confirmed
        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
        expect($vendorBill->posted_at)->not->toBeNull();
        expect($vendorBill->journalEntry)->not->toBeNull();
    });

    it('shows user-friendly error message when confirming empty vendor bill via UI', function () {
        // Test Setup: Create a vendor bill in draft status with zero line items
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
        ]);

        // Ensure no lines exist
        expect($vendorBill->lines)->toHaveCount(0);

        // Attempt to confirm via Filament action
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        // Call the confirm action and verify error notification is shown
        $editWire->callAction('confirm')
            ->assertNotified(); // Should show error notification with user-friendly message

        // The error message should be user-friendly and clearly explain why the action failed
        // This will be verified once we implement the proper validation
    });

    it('prevents confirmation when vendor bill has zero total amount', function () {
        // Create an expense account for the test
        $expenseAccount = \App\Models\Account::factory()->for($this->company)->create([
            'type' => 'expense',
            'name' => 'Test Expense Account',
        ]);

        // Edge case: vendor bill with lines but zero amounts
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => \Brick\Money\Money::of(0, $this->company->currency->code),
        ]);

        // Create a line with zero amount
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'description' => 'Zero amount line',
            'quantity' => 0,
            'unit_price' => \Brick\Money\Money::of(0, $this->company->currency->code),
            'subtotal' => \Brick\Money\Money::of(0, $this->company->currency->code),
            'total_line_tax' => \Brick\Money\Money::of(0, $this->company->currency->code),
            'expense_account_id' => $expenseAccount->id,
        ]);

        $vendorBill->refresh();
        expect($vendorBill->lines)->toHaveCount(1);
        expect($vendorBill->total_amount->isZero())->toBeTrue();

        // Should still prevent confirmation due to zero total
        $vendorBillService = app(VendorBillService::class);

        expect(function () use ($vendorBillService, $vendorBill) {
            $vendorBillService->confirm($vendorBill, $this->user);
        })->toThrow(RuntimeException::class, __('vendor_bill.validation_zero_total_amount'));
    });

    it('enables confirm action when vendor bill has valid line items', function () {
        // Create vendor and product
        $vendor = \App\Models\Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $product = \App\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'type' => \App\Enums\Products\ProductType::Service,
        ]);

        // Create vendor bill with valid line items
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => \Brick\Money\Money::of(100, $this->company->currency->code),
        ]);

        // Create a valid line
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Valid service line',
            'quantity' => 1,
            'unit_price' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'subtotal' => \Brick\Money\Money::of(100, $this->company->currency->code),
            'total_line_tax' => \Brick\Money\Money::of(0, $this->company->currency->code),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $vendorBill->refresh();

        // UI should show confirm action as enabled
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        $editWire->assertActionVisible('confirm');
        $editWire->assertActionEnabled('confirm');
    });
});
