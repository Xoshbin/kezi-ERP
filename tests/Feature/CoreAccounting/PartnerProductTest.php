<?php

use App\Models\Account;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesApplication;

uses(RefreshDatabase::class, CreatesApplication::class);

beforeEach(function () {
    $this->company = $this->createConfiguredCompany();
    $this->user = User::factory()->for($this->company)->create();
    $this->actingAs($this->user);
});

test('a partner record is soft-deleted to preserve historical transaction context', function () {
    $partner = Partner::factory()->for($this->company)->create();
    $partner->delete();

    // Partners, as non-financial records, should be soft-deleted to maintain auditability [2-5].
    $this->assertSoftDeleted($partner);
    expect(Partner::find($partner->id))->toBeNull(); // Verifies default query behavior
});

test('a soft-deleted partner can be retrieved using "withTrashed" for historical reporting', function () {
    $partner = Partner::factory()->for($this->company)->create();
    $partner->delete();

    // Ensures that historical data linked to soft-deleted entities is still accessible [2-5].
    expect(Partner::withTrashed()->find($partner->id))->not->toBeNull();
});

test('a product record is soft-deleted to preserve its history and linkages', function () {
    $product = Product::factory()->for($this->company)->create();
    $product->delete();

    // Products, like partners, are non-financial and subject to soft deletion principles [2-5].
    $this->assertSoftDeleted($product);
    expect(Product::find($product->id))->toBeNull();
});

test('a soft-deleted product can be retrieved with "withTrashed" for historical analysis', function () {
    $product = Product::factory()->for($this->company)->create();
    $product->delete();

    // Verifies the ability to access product history even after deactivation [2-5].
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('a product is correctly linked to its default income and expense general ledger accounts', function () {
    $incomeAccount = Account::factory()->for($this->company)->create(['type' => 'Income']);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    $product = Product::factory()->for($this->company)->create([
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $expenseAccount->id,
    ]);

    // Ensures proper accounting categorization for product sales and purchases [3, 5].
    expect($product->incomeAccount->id)->toBe($incomeAccount->id);
    expect($product->expenseAccount->id)->toBe($expenseAccount->id);
});

test('a tax is correctly linked to its designated general ledger tax account', function () {
    $taxAccount = Account::factory()->for($this->company)->create(['type' => 'Liability']); // e.g., VAT Payable
    $tax = Tax::factory()->for($this->company)->create(['tax_account_id' => $taxAccount->id]);

    // Critical for accurate tax reporting and balance sheet presentation [3, 5].
    expect($tax->taxAccount->id)->toBe($taxAccount->id);
});