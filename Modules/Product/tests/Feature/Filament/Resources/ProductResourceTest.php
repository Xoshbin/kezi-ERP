<?php

use App\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use App\Filament\Clusters\Inventory\Resources\Products\Pages\EditProduct;
use App\Filament\Clusters\Inventory\Resources\Products\Pages\ListProducts;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $products = \Modules\Product\Models\Product::factory()->count(5)->create();

    livewire(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords($products);
});

it('can render the create page', function () {
    livewire(CreateProduct::class)
        ->assertOk();
});

it('can create a product', function () {
    $newProductData = \Modules\Product\Models\Product::factory()->make();

    // Required accounts for storable products
    $incomeAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();
    $expenseAccount = \Modules\Accounting\Models\Account::factory()->for($this->company)->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => $newProductData->name,
            'sku' => $newProductData->sku,
            'type' => $newProductData->type,
            'description' => $newProductData->description,
            'income_account_id' => $incomeAccount->id,
            'expense_account_id' => $expenseAccount->id,
            'default_inventory_account_id' => $expenseAccount->id,
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(\Modules\Product\Models\Product::class, [
        'name' => json_encode(['en' => $newProductData->name]),
        'sku' => $newProductData->sku,
    ]);
});

it('can render the edit page', function () {
    $product = \Modules\Product\Models\Product::factory()->create();

    livewire(EditProduct::class, [
        'record' => $product->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $product->name,
            'email' => $product->email,
        ]);
});

it('can update a product', function () {
    $product = \Modules\Product\Models\Product::factory()->create();

    $newProductData = \Modules\Product\Models\Product::factory()->make();

    livewire(EditProduct::class, [
        'record' => $product->id,
    ])
        ->fillForm([
            'name' => $newProductData->name,
            'sku' => $newProductData->sku,
        ])
        ->call('save')
        ->assertNotified();

    assertDatabaseHas(\Modules\Product\Models\Product::class, [
        'id' => $product->id,
        'name' => json_encode(['en' => $newProductData->name, 'ar' => '']),
        'sku' => $newProductData->sku,
    ]);
});

it('can delete a product', function () {
    $product = \Modules\Product\Models\Product::factory()->create();

    livewire(EditProduct::class, [
        'record' => $product->id,
    ])
        ->callAction(DeleteAction::class) // This opens the confirmation modal
        ->callMountedAction()            // This clicks "Confirm"
        ->assertNotified()
        ->assertRedirect();

    assertSoftDeleted($product);

    //    assertDatabaseMissing($product);
});
