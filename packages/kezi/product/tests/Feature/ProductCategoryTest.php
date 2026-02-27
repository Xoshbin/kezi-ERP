<?php

use Illuminate\Validation\ValidationException;
use Kezi\Product\Models\ProductCategory;

use function Pest\Laravel\assertDatabaseHas;

it('can create a category hierarchy', function () {
    $parent = ProductCategory::factory()->create(['name' => 'Parent']);
    $child = ProductCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id, 'company_id' => $parent->company_id]);
    $grandchild = ProductCategory::factory()->create(['name' => 'Grandchild', 'parent_id' => $child->id, 'company_id' => $parent->company_id]);

    expect($child->parent_id)->toBe($parent->id)
        ->and($grandchild->parent_id)->toBe($child->id)
        ->and($grandchild->parent->parent->id)->toBe($parent->id);
});

it('prevents a category from being its own parent', function () {
    $category = ProductCategory::factory()->create(['name' => 'Category']);

    try {
        $category->parent_id = $category->id;
        $category->save();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('parent_id');

        return;
    }

    $this->fail('Should have thrown ValidationException');
});

it('prevents circular dependencies', function () {
    $parent = ProductCategory::factory()->create(['name' => 'Parent']);
    $child = ProductCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id, 'company_id' => $parent->company_id]);
    $grandchild = ProductCategory::factory()->create(['name' => 'Grandchild', 'parent_id' => $child->id, 'company_id' => $parent->company_id]);

    // Try to make Parent a child of Grandchild
    try {
        $parent->parent_id = $grandchild->id;
        $parent->save();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('parent_id')
            ->and($e->getMessage())->toContain('Circular dependency');

        return;
    }

    $this->fail('Should have thrown ValidationException for circular dependency');
});

it('prevents deletion of category with children', function () {
    $parent = ProductCategory::factory()->create(['name' => 'Parent']);
    ProductCategory::factory()->create(['name' => 'Child', 'parent_id' => $parent->id, 'company_id' => $parent->company_id]);

    try {
        $parent->delete();
    } catch (ValidationException $e) {
        expect($e->getMessage())->toContain('Cannot delete');
        assertDatabaseHas('product_categories', ['id' => $parent->id]);

        return;
    } catch (\Illuminate\Database\QueryException $e) {
        // Fallback checks
        expect($e->getMessage())->toContain('constraint');
        assertDatabaseHas('product_categories', ['id' => $parent->id]);

        return;
    }

    $this->fail('Should have prevented deletion');
});

it('can reparent a category', function () {
    $oldParent = ProductCategory::factory()->create(['name' => 'Old Parent']);
    $newParent = ProductCategory::factory()->create(['name' => 'New Parent', 'company_id' => $oldParent->company_id]);
    $child = ProductCategory::factory()->create(['name' => 'Child', 'parent_id' => $oldParent->id, 'company_id' => $oldParent->company_id]);

    $child->update(['parent_id' => $newParent->id]);

    expect($child->fresh()->parent_id)->toBe($newParent->id);
});
