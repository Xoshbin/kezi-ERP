<?php

namespace Jmeryar\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Product\Models\Product;
use Jmeryar\Product\Models\ProductAttribute;
use Jmeryar\Product\Models\ProductAttributeValue;
use Jmeryar\Product\Models\ProductVariantAttribute;
use Tests\TestCase;

class ProductAttributeValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_attribute_value_used_in_variant()
    {
        $attribute = ProductAttribute::factory()->create(['name' => 'Color']);
        $value = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'name' => 'Red',
        ]);

        $product = Product::factory()->create(['name' => 'Variant 1']);

        ProductVariantAttribute::create([
            'product_id' => $product->id,
            'product_attribute_id' => $attribute->id,
            'product_attribute_value_id' => $value->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot delete attribute value 'Red' because it is being used by one or more product variants.");

        $value->delete();
    }

    public function test_can_delete_attribute_value_not_used_in_variant()
    {
        $attribute = ProductAttribute::factory()->create(['name' => 'Color']);
        $value = ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'name' => 'Blue',
        ]);

        $value->delete();

        $this->assertDatabaseMissing('product_attribute_values', ['id' => $value->id]);
    }
}
