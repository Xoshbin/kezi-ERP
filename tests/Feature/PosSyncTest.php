<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Kezi\Product\Models\Product;
use Kezi\Product\Models\ProductCategory;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup basic data
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_order', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_order', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo(['create_pos_order', 'create_pos_session', 'view_any_pos_session', 'view_any_pos_order']);

    Sanctum::actingAs($this->user, ['*']);
});

test('can fetch master data', function () {
    // Create seed data
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $category = ProductCategory::factory()->create(); // Assuming global
    $product = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'TEST-001', 'is_active' => true]);

    $response = $this->getJson('/api/pos/sync/master-data');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'products',
            'categories',
            'taxes',
            'customers',
            'profiles',
        ]);

    // Check content
    $products = $response->json('products');
    expect($products)->not->toBeEmpty();
    $firstProduct = $products[0];

    expect($firstProduct['id'])->toBe($product->id)
        ->and($firstProduct['name'])->toBeString() // Ensure translation is resolved to string
        ->and($firstProduct['unit_price'])->toBeInt()
        ->and($firstProduct['tax_ids'])->toBeArray();

    $currencyCode = $this->company->currency->code;

    if (isset($firstProduct['currency_code'])) {
        expect($firstProduct['currency_code'])->toBe($currencyCode);
    }

    // Check company currency
    expect($response->json('company_currency'))->not->toBeNull();
    expect($response->json('company_currency.code'))->toBe($currencyCode);

    // Check company scoping (create another company's product)
    $otherCompany = Company::factory()->create();
    $otherProduct = Product::factory()->create(['company_id' => $otherCompany->id, 'sku' => 'OTHER-001']);

    $response2 = $this->getJson('/api/pos/sync/master-data');
    $ids = collect($response2->json('products'))->pluck('id');
    expect($ids)->not->toContain($otherProduct->id);
});

test('can sync orders', function () {
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create(['pos_profile_id' => $profile->id, 'user_id' => $this->user->id]);
    $customer = Partner::factory()->create();
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $product = Product::factory()->create(['company_id' => $this->company->id]);

    $uuid = Str::uuid()->toString();

    $orderData = [
        'uuid' => $uuid,
        'order_number' => 'ORD-TEST-1',
        'status' => 'paid',
        'ordered_at' => now()->toIso8601String(),
        'total_amount' => '2500', // 25.00
        'total_tax' => '200', // 2.00
        'customer_id' => $customer->id,
        'currency_id' => $currency->id,
        'pos_session_id' => $session->id,
        'sector_data' => [],
        'notes' => 'Test order',
        'lines' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => '1000', // 10.00
                'tax_amount' => '100', // 1.00
                'total_amount' => '2200', // (10+1)*2 = 22.00 -> 2200
                'metadata' => [],
            ],
        ],
    ];

    $response = $this->postJson('/api/pos/sync/orders', ['orders' => [$orderData]]);

    $response->assertStatus(200)
        ->assertJsonFragment(['synced' => [$uuid]]);

    $this->assertDatabaseHas('pos_orders', [
        'uuid' => $uuid,
        'company_id' => $this->company->id,
        'total_amount' => 2500,
    ]);

    $this->assertDatabaseHas('pos_order_lines', [
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 1000,
    ]);
});

test('idempotent order sync', function () {
    $uuid = Str::uuid()->toString();
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create(['pos_profile_id' => $profile->id, 'user_id' => $this->user->id]);

    // Minimal valid order data
    $orderData = [
        'uuid' => $uuid,
        'order_number' => 'ORD-IDEM-1',
        'status' => 'paid',
        'ordered_at' => now()->toIso8601String(),
        'total_amount' => '0',
        'total_tax' => '0',
        'customer_id' => Partner::factory()->create()->id,
        'currency_id' => $this->company->currency_id,
        'pos_session_id' => $session->id,
        'sector_data' => [],
        'lines' => [],
    ];

    // First sync
    $this->postJson('/api/pos/sync/orders', ['orders' => [$orderData]])
        ->assertStatus(200)
        ->assertJsonFragment(['synced' => [$uuid]]);

    // Second sync
    $this->postJson('/api/pos/sync/orders', ['orders' => [$orderData]])
        ->assertStatus(200)
        ->assertJsonFragment(['synced' => [$uuid]]);

    // Count should be 1
    expect(PosOrder::where('uuid', $uuid)->count())->toBe(1);
});

test('session management flow', function () {
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);

    // Open Session
    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 10000, // 100.00
    ]);

    $response->assertStatus(201);
    $sessionId = $response->json('session.id');

    $this->assertDatabaseHas('pos_sessions', [
        'id' => $sessionId,
        'status' => 'opened',
        'opening_cash' => 10000,
    ]);

    // Get Current
    $this->getJson('/api/pos/sessions/current')
        ->assertStatus(200)
        ->assertJsonFragment(['id' => $sessionId]);

    // Close Session
    $this->postJson("/api/pos/sessions/{$sessionId}/close", [
        'closing_cash' => 15000,
    ])->assertStatus(200);

    $this->assertDatabaseHas('pos_sessions', [
        'id' => $sessionId,
        'status' => 'closed',
        'closing_cash' => 15000,
    ]);
});
