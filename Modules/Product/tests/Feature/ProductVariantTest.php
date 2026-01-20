<?php

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Actions\GenerateProductVariantsAction;
use Modules\Product\DataTransferObjects\GenerateProductVariantsDTO;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductAttribute;
use Modules\Product\Models\ProductAttributeValue;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_variants_from_template()
    {
        // 1. Setup Template Product
        $template = Product::factory()->create([
            'name' => 'T-Shirt',
            'sku' => 'TSHIRT',
            'is_template' => true,
        ]);

        // 2. Setup Attributes and Values
        $size = ProductAttribute::factory()->create(['name' => 'Size', 'company_id' => $template->company_id]);
        $small = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'S']);
        $large = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'L']);

        $color = ProductAttribute::factory()->create(['name' => 'Color', 'company_id' => $template->company_id]);
        $red = ProductAttributeValue::factory()->create(['product_attribute_id' => $color->id, 'name' => 'Red']);

        // 3. Prepare DTO
        $dto = new GenerateProductVariantsDTO(
            templateProductId: $template->id,
            attributeValueMap: [
                $size->id => [$small->id, $large->id],
                $color->id => [$red->id],
            ]
        );

        // 4. Execute Action
        $action = new GenerateProductVariantsAction;
        $variants = $action->execute($dto);

        // 5. Assertions
        $this->assertCount(2, $variants);

        $this->assertDatabaseHas('products', [
            'parent_product_id' => $template->id,
            'sku' => 'TSHIRT-S-RED',
        ]);

        $this->assertDatabaseHas('products', [
            'parent_product_id' => $template->id,
            'sku' => 'TSHIRT-L-RED',
        ]);

        // Verify relationships
        $variant1 = Product::where('sku', 'TSHIRT-S-RED')->first();
        $this->assertCount(2, $variant1->variantAttributes);
        $this->assertTrue($variant1->isVariant());
        $this->assertEquals('T-Shirt', $variant1->name); // Inherited name
    }

    public function test_cannot_generate_variants_for_non_template()
    {
        $product = Product::factory()->create(['is_template' => false]);

        $dto = new GenerateProductVariantsDTO(
            templateProductId: $product->id,
            attributeValueMap: []
        );

        $action = new GenerateProductVariantsAction;

        $this->expectException(\InvalidArgumentException::class);
        $action->execute($dto);
    }
}
