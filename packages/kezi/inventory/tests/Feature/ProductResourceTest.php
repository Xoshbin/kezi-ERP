<?php

namespace Kezi\Inventory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Product\Models\Product;
use Kezi\Product\Models\ProductAttribute;
use Kezi\Product\Models\ProductAttributeValue;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Traits\WithConfiguredCompany;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupWithConfiguredCompany();
    }

    public function test_can_generate_variants_via_filament_action()
    {
        $template = Product::factory()->create([
            'name' => 'T-Shirt',
            'sku' => 'TSHIRT',
            'is_template' => true,
            'company_id' => $this->company->id,
        ]);
        $template->update(['is_template' => true]);

        // 2. Setup Attributes and Values
        $size = ProductAttribute::factory()->create(['name' => 'Size', 'company_id' => $template->company_id]);
        $small = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'S']);
        $large = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'L']);

        // 3. Test Filament Action
        Livewire::test(\Kezi\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\EditProduct::class, [
            'record' => $template->id,
        ])
            ->fillForm([
                'is_template' => true,
                'product_attributes' => [
                    [
                        'product_attribute_id' => $size->id,
                        'values' => [$small->id, $large->id],
                    ],
                ],
            ])
            ->callAction('generate_variants', data: [
                'selected_variants' => ['0', '1'],
                'delete_existing' => false,
            ])
            ->assertHasNoActionErrors();

        // 4. Assertions
        $this->assertDatabaseHas('products', [
            'parent_product_id' => $template->id,
            'sku' => 'TSHIRT-S',
        ]);

        $this->assertDatabaseHas('products', [
            'parent_product_id' => $template->id,
            'sku' => 'TSHIRT-L',
        ]);
    }
}
