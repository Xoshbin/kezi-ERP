<?php

namespace Modules\Manufacturing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Manufacturing\Enums\ManufacturingOrderStatus;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Models\ManufacturingOrder;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('ManufacturingOrder Model', function () {
    it('belongs to a BOM', function () {
        $bom = BillOfMaterial::factory()->create(['company_id' => $this->company->id]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
        ]);

        expect($mo->billOfMaterial)->not->toBeNull();
        expect($mo->billOfMaterial->id)->toBe($bom->id);
    });

    it('has lines relationship', function () {
        $mo = ManufacturingOrder::factory()->create(['company_id' => $this->company->id]);

        expect($mo->lines)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    it('can be created in draft status', function () {
        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'status' => ManufacturingOrderStatus::Draft,
        ]);

        expect($mo->status)->toBe(ManufacturingOrderStatus::Draft);
    });

    it('belongs to product', function () {
        $product = Product::factory()->create(['company_id' => $this->company->id]);
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
        ]);

        $mo = ManufacturingOrder::factory()->create([
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'product_id' => $product->id,
        ]);

        expect($mo->product)->not->toBeNull();
        expect($mo->product->id)->toBe($product->id);
    });
});
