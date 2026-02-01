<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Kezi\Manufacturing\Enums\BOMType;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages\CreateBillOfMaterial;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages\EditBillOfMaterial;
use Kezi\Manufacturing\Filament\Clusters\Manufacturing\Resources\BillOfMaterialResource\Pages\ListBillOfMaterials;
use Kezi\Manufacturing\Models\BillOfMaterial;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

describe('BillOfMaterialResource', function () {
    it('can render the list page', function () {
        $this->get(BillOfMaterialResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        $this->get(BillOfMaterialResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->get(BillOfMaterialResource::getUrl('edit', ['record' => $bom]))
            ->assertSuccessful();
    });

    it('can list BOMs', function () {
        $bom1 = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-TEST-001',
        ]);

        $bom2 = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-TEST-002',
        ]);

        Livewire::test(ListBillOfMaterials::class)
            ->assertCanSeeTableRecords([$bom1, $bom2])
            ->assertSee('BOM-TEST-001')
            ->assertSee('BOM-TEST-002');
    });

    it('can create a BOM', function () {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Finished Product'],
            'type' => ProductType::Storable,
        ]);

        Livewire::test(CreateBillOfMaterial::class)
            ->fillForm([
                'product_id' => $product->id,
                'code' => 'BOM-NEW-001',
                'name' => 'New BOM for Testing',
                'type' => BOMType::Normal->value,
                'quantity' => 1.0,
                'is_active' => true,
                'notes' => 'Test notes',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('bills_of_materials', [
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'code' => 'BOM-NEW-001',
        ]);
    });

    it('validates required fields on create', function () {
        Livewire::test(CreateBillOfMaterial::class)
            ->fillForm([
                'product_id' => null,
                'code' => '',
                'name' => '',
                'type' => null,
                'quantity' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'product_id' => 'required',
                'code' => 'required',
                'name' => 'required',
                'type' => 'required',
                'quantity' => 'required',
            ]);
    });

    it('validates unique code on create', function () {
        $product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-DUPLICATE',
        ]);

        Livewire::test(CreateBillOfMaterial::class)
            ->fillForm([
                'product_id' => $product->id,
                'code' => 'BOM-DUPLICATE',
                'name' => 'Test BOM',
                'type' => BOMType::Normal->value,
                'quantity' => 1.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);
    });

    it('can edit a BOM', function () {
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-ORIGINAL',
            'name' => ['en' => 'Original Name'],
        ]);

        Livewire::test(EditBillOfMaterial::class, ['record' => $bom->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'notes' => 'Updated notes',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $bom->refresh();
        expect($bom->name)->toBe('Updated Name');
        expect($bom->notes)->toBe('Updated notes');
    });

    it('can delete a BOM', function () {
        $bom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
        ]);

        Livewire::test(EditBillOfMaterial::class, ['record' => $bom->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('bills_of_materials', [
            'id' => $bom->id,
        ]);
    });

    it('can filter BOMs by type', function () {
        $normalBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'type' => BOMType::Normal,
            'code' => 'BOM-NORMAL-001',
        ]);

        $kitBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'type' => BOMType::Kit,
            'code' => 'BOM-KIT-001',
        ]);

        Livewire::test(ListBillOfMaterials::class)
            ->filterTable('type', BOMType::Normal->value)
            ->assertCanSeeTableRecords([$normalBom])
            ->assertCanNotSeeTableRecords([$kitBom]);
    });

    it('can filter BOMs by active status', function () {
        $activeBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'code' => 'BOM-ACTIVE-001',
        ]);

        $inactiveBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
            'code' => 'BOM-INACTIVE-001',
        ]);

        Livewire::test(ListBillOfMaterials::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeBom])
            ->assertCanNotSeeTableRecords([$inactiveBom]);
    });

    it('can search BOMs by code', function () {
        $searchableBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-SEARCH-TARGET',
        ]);

        $otherBom = BillOfMaterial::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'BOM-OTHER-001',
        ]);

        Livewire::test(ListBillOfMaterials::class)
            ->searchTable('SEARCH-TARGET')
            ->assertCanSeeTableRecords([$searchableBom])
            ->assertCanNotSeeTableRecords([$otherBom]);
    });
});
