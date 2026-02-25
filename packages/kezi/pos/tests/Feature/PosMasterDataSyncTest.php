<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosProfile;
use Kezi\Product\Models\Product;
use Kezi\Product\Models\ProductCategory;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_order', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo('view_any_pos_order');

    Sanctum::actingAs($this->user, ['*']);
});

it('can fetch master data including products and categories', function () {
    // Create categories
    $category1 = ProductCategory::factory()->create(['name' => 'Hardware']);
    $category2 = ProductCategory::factory()->create(['name' => 'Software']);

    // Create products
    Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Laptop'],
        'sku' => 'LAP-001',
        'is_active' => true,
        // 'category_id' => $category1->id, // If we decide to add it
    ]);

    Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Office Suite'],
        'sku' => 'SOFT-001',
        'is_active' => true,
    ]);

    // Create POS Profile
    PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Main POS',
        'is_active' => true,
    ]);

    getJson('/api/pos/sync/master-data')
        ->assertOk()
        ->assertJsonStructure([
            'products',
            'categories',
            'taxes',
            'customers',
            'profiles',
            'currencies',
            'company_currency',
            'timestamp',
        ])
        ->assertJsonCount(2, 'products')
        ->assertJsonCount(1, 'profiles');
});

it('formats product is_active property strictly as an integer for indexeddb support', function () {
    Product::factory()->create([
        'company_id' => $this->company->id,
        'name' => ['en' => 'Test Item'],
        'sku' => 'TEST-001',
        'is_active' => true,
    ]);

    getJson('/api/pos/sync/master-data')
        ->assertOk()
        ->assertJsonPath('products.0.is_active', 1);
});
