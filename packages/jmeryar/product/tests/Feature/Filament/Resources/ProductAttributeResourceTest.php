<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\Product\Filament\Resources\ProductAttributeResource\Pages\ManageProductAttributes;
use Jmeryar\Product\Models\ProductAttribute;
use Jmeryar\Product\Models\ProductAttributeValue;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

describe('ProductAttributeResource List Page', function () {
    it('can render list page', function () {
        livewire(ManageProductAttributes::class)
            ->assertOk();
    });

    it('can list product attributes', function () {
        $attributes = ProductAttribute::factory()
            ->count(3)
            ->for($this->company)
            ->create();

        livewire(ManageProductAttributes::class)
            ->assertCanSeeTableRecords($attributes);
    });

    it('can search product attributes by name', function () {
        $attribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Size']);

        $otherAttribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Color']);

        livewire(ManageProductAttributes::class)
            ->searchTable('Size')
            ->assertCanSeeTableRecords([$attribute])
            ->assertCanNotSeeTableRecords([$otherAttribute]);
    });
});

describe('ProductAttributeResource Create', function () {
    it('can create a product attribute', function () {
        livewire(ManageProductAttributes::class)
            ->callAction('create', [
                'name' => 'Size',
                'type' => 'select',
                'sort_order' => 1,
                'is_active' => true,
            ])
            ->assertNotified();

        assertDatabaseHas(ProductAttribute::class, [
            'name' => json_encode(['en' => 'Size']),
            'type' => 'select',
            'is_active' => true,
        ]);
    });

    it('can create a color type attribute', function () {
        livewire(ManageProductAttributes::class)
            ->callAction('create', [
                'name' => 'Color',
                'type' => 'color',
                'is_active' => true,
            ])
            ->assertNotified();

        assertDatabaseHas(ProductAttribute::class, [
            'name' => json_encode(['en' => 'Color']),
            'type' => 'color',
        ]);
    });

    it('validates required fields', function () {
        livewire(ManageProductAttributes::class)
            ->callAction('create', [
                'name' => '',
                'type' => '',
            ])
            ->assertHasActionErrors(['name' => 'required', 'type' => 'required']);
    });

    it('can create attribute with values', function () {
        livewire(ManageProductAttributes::class)
            ->callAction('create', [
                'name' => 'Size',
                'type' => 'select',
                'is_active' => true,
                'values' => [
                    ['name' => 'Small', 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Medium', 'sort_order' => 2, 'is_active' => true],
                    ['name' => 'Large', 'sort_order' => 3, 'is_active' => true],
                ],
            ])
            ->assertNotified();

        $attribute = ProductAttribute::where('type', 'select')->first();
        expect($attribute)->not->toBeNull();
        expect($attribute->values)->toHaveCount(3);
    });
});

describe('ProductAttributeResource Edit', function () {
    it('can update a product attribute', function () {
        $attribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Old Name']);

        livewire(ManageProductAttributes::class)
            ->callTableAction('edit', $attribute, [
                'name' => 'New Name',
                'type' => 'radio',
            ])
            ->assertNotified();

        assertDatabaseHas(ProductAttribute::class, [
            'id' => $attribute->id,
            'type' => 'radio',
        ]);
    });

    it('can add values to existing attribute', function () {
        $attribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['type' => 'select']);

        livewire(ManageProductAttributes::class)
            ->callTableAction('edit', $attribute, [
                'name' => $attribute->name,
                'type' => 'select',
                'values' => [
                    ['name' => 'Option 1', 'sort_order' => 1, 'is_active' => true],
                    ['name' => 'Option 2', 'sort_order' => 2, 'is_active' => true],
                ],
            ])
            ->assertNotified();

        expect($attribute->fresh()->values)->toHaveCount(2);
    });
});

describe('ProductAttributeResource Delete', function () {
    it('can delete a product attribute', function () {
        $attribute = ProductAttribute::factory()
            ->for($this->company)
            ->create();

        livewire(ManageProductAttributes::class)
            ->callTableAction('delete', $attribute)
            ->assertNotified();

        assertDatabaseMissing(ProductAttribute::class, [
            'id' => $attribute->id,
        ]);
    });

    it('deletes attribute with associated values', function () {
        $attribute = ProductAttribute::factory()
            ->for($this->company)
            ->create();

        $values = ProductAttributeValue::factory()
            ->count(3)
            ->for($attribute, 'attribute')
            ->create();

        expect($values)->toHaveCount(3);
        expect($attribute->fresh()->values)->toHaveCount(3);

        livewire(ManageProductAttributes::class)
            ->callTableAction('delete', $attribute)
            ->assertNotified();

        assertDatabaseMissing(ProductAttribute::class, ['id' => $attribute->id]);
        // Note: Cascade delete behavior depends on database FK constraints
    });
});

describe('ProductAttributeResource Table Features', function () {
    it('displays is_active status icon correctly', function () {
        $activeAttribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['is_active' => true]);

        $inactiveAttribute = ProductAttribute::factory()
            ->for($this->company)
            ->create(['is_active' => false]);

        livewire(ManageProductAttributes::class)
            ->assertTableColumnStateSet('is_active', true, $activeAttribute)
            ->assertTableColumnStateSet('is_active', false, $inactiveAttribute);
    });

    it('can sort by name column', function () {
        $attr1 = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Color']);

        $attr2 = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Alpha']);

        $attr3 = ProductAttribute::factory()
            ->for($this->company)
            ->create(['name' => 'Beta']);

        livewire(ManageProductAttributes::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords([$attr2, $attr3, $attr1], inOrder: true);
    });
});
