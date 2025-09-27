<?php

namespace Tests\Feature;

use App\Enums\Inventory\ValuationMethod;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can create a product using factory without specifying inventory valuation method', function () {
    // This test should initially fail with the constraint violation
    // and pass after we fix the ProductFactory

    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create();

    expect($product)->toBeInstanceOf(\Modules\Product\Models\Product::class)
        ->and($product->inventory_valuation_method)->toBeInstanceOf(ValuationMethod::class)
        ->and($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);
});

it('can create a product with explicit inventory valuation method', function () {
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'inventory_valuation_method' => ValuationMethod::FIFO,
    ]);

    expect($product->inventory_valuation_method)->toBe(ValuationMethod::FIFO);
});

it('defaults to AVCO valuation method when not specified', function () {
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->make();

    expect($product->inventory_valuation_method)->toBe(ValuationMethod::AVCO);
});

it('properly casts inventory valuation method to enum', function () {
    $product = \Modules\Product\Models\Product::factory()->for($this->company)->create([
        'inventory_valuation_method' => 'fifo',
    ]);

    expect($product->inventory_valuation_method)->toBeInstanceOf(ValuationMethod::class)
        ->and($product->inventory_valuation_method)->toBe(ValuationMethod::FIFO);
});
