<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

describe('SKU Uniqueness Within Company', function () {
    it('allows unique SKU within company', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'UNIQUE-SKU-001']);

        expect($product->exists)->toBeTrue();
        expect($product->sku)->toBe('UNIQUE-SKU-001');
    });

    it('prevents duplicate SKU within same company', function () {
        Product::factory()
            ->for($this->company)
            ->create(['sku' => 'DUPLICATE-SKU']);

        // The factory handles company_id, so we need to force it
        expect(fn () => Product::create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Another Product'],
            'sku' => 'DUPLICATE-SKU',
            'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
            'is_active' => true,
        ]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows same SKU in different companies', function () {
        $company2 = \App\Models\Company::factory()->create();

        $product1 = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'SAME-SKU']);

        $product2 = Product::factory()
            ->for($company2)
            ->create(['sku' => 'SAME-SKU']);

        expect($product1->exists)->toBeTrue();
        expect($product2->exists)->toBeTrue();
        expect($product1->sku)->toBe($product2->sku);
        expect($product1->company_id)->not->toBe($product2->company_id);
    });
});

describe('SKU Scope Query', function () {
    it('finds product by SKU within company using scope', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'FIND-ME-SKU']);

        $found = Product::bySku('FIND-ME-SKU', $this->company->id)->first();

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($product->id);
    });

    it('does not find product with different company using scope', function () {
        $company2 = \App\Models\Company::factory()->create();

        Product::factory()
            ->for($company2)
            ->create(['sku' => 'OTHER-COMPANY-SKU']);

        $found = Product::bySku('OTHER-COMPANY-SKU', $this->company->id)->first();

        expect($found)->toBeNull();
    });

    it('returns null for non-existent SKU', function () {
        $found = Product::bySku('NON-EXISTENT-SKU', $this->company->id)->first();

        expect($found)->toBeNull();
    });
});

describe('SKU Format Validation', function () {
    it('allows alphanumeric SKU with dashes', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'PROD-001-A']);

        expect($product->sku)->toBe('PROD-001-A');
    });

    it('allows alphanumeric SKU with underscores', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'PROD_001_A']);

        expect($product->sku)->toBe('PROD_001_A');
    });

    it('allows numeric only SKU', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => '12345678']);

        expect($product->sku)->toBe('12345678');
    });

    it('preserves SKU case', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'MixedCase-SKU']);

        expect($product->sku)->toBe('MixedCase-SKU');
    });
});

describe('Variant SKU Generation', function () {
    it('generates variant SKU from template SKU', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'sku' => 'TEMPLATE',
                'is_template' => true,
            ]);

        $variant = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TEMPLATE-S-RED',
                'variant_sku_suffix' => 'S-RED',
            ]);

        expect($variant->sku)->toBe('TEMPLATE-S-RED');
        expect($variant->variant_sku_suffix)->toBe('S-RED');
    });

    it('each variant has unique SKU', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'sku' => 'TSHIRT',
                'is_template' => true,
            ]);

        $variant1 = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TSHIRT-S',
            ]);

        $variant2 = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TSHIRT-M',
            ]);

        $variant3 = Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TSHIRT-L',
            ]);

        expect($variant1->sku)->not->toBe($variant2->sku);
        expect($variant2->sku)->not->toBe($variant3->sku);
        expect($variant1->sku)->not->toBe($variant3->sku);
    });

    it('variant SKU cannot duplicate existing SKU', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'sku' => 'TEMPLATE',
                'is_template' => true,
            ]);

        Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TEMPLATE-S',
            ]);

        // Try to create another variant with same SKU
        expect(fn () => Product::create([
            'company_id' => $this->company->id,
            'parent_product_id' => $template->id,
            'name' => ['en' => 'Duplicate Variant'],
            'sku' => 'TEMPLATE-S',
            'type' => \Jmeryar\Product\Enums\Products\ProductType::Service,
            'is_active' => true,
        ]))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });
});

describe('SKU Update Constraints', function () {
    it('can update SKU of product without transactions', function () {
        $product = Product::factory()
            ->for($this->company)
            ->create(['sku' => 'OLD-SKU']);

        $product->update(['sku' => 'NEW-SKU']);

        expect($product->fresh()->sku)->toBe('NEW-SKU');
    });

    it('cannot update SKU of template with variants', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'sku' => 'TEMPLATE-SKU',
                'is_template' => true,
            ]);

        Product::factory()
            ->for($this->company)
            ->create([
                'parent_product_id' => $template->id,
                'sku' => 'TEMPLATE-SKU-S',
            ]);

        $template->sku = 'MODIFIED-TEMPLATE-SKU';

        expect(fn () => $template->save())
            ->toThrow(\RuntimeException::class, 'Cannot modify attributes');
    });

    it('can update SKU of template without variants', function () {
        $template = Product::factory()
            ->for($this->company)
            ->create([
                'sku' => 'TEMPLATE-SKU',
                'is_template' => true,
            ]);

        $template->update(['sku' => 'NEW-TEMPLATE-SKU']);

        expect($template->fresh()->sku)->toBe('NEW-TEMPLATE-SKU');
    });
});

describe('SKU Search', function () {
    it('sku is included in non-translatable search fields', function () {
        $product = new Product;
        $searchFields = $product->getNonTranslatableSearchFields();

        expect($searchFields)->toContain('sku');
    });

    it('can find product by partial SKU match', function () {
        Product::factory()
            ->for($this->company)
            ->create(['sku' => 'PRODUCT-ABC-123']);

        Product::factory()
            ->for($this->company)
            ->create(['sku' => 'PRODUCT-XYZ-456']);

        $results = Product::where('sku', 'like', '%ABC%')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->sku)->toBe('PRODUCT-ABC-123');
    });
});
