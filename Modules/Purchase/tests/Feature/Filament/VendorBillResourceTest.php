<?php

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\VendorBills\Pages\EditVendorBill;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Modules\Foundation\Models\Partner;
use Modules\Inventory\Models\StockMove;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Modules\Purchase\Enums\Purchases\VendorBillStatus;
use Modules\Purchase\Models\VendorBill;
use Modules\Purchase\Services\VendorBillService;
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
        $editWire->assertActionVisible('post');

        // The confirm action should be disabled when no line items exist
        $editWire->assertActionDisabled('post');

        // Attempt to call the confirm action - should show error notification
        $editWire->callAction('post')
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
        })->toThrow(\RuntimeException::class, 'Cannot confirm vendor bill without line items');

        // Verify the bill was NOT confirmed
        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Draft);
        expect($vendorBill->posted_at)->toBeNull();
        expect($vendorBill->journalEntry)->toBeNull();
    });

    it('allows confirming vendor bill with line items', function () {
        // Create vendor and product with proper setup
        $vendor = Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'type' => ProductType::Service, // Use service to avoid inventory account requirement
        ]);

        // Create vendor bill manually to ensure proper setup
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => Money::of(100, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
        ]);

        // Create line with proper currency
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Test service line',
            'quantity' => 1,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'subtotal' => Money::of(100, $this->company->currency->code),
            'total_line_tax' => Money::of(0, $this->company->currency->code),
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
        $editWire->callAction('post')
            ->assertNotified(); // Should show error notification with user-friendly message

        // The error message should be user-friendly and clearly explain why the action failed
        // This will be verified once we implement the proper validation
    });

    it('prevents confirmation when vendor bill has zero total amount', function () {
        // Create an expense account for the test
        $expenseAccount = Account::factory()->for($this->company)->create([
            'type' => 'expense',
            'name' => 'Test Expense Account',
        ]);

        // Edge case: vendor bill with lines but zero amounts
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => Money::of(0, $this->company->currency->code),
        ]);

        // Create a line with zero amount
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'description' => 'Zero amount line',
            'quantity' => 0,
            'unit_price' => Money::of(0, $this->company->currency->code),
            'subtotal' => Money::of(0, $this->company->currency->code),
            'total_line_tax' => Money::of(0, $this->company->currency->code),
            'expense_account_id' => $expenseAccount->id,
        ]);

        $vendorBill->refresh();
        expect($vendorBill->lines)->toHaveCount(1);
        expect($vendorBill->total_amount->isZero())->toBeTrue();

        // Should still prevent confirmation due to zero total
        $vendorBillService = app(VendorBillService::class);

        expect(function () use ($vendorBillService, $vendorBill) {
            $vendorBillService->confirm($vendorBill, $this->user);
        })->toThrow(\RuntimeException::class, 'Cannot confirm vendor bill with zero total amount');
    });

    it('enables confirm action when vendor bill has valid line items', function () {
        // Create vendor and product
        $vendor = Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'type' => ProductType::Service,
        ]);

        // Create vendor bill with valid line items
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => Money::of(100, $this->company->currency->code),
        ]);

        // Create a valid line
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Valid service line',
            'quantity' => 1,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'subtotal' => Money::of(100, $this->company->currency->code),
            'total_line_tax' => Money::of(0, $this->company->currency->code),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $vendorBill->refresh();

        // UI should show confirm action as enabled
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        $editWire->assertActionVisible('post');
        $editWire->assertActionEnabled('post');
    });

    it('successfully confirms vendor bill with storable products via Filament UI', function () {
        // Create vendor and storable product
        $vendor = Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        // Create required accounts for storable product
        $inventoryAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Inventory Asset'],
            'code' => '1300',
            'type' => AccountType::CurrentAssets,
        ]);

        $stockInputAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Stock Input'],
            'code' => '2100',
            'type' => AccountType::CurrentLiabilities,
        ]);

        $expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Product Expense'],
            'code' => '5000',
            'type' => AccountType::Expense,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'type' => ProductType::Storable, // Use storable to test inventory workflow
            'default_inventory_account_id' => $inventoryAccount->id,
            'default_stock_input_account_id' => $stockInputAccount->id,
            'expense_account_id' => $expenseAccount->id,
        ]);

        // Create vendor bill with storable product
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => Money::of(100, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
        ]);

        // Create line with storable product
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Test storable product line',
            'quantity' => 1,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'subtotal' => Money::of(100, $this->company->currency->code),
            'total_line_tax' => Money::of(0, $this->company->currency->code),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $vendorBill->refresh();

        // Ensure lines exist and bill is ready for confirmation
        expect($vendorBill->lines)->toHaveCount(1);
        expect($vendorBill->status)->toBe(VendorBillStatus::Draft);

        // Test Filament UI confirmation
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        // Verify confirm action is available and enabled
        $editWire->assertActionVisible('post');
        $editWire->assertActionEnabled('post');

        // First test: Try confirming via service layer directly to ensure it works
        $vendorBillService = app(VendorBillService::class);
        try {
            $vendorBillService->confirm($vendorBill, $this->user);
            $vendorBill->refresh();
            expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
            expect($vendorBill->posted_at)->not->toBeNull();
            expect($vendorBill->journalEntry)->not->toBeNull();
        } catch (Exception $e) {
            // If service layer fails, we need to understand why
            throw new \Exception('Service layer confirmation failed: '.$e->getMessage());
        }

        // Create a fresh vendor bill for UI test (can't reset posted bills due to constraints)
        $uiTestVendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $this->company->currency_id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => now()->format('Y-m-d'),
            'accounting_date' => now()->format('Y-m-d'),
            'total_amount' => Money::of(100, $this->company->currency->code),
            'total_tax' => Money::of(0, $this->company->currency->code),
        ]);

        // Create line with storable product for UI test
        $uiTestVendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Test storable product line for UI',
            'quantity' => 1,
            'unit_price' => Money::of(100, $this->company->currency->code),
            'subtotal' => Money::of(100, $this->company->currency->code),
            'total_line_tax' => Money::of(0, $this->company->currency->code),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $uiTestVendorBill->refresh();

        // Now test the Filament UI confirmation
        $editWire = livewire(EditVendorBill::class, [
            'record' => $uiTestVendorBill->getRouteKey(),
        ]);

        // Perform the confirmation action
        $editWire->callAction('post');

        // Check if there were any error notifications
        // The action should succeed without errors
        $editWire->assertNotified();

        // Verify the bill was confirmed successfully
        $uiTestVendorBill->refresh();
        expect($uiTestVendorBill->status)->toBe(VendorBillStatus::Posted);
        expect($uiTestVendorBill->posted_at)->not->toBeNull();
        expect($uiTestVendorBill->journalEntry)->not->toBeNull();

        // Verify journal entry was created with correct reference
        $journalEntry = $uiTestVendorBill->journalEntry;
        expect($journalEntry->reference)->toBe($uiTestVendorBill->bill_reference);
        expect($journalEntry->is_posted)->toBeTrue();

        // Verify stock move was created for storable product
        $stockMove = StockMove::where('source_type', VendorBill::class)
            ->where('source_id', $uiTestVendorBill->id)
            ->first();
        expect($stockMove)->not->toBeNull();

        // Check product line for product and quantity
        $productLine = $stockMove->productLines()->where('product_id', $product->id)->first();
        expect($productLine)->not->toBeNull();
        expect((float) $productLine->quantity)->toBe(1.0);

        // Verify no duplicate journal entry constraint violations occurred
        // This test specifically addresses the issue where multiple journal entries
        // with the same reference were being created, causing constraint violations
        $journalEntries = JournalEntry::where('source_type', VendorBill::class)
            ->where('source_id', $uiTestVendorBill->id)
            ->get();

        // Should have exactly TWO journal entries for a storable product vendor bill:
        // 1. Main vendor bill entry (Dr Expense, Cr AP)
        // 2. Stock valuation entry (Dr Inventory, Cr Stock Input)
        expect($journalEntries)->toHaveCount(2);

        // Verify the main journal entry has the expected reference format (not STOCK-IN)
        expect($journalEntry->reference)->not->toContain('STOCK-IN');

        // Verify there's a separate stock valuation journal entry
        $stockValuationEntry = $journalEntries->first(function ($entry) use ($journalEntry) {
            return $entry->id !== $journalEntry->id;
        });
        expect($stockValuationEntry)->not->toBeNull();
        expect($stockValuationEntry->reference)->toContain('STOCK-IN');
        expect($stockValuationEntry->reference)->toContain('VendorBill-'.$uiTestVendorBill->id);
    });

    it('handles exchange rate fallback during vendor bill confirmation via UI', function () {
        // Create foreign currency
        $usd = Currency::factory()->create(['code' => 'USD']);

        // Create current exchange rate (not for historical date)
        CurrencyRate::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $usd->id,
            'rate' => 1460.0,
            'effective_date' => now(),
        ]);

        // Create vendor and product
        $vendor = Partner::factory()->vendor()->create([
            'company_id' => $this->company->id,
        ]);

        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'unit_price' => Money::of(100, 'USD'),
            'type' => ProductType::Service,
        ]);

        // Create vendor bill with historical date (no rate available for this date)
        $vendorBill = VendorBill::factory()->for($this->company)->create([
            'vendor_id' => $vendor->id,
            'currency_id' => $usd->id,
            'status' => VendorBillStatus::Draft,
            'bill_date' => '2025-05-15', // Historical date
            'accounting_date' => '2025-05-15',
            'total_amount' => Money::of(100, 'USD'),
            'total_tax' => Money::of(0, 'USD'),
        ]);

        // Create line item
        $vendorBill->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'description' => 'Test service line',
            'quantity' => 1,
            'unit_price' => Money::of(100, 'USD'),
            'subtotal' => Money::of(100, 'USD'),
            'total_line_tax' => Money::of(0, 'USD'),
            'expense_account_id' => $product->expense_account_id,
        ]);

        $vendorBill->refresh();

        // Test Filament UI confirmation with exchange rate fallback
        $editWire = livewire(EditVendorBill::class, [
            'record' => $vendorBill->getRouteKey(),
        ]);

        // Perform the confirmation action - should use fallback exchange rate
        $editWire->callAction('post');

        // Verify success notification (no exchange rate error)
        $editWire->assertNotified();

        // Verify the bill was confirmed successfully with fallback rate
        $vendorBill->refresh();
        expect($vendorBill->status)->toBe(VendorBillStatus::Posted);
        expect((float) $vendorBill->exchange_rate_at_creation)->toBe(1460.0);
        expect($vendorBill->total_amount_company_currency->__toString())->toContain('146000.000');
        expect($vendorBill->journalEntry)->not->toBeNull();
    });
});
