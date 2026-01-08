<?php

namespace Modules\Manufacturing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('BillOfMaterial Model', function () {
    it('belongs to a product', function () {
        $product = Product::factory()->create(['company_id' => $this->company->id]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
        ]);

        expect($bom->product)->not->toBeNull();
        expect($bom->product->id)->toBe($product->id);
    });

    it('has lines relationship', function () {
        $bom = BillOfMaterial::factory()->create(['company_id' => $this->company->id]);

        expect($bom->lines)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    it('scopes active BOMs', function () {
        BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $activeBoms = BillOfMaterial::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        expect($activeBoms)->toHaveCount(1);
    });

    it('belongs to company', function () {
        $bom = BillOfMaterial::factory()->create(['company_id' => $this->company->id]);

        expect($bom->company)->not->toBeNull();
        expect($bom->company->id)->toBe($this->company->id);
    });
});
