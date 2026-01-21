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

    public function test_can_delete_template_without_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
        ]);

        $template->delete();
        $this->assertSoftDeleted($template);
    }

    public function test_cannot_delete_template_with_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
        ]);

        Product::factory()->create([
            'parent_product_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete template product with 1 existing variant(s)');

        $template->delete();
    }

    public function test_can_delete_after_removing_all_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
        ]);

        $variant = Product::factory()->create([
            'parent_product_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        // This should fail
        try {
            $template->delete();
            $this->fail('Should not be able to delete template with variant');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Cannot delete template product with 1 existing variant(s)', $e->getMessage());
        }

        // Delete variant first
        $variant->forceDelete();

        $template->delete();
        $this->assertSoftDeleted($template);
    }

    public function test_error_message_shows_correct_variant_count()
    {
        $template = Product::factory()->create([
            'is_template' => true,
        ]);

        Product::factory()->count(3)->create([
            'parent_product_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete template product with 3 existing variant(s)');

        $template->delete();
    }

    public function test_cannot_modify_attributes_of_template_with_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
        ]);

        Product::factory()->create([
            'parent_product_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        // Use SKU to trigger protection
        $template->sku = 'MODIFIED-SKU';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot modify attributes or critical fields of a template that has existing variants.');

        $template->save();
    }

    public function test_can_update_other_fields_of_template_with_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
            'name' => 'Old Name',
        ]);

        Product::factory()->create([
            'parent_product_id' => $template->id,
            'company_id' => $template->company_id,
        ]);

        $template->name = 'New Name';
        $template->save();

        $this->assertEquals('New Name', $template->fresh()->name);
    }

    public function test_can_modify_attributes_if_no_variants_exist()
    {
        $template = Product::factory()->create([
            'is_template' => true,
            'sku' => 'OLD-SKU',
        ]);
        $template->sku = 'NEW-SKU';
        $template->save();

        $this->assertEquals('NEW-SKU', $template->fresh()->sku);
    }

    public function test_regeneration_with_delete_existing_flag()
    {
        $template = Product::factory()->create(['is_template' => true, 'sku' => 'T1']);
        $size = ProductAttribute::factory()->create(['name' => 'Size', 'company_id' => $template->company_id]);
        $small = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'S']);

        // Create initial variant
        Product::factory()->create([
            'parent_product_id' => $template->id,
            'sku' => 'T1-S',
            'company_id' => $template->company_id,
        ]);

        $this->assertEquals(2, Product::count());

        $dto = new GenerateProductVariantsDTO(
            templateProductId: $template->id,
            attributeValueMap: [$size->id => [$small->id]],
            deleteExisting: true
        );

        $action = new GenerateProductVariantsAction;
        $action->execute($dto);

        $this->assertEquals(2, Product::count()); // 1 template + 1 regenerated variant
    }

    public function test_regeneration_skips_duplicates_if_not_deleting_existing()
    {
        $template = Product::factory()->create(['is_template' => true, 'sku' => 'T2']);
        $size = ProductAttribute::factory()->create(['name' => 'Size', 'company_id' => $template->company_id]);
        $small = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'S']);

        // Create initial variant
        Product::factory()->create([
            'parent_product_id' => $template->id,
            'sku' => 'T2-S',
            'company_id' => $template->company_id,
        ]);

        $dto = new GenerateProductVariantsDTO(
            templateProductId: $template->id,
            attributeValueMap: [$size->id => [$small->id]],
            deleteExisting: false
        );

        $action = new GenerateProductVariantsAction;
        $variants = $action->execute($dto);

        $this->assertCount(0, $variants); // Skipped because it already exists
        $this->assertEquals(2, Product::count());
    }

    public function test_cannot_regenerate_if_variants_have_transactions()
    {
        $template = Product::factory()->create(['is_template' => true, 'sku' => 'T3']);
        $variant = Product::factory()->create([
            'parent_product_id' => $template->id,
            'sku' => 'T3-S',
            'company_id' => $template->company_id,
        ]);

        // Mock a transaction (InvoiceLine)
        \Modules\Sales\Models\InvoiceLine::factory()->create([
            'product_id' => $variant->id,
        ]);

        $size = ProductAttribute::factory()->create(['name' => 'Size', 'company_id' => $template->company_id]);
        $small = ProductAttributeValue::factory()->create(['product_attribute_id' => $size->id, 'name' => 'S']);

        $dto = new GenerateProductVariantsDTO(
            templateProductId: $template->id,
            attributeValueMap: [$size->id => [$small->id]],
            deleteExisting: true
        );

        $action = new GenerateProductVariantsAction;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has existing transactions');

        $action->execute($dto);
    }

    public function test_template_price_syncs_to_variants_without_override()
    {
        $template = Product::factory()->create([
            'is_template' => true,
            'unit_price' => 100,
        ]);

        $variant = Product::factory()->create([
            'parent_product_id' => $template->id,
            'unit_price' => 100,
            'has_price_override' => false,
            'company_id' => $template->company_id,
        ]);

        $template->unit_price = 150;
        $template->save();

        $this->assertEquals(150, $variant->fresh()->unit_price->getAmount()->toInt());
    }

    public function test_template_price_does_not_sync_to_variants_with_override()
    {
        $template = Product::factory()->create([
            'is_template' => true,
            'unit_price' => 100,
        ]);

        $variant = Product::factory()->create([
            'parent_product_id' => $template->id,
            'unit_price' => 200,
            'has_price_override' => true,
            'company_id' => $template->company_id,
        ]);

        $template->unit_price = 150;
        $template->save();

        $this->assertEquals(200, $variant->fresh()->unit_price->getAmount()->toInt());
    }

    public function test_template_name_syncs_to_all_variants()
    {
        $template = Product::factory()->create([
            'is_template' => true,
            'name' => 'Old Template Name',
        ]);

        $variant1 = Product::factory()->create([
            'parent_product_id' => $template->id,
            'has_price_override' => false,
            'company_id' => $template->company_id,
        ]);

        $variant2 = Product::factory()->create([
            'parent_product_id' => $template->id,
            'has_price_override' => true,
            'company_id' => $template->company_id,
        ]);

        $template->name = 'New Template Name';
        $template->save();

        $this->assertEquals('New Template Name', $variant1->fresh()->name);
        $this->assertEquals('New Template Name', $variant2->fresh()->name);
    }
}
