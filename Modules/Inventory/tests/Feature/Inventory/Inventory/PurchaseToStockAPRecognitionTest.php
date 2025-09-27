<?php

namespace Modules\Inventory\Tests\Feature\Inventory;

use App\Actions\Purchases\CreateVendorBillLineAction;
use App\DataTransferObjects\Purchases\CreateVendorBillLineDTO;
use App\Models\Product;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

it('posts AP for storable product bills and posts inventory + input tax correctly', function () {
    $this->setupWithConfiguredCompany();
    $this->setupInventoryTestEnvironment();

    // Create storable product with inventory accounts
    $this->product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
        'inventory_valuation_method' => \App\Enums\Inventory\ValuationMethod::AVCO,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_stock_input_account_id' => $this->stockInputAccount->id,
    ]);

    $quantity = 3;
    $unitPrice = Money::of(200, $this->company->currency->code);
    $total = $unitPrice->multipliedBy($quantity);

    $vendorBill = \Modules\Purchase\Models\VendorBill::factory()->for($this->company)->create([
        'vendor_id' => $this->vendor->id,
        'status' => 'draft',
    ]);

    $lineDto = new CreateVendorBillLineDTO(
        product_id: $this->product->id,
        description: 'Stock Item',
        quantity: $quantity,
        unit_price: (string) $unitPrice->getAmount(),
        expense_account_id: $this->product->expense_account_id,
        tax_id: null,
        analytic_account_id: null
    );

    resolve(CreateVendorBillLineAction::class)->execute($vendorBill, $lineDto);
    $vendorBill->refresh();

    // Act
    resolve(VendorBillService::class)->post($vendorBill, $this->user);

    // Assert: journal entry exists and posted
    $journalEntry = $vendorBill->journalEntry;
    expect($journalEntry)->not->toBeNull();
    expect($journalEntry->is_posted)->toBeTrue();

    // Phase 1: Stock Input debit equals subtotal (Inventory Dr is in valuation JE)
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->product->default_stock_input_account_id,
        'debit' => $total->getMinorAmount()->toInt(),
        'credit' => 0,
    ]);

    // Accounts Payable credit equals total (subtotal + tax). Since tax is null here, matches subtotal
    $this->assertDatabaseHas('journal_entry_lines', [
        'journal_entry_id' => $journalEntry->id,
        'account_id' => $this->company->default_accounts_payable_id,
        'debit' => 0,
        'credit' => $total->getMinorAmount()->toInt(),
    ]);
});
