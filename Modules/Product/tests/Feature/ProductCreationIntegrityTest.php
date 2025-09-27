<?php

namespace Modules\Product\Tests\Feature;

use App\Enums\Inventory\ValuationMethod;
use App\Enums\Products\ProductType;
use App\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use App\Models\Account;
use App\Models\Product;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can create a product with explicit inventory valuation method', function () {
    // Create the accounts needed for the product
    $incomeAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $inventoryAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();

    // This reproduces the exact scenario from the error message but with explicit valuation method
    $product = \Modules\Product\Models\Product::create([
        'name' => 'iphone',
        'sku' => 'iphone17',
        'type' => \Modules\Product\Enums\Products\ProductType::Storable,
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
    expect($product)->toBeInstanceOf(\Modules\Product\Models\Product::class)
        ->and($product->name)->toBe('iphone')
        ->and($product->sku)->toBe('iphone17')
        ->and($product->type)->toBe(\Modules\Product\Enums\Products\ProductType::Storable)
        ->and($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);

    // Verify it's saved in the database
    $this->assertDatabaseHas('products', [
        'name' => json_encode(['en' => 'iphone']),
        'sku' => 'iphone17',
        'type' => \Modules\Product\Enums\Products\ProductType::Storable->value,
        'inventory_valuation_method' => ValuationMethod::AVCO->value,
        'company_id' => $this->company->id,
    ]);
});

it('can create a product using factory that matches the database default', function () {
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create();

    // Verify the factory default matches the database default
    expect($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);

    // Verify it's properly stored in the database
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'inventory_valuation_method' => 'avco', // Database stores the string value
    ]);
});

it('can create a product through Filament interface with default valuation method', function () {
    $incomeAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => \Modules\Product\Enums\Products\ProductType::Storable->value,
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
        'type' => \Modules\Product\Enums\Products\ProductType::Storable->value,
        'inventory_valuation_method' => ValuationMethod::AVCO->value,
        'company_id' => $this->company->id,
    ]);
});
