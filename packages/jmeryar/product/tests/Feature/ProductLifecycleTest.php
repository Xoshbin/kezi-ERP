<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

describe('Product Active/Inactive States', function () {
    it('can be created as active when is_active explicitly set', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['is_active' => true]);

        expect($product->is_active)->toBeTrue();
    });

    it('can deactivate a product', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['is_active' => true]);

        $product->update(['is_active' => false]);

        expect($product->fresh()->is_active)->toBeFalse();
    });

    it('can reactivate a product', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['is_active' => false]);

        $product->update(['is_active' => true]);

        expect($product->fresh()->is_active)->toBeTrue();
    });

    it('active scope filters only active products', function () {
        Product::factory()
            ->for($this->company)
            ->count(3)
            ->create(['is_active' => true]);

        Product::factory()
            ->for($this->company)
            ->count(2)
            ->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        expect($activeProducts)->toHaveCount(3);
        expect($activeProducts->every(fn ($p) => $p->is_active))->toBeTrue();
    });
});

describe('Product with Transactions', function () {
    it('can deactivate product with transactions', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['is_active' => true]);

        // Create a transaction using this product
        \Jmeryar\Sales\Models\InvoiceLine::factory()->create([
            'product_id' => $product->id,
        ]);

        // Can still deactivate even with transactions
        $product->update(['is_active' => false]);

        expect($product->fresh()->is_active)->toBeFalse();
    });

    it('cannot delete product with transactions', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create();

        \Jmeryar\Sales\Models\InvoiceLine::factory()->create([
            'product_id' => $product->id,
        ]);

        expect(fn () => $product->delete())
            ->toThrow(\Jmeryar\Foundation\Exceptions\DeletionNotAllowedException::class);
    });

    it('cannot delete product used in vendor bills', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create();

        \Jmeryar\Purchase\Models\VendorBillLine::factory()->create([
            'product_id' => $product->id,
        ]);

        expect(fn () => $product->delete())
            ->toThrow(\Jmeryar\Foundation\Exceptions\DeletionNotAllowedException::class);
    });
});

describe('Product Lifecycle with Variants', function () {
    it('deactivating template syncs to variants', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'is_template' => true,
                'is_active' => true,
            ]);

        $variant1 = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'is_active' => true,
            ]);

        $variant2 = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'is_active' => true,
            ]);

        $template->update(['is_active' => false]);

        expect($variant1->fresh()->is_active)->toBeFalse();
        expect($variant2->fresh()->is_active)->toBeFalse();
    });

    it('reactivating template syncs to variants', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'is_template' => true,
                'is_active' => false,
            ]);

        $variant = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'is_active' => false,
            ]);

        $template->update(['is_active' => true]);

        expect($variant->fresh()->is_active)->toBeTrue();
    });

    it('can individually deactivate variant without affecting template', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'is_template' => true,
                'is_active' => true,
            ]);

        $variant = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'is_active' => true,
            ]);

        // Variants can be individually deactivated
        // This should work without affecting template
        Product::withoutEvents(function () use ($variant) {
            $variant->update(['is_active' => false]);
        });

        expect($variant->fresh()->is_active)->toBeFalse();
        expect($template->fresh()->is_active)->toBeTrue();
    });
});

describe('Product Soft Delete', function () {
    it('soft deletes product', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create();

        $product->delete();

        expect($product->trashed())->toBeTrue();
        $this->assertSoftDeleted($product);
    });

    it('can restore soft deleted product', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create();

        $product->delete();
        expect($product->trashed())->toBeTrue();

        $product->restore();
        expect($product->fresh()->trashed())->toBeFalse();
    });

    it('trashed products are excluded from queries', function () {
        Product::factory()
            ->for($this->company)
            ->count(3)
            ->create();

        $trashedProduct = Product::factory()
            ->for($this->company)
            ->create();
        $trashedProduct->delete();

        expect(Product::count())->toBe(3);
        expect(Product::withTrashed()->count())->toBe(4);
    });
});

describe('Product Type Constraints', function () {
    it('storable product requires inventory account', function () {
        expect(fn () => Product::factory()
            ->for($this->company)
            ->create([
                'type' => \Jmeryar\Product\Enums\Products\ProductType::Storable,
                'default_inventory_account_id' => null,
            ]))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('service product does not require inventory account', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create([
                'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
                'default_inventory_account_id' => null,
            ]);

        expect($product->exists)->toBeTrue();
    });

    it('consumable product does not require inventory account', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create([
                'type' => \Jmeryar\Product\Enums\Products\ProductType::Consumable,
                'default_inventory_account_id' => null,
            ]);

        expect($product->exists)->toBeTrue();
    });
});
