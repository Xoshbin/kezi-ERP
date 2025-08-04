<?php

use App\Models\Tax;
use App\Models\User;
use App\Models\Account;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Product;
use Tests\Traits\CreatesApplication;
use Tests\Traits\WithConfiguredCompany;
use Brick\Money\Money; // Import the Money class
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

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
    // MODIFIED: The product factory needs a Money object for unit_price
    $currencyCode = $this->company->currency->code;
    $product = Product::factory()->for($this->company)->create([
        'unit_price' => Money::of(10, $currencyCode)
    ]);
    $product->delete();

    // Products, like partners, are non-financial and subject to soft deletion principles [2-5].
    $this->assertSoftDeleted($product);
    expect(Product::find($product->id))->toBeNull();
});

test('a soft-deleted product can be retrieved with "withTrashed" for historical analysis', function () {
    // MODIFIED: The product factory needs a Money object for unit_price
    $currencyCode = $this->company->currency->code;
    $product = Product::factory()->for($this->company)->create([
        'unit_price' => Money::of(10, $currencyCode)
    ]);
    $product->delete();

    // Verifies the ability to access product history even after deactivation [2-5].
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('a product is correctly linked to its default income and expense general ledger accounts', function () {
    $incomeAccount = Account::factory()->for($this->company)->create(['type' => 'Income']);
    $expenseAccount = Account::factory()->for($this->company)->create(['type' => 'Expense']);
    // MODIFIED: The product factory needs a Money object for unit_price
    $currencyCode = $this->company->currency->code;
    $product = Product::factory()->for($this->company)->create([
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'unit_price' => Money::of(10, $currencyCode)
    ]);

    // Ensures proper accounting categorization for product sales and purchases [3, 5].
    expect($product->incomeAccount->id)->toBe($incomeAccount->id);
    expect($product->expenseAccount->id)->toBe($expenseAccount->id);
});

test('a tax is correctly linked to its designated general ledger tax account', function () {
    $taxAccount = Account::factory()->for($this->company)->create(['type' => 'Liability']); // e.g., VAT Payable
    // MODIFIED: The tax factory needs a Money object for rate
    $currencyCode = $this->company->currency->code;
    $tax = Tax::factory()->for($this->company)->create([
        'tax_account_id' => $taxAccount->id,
        'rate' => Money::of('0.05', $currencyCode), // e.g. 5%
    ]);

    // Critical for accurate tax reporting and balance sheet presentation [3, 5].
    expect($tax->taxAccount->id)->toBe($taxAccount->id);
});
