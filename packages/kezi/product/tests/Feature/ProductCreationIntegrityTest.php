<?php

namespace Kezi\Product\Tests\Feature;

use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can create a product with explicit inventory valuation method', function () {
    // Create the accounts needed for the product
    $incomeAccount = Account::factory()->for($this->company)->create();
    $expenseAccount = Account::factory()->for($this->company)->create();
    $inventoryAccount = Account::factory()->for($this->company)->create();

    // This reproduces the exact scenario from the error message but with explicit valuation method
    $product = Product::create([
        'name' => 'iphone',
        'sku' => 'iphone17',
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable,
        'description' => 'iphone',
        'unit_price' => Money::of(100000, $this->company->currency->code),
        'income_account_id' => $incomeAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'default_inventory_account_id' => $inventoryAccount->id,
        'inventory_valuation_method' => ValuationMethod::AVCO, // Explicitly set
        'is_active' => true,
        'company_id' => $this->company->id,
    ]);

    // Verify the product was created successfully
    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->toBe('iphone')
        ->and($product->sku)->toBe('iphone17')
        ->and($product->type)->toBe(\Kezi\Product\Enums\Products\ProductType::Storable)
        ->and($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);

    // Verify it's saved in the database
    $this->assertDatabaseHas('products', [
        'name' => json_encode(['en' => 'iphone']),
        'sku' => 'iphone17',
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable->value,
        'inventory_valuation_method' => ValuationMethod::AVCO->value,
        'company_id' => $this->company->id,
    ]);
});

it('can create a product using factory that matches the database default', function () {
    $product = Product::factory()->for($this->company)->create();

    // Verify the factory default matches the database default
    expect($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);

    // Verify it's properly stored in the database
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'inventory_valuation_method' => 'avco', // Database stores the string value
    ]);
});

it('can create a product through Filament interface with default valuation method', function () {
    $incomeAccount = Account::factory()->for($this->company)->create();
    $expenseAccount = Account::factory()->for($this->company)->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => \Kezi\Product\Enums\Products\ProductType::Storable->value,
            'unit_price' => 100000,
            'income_account_id' => $incomeAccount->id,
            'expense_account_id' => $expenseAccount->id,
            'default_inventory_account_id' => $expenseAccount->id,
            // Note: Not explicitly setting inventory_valuation_method
            // The form should use the default from the ProductResource
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the product was created with the correct default valuation method
    $this->assertDatabaseHas('products', [
        'name' => json_encode(['en' => 'Test Product']),
        'sku' => 'TEST-001',
        'type' => \Kezi\Product\Enums\Products\ProductType::Storable->value,
        'inventory_valuation_method' => ValuationMethod::AVCO->value,
        'company_id' => $this->company->id,
    ]);
});
