<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\ProductResource;
use Modules\Inventory\Filament\Clusters\Inventory\Resources\Products\Pages\EditProduct;
use Modules\Product\Enums\Products\ProductType;
use Modules\Product\Models\Product;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // 1. Create company FIRST so seeder can find it and create roles for it
    $this->company = \App\Models\Company::factory()->create();

    // 2. Run Seeder
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('hides cost fields from users without permission', function () {
    // 1. Create a user with 'employee' role (who does NOT have view_cost_product permission)
    $employeeUser = User::factory()->create([
        'email' => 'employee@test.com',
    ]);

    // Assign role in the context of the company
    $employeeRole = Role::where('name', 'employee')->where('company_id', $this->company->id)->first();

    // Ensure role exists (sanity check)
    expect($employeeRole)->not->toBeNull();

    // Use Spatie method with team/company
    setPermissionsTeamId($this->company->id);
    $employeeUser->assignRole($employeeRole);

    // 2. Create a Storable product (where cost is relevant)
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'average_cost' => 1000, // 10.00
    ]);

    // 3. Authenticate FIRST
    actingAs($employeeUser);

    // THEN Set Tenant (requires authenticated user)
    Filament::setTenant($this->company);

    // Filament testing helper to check form field visibility
    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
    ->assertFormFieldHidden('average_cost');
});

it('shows cost fields to users with permission', function () {
    // 1. Create a user with 'inventory_manager' role (who HAS view_cost_product permission)
    $managerUser = User::factory()->create([
        'email' => 'manager@test.com',
    ]);

    $managerRole = Role::where('name', 'inventory_manager')->where('company_id', $this->company->id)->first();

    // Ensure role exists
    expect($managerRole)->not->toBeNull();

    setPermissionsTeamId($this->company->id);
    $managerUser->assignRole($managerRole);

    // 2. Create a Storable product
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'average_cost' => 1000,
    ]);

    // 3. Authenticate FIRST
    actingAs($managerUser);

    // THEN Set Tenant
    Filament::setTenant($this->company);

    Livewire::test(EditProduct::class, [
        'record' => $product->getRouteKey(),
    ])
    ->assertFormFieldVisible('average_cost');
});
