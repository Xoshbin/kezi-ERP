<?php

namespace Modules\Manufacturing\Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Manufacturing\Models\BillOfMaterial;
use Modules\Manufacturing\Services\BOMService;
use Modules\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->service = app(BOMService::class);
});

describe('BOM Costing Edge Cases', function () {
    it('calculates recursive cost for multi-level BOMs', function () {
        $currencyCode = $this->company->currency->code;

        // Level 3: Child component
        $childProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'average_cost' => 10, // 10 units
        ]);

        // Level 2: Parent component (BOM for Parent)
        $parentProduct = Product::factory()->create(['company_id' => $this->company->id]);
        $parentBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $parentProduct->id,
            'quantity' => 1,
        ]);

        // Parent consumes 5 Child
        $parentBom->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $childProduct->id,
            'quantity' => 5,
            'unit_cost' => 0, // Should be overridden by child product's cost or its BOM
            'currency_code' => $currencyCode,
        ]);

        // Level 1: Grandparent (Top-level BOM)
        $grandparentProduct = Product::factory()->create(['company_id' => $this->company->id]);
        $grandparentBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $grandparentProduct->id,
            'quantity' => 1,
        ]);

        // Grandparent consumes 2 Parent
        $grandparentBom->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $parentProduct->id,
            'quantity' => 2,
            'unit_cost' => 0, // Should be overridden by recursive calculation
            'currency_code' => $currencyCode,
        ]);

        // Expected Cost:
        // Child unit cost = 10 (average cost)
        // Parent cost (for 1 unit) = 5 * 10 = 50
        // Grandparent cost (for 1 unit) = 2 * 50 = 100

        $totalCost = $this->service->calculateTotalMaterialCost($grandparentBom->fresh(['lines.product']));

        expect($totalCost->getAmount()->toFloat())->toBe(100.0);
    });

    it('falls back to product average cost when no BOM exists', function () {
        $currencyCode = $this->company->currency->code;

        $component = Product::factory()->create([
            'company_id' => $this->company->id,
            'average_cost' => 25,
        ]);

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'quantity' => 1,
        ]);

        $bom->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $component->id,
            'quantity' => 4,
            'unit_cost' => 0,
            'currency_code' => $currencyCode,
        ]);

        // Expected Cost: 4 * 25 = 100
        $totalCost = $this->service->calculateTotalMaterialCost($bom->fresh(['lines.product']));

        expect($totalCost->getAmount()->toFloat())->toBe(100.0);
    });

    it('handles zero values gracefully', function () {
        $currencyCode = $this->company->currency->code;

        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'quantity' => 1,
        ]);

        // Line with 0 quantity
        $bom->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id])->id,
            'quantity' => 0,
            'unit_cost' => 100,
            'currency_code' => $currencyCode,
        ]);

        // Line with 0 cost
        $bom->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => Product::factory()->create(['company_id' => $this->company->id, 'average_cost' => 0])->id,
            'quantity' => 10,
            'unit_cost' => 0,
            'currency_code' => $currencyCode,
        ]);

        $totalCost = $this->service->calculateTotalMaterialCost($bom->fresh(['lines.product']));

        expect($totalCost->getAmount()->toFloat())->toBe(0.0);
    });

    it('detects circular dependencies and throws exception', function () {
        $currencyCode = $this->company->currency->code;

        $productA = Product::factory()->create(['company_id' => $this->company->id]);
        $productB = Product::factory()->create(['company_id' => $this->company->id]);

        $bomA = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $productA->id,
            'quantity' => 1,
        ]);

        $bomB = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $productB->id,
            'quantity' => 1,
        ]);

        // A consumes B
        $bomA->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_cost' => 0,
            'currency_code' => $currencyCode,
        ]);

        // B consumes A
        $bomB->lines()->create([
            'company_id' => $this->company->id,
            'product_id' => $productA->id,
            'quantity' => 1,
            'unit_cost' => 0,
            'currency_code' => $currencyCode,
        ]);

        expect(fn () => $this->service->calculateTotalMaterialCost($bomA->fresh(['lines.product'])))
            ->toThrow(\RuntimeException::class, 'Circular BOM dependency detected');
    });
});
