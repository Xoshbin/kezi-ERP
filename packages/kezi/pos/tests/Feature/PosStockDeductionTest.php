<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Enums\Accounting\JournalType;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Enums\Inventory\StockLocationType;
use Kezi\Inventory\Models\StockLocation;
use Kezi\Inventory\Models\StockQuant;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->user->update(['company_id' => $this->company->id]);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_order', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo('create_pos_order');

    Sanctum::actingAs($this->user, ['*']);

    // Create stock locations
    $this->warehouseLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'POS Warehouse',
        'type' => StockLocationType::Internal,
    ]);

    $this->customerLocation = StockLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Customers',
        'type' => StockLocationType::Customer,
    ]);

    // Create POS profile with stock location set
    $this->profile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'stock_location_id' => $this->warehouseLocation->id,
    ]);

    // Setup accounting
    $this->inventoryAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::CurrentAssets,
    ]);

    $this->cogsAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Expense,
    ]);

    $this->salesJournal = Journal::factory()->create([
        'company_id' => $this->company->id,
        'type' => JournalType::Sale,
    ]);

    $this->receivableAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'type' => AccountType::Receivable,
    ]);

    $this->company->update([
        'default_sales_journal_id' => $this->salesJournal->id,
        'default_accounts_receivable_id' => $this->receivableAccount->id,
        'default_stock_location_id' => $this->warehouseLocation->id,
    ]);
});

it('deducts stock when a POS order is synced', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => \Brick\Money\Money::of(5, 'USD'),
    ]);

    // Set initial stock
    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'location_id' => $this->warehouseLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    // Sync a POS order
    $session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $this->profile->id,
        'status' => 'opened',
    ]);

    $uuid = Str::uuid()->toString();
    $payload = [
        'orders' => [
            [
                'uuid' => $uuid,
                'order_number' => 'POS-STOCK-001',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => 1000,
                'total_tax' => 0,
                'discount_amount' => 0,
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 3,
                        'unit_price' => 333,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 999,
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    postJson('/api/pos/sync/orders', $payload)
        ->assertOk()
        ->assertJsonFragment(['synced' => [$uuid]]);

    // Verify order was created
    assertDatabaseHas('pos_orders', ['uuid' => $uuid]);

    // Verify stock was deducted
    assertDatabaseHas('stock_moves', [
        'move_type' => 'outgoing',
        'status' => 'done',
        'source_type' => \Kezi\Sales\Models\Invoice::class,
    ]);

    // Verify stock move product line
    assertDatabaseHas('stock_move_product_lines', [
        'product_id' => $product->id,
        'quantity' => 3,
        'from_location_id' => $this->warehouseLocation->id,
        'to_location_id' => $this->customerLocation->id,
    ]);

    // Verify quant was deducted (100 - 3 = 97)
    $quant = StockQuant::where('product_id', $product->id)
        ->where('location_id', $this->warehouseLocation->id)
        ->first();
    expect((float) $quant->quantity)->toBe(97.0);
});

it('syncs order successfully even without stock when strict mode is off', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => \Brick\Money\Money::of(5, 'USD'),
    ]);

    // No stock quant created — product has zero stock

    $session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $this->profile->id,
        'status' => 'opened',
    ]);

    $uuid = Str::uuid()->toString();
    $payload = [
        'orders' => [
            [
                'uuid' => $uuid,
                'order_number' => 'POS-NOSTOCK-001',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => 500,
                'total_tax' => 0,
                'discount_amount' => 0,
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 500,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 500,
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    // Should still sync even though stock is 0 (soft-fail by default)
    postJson('/api/pos/sync/orders', $payload)
        ->assertOk()
        ->assertJsonFragment(['synced' => [$uuid]]);

    assertDatabaseHas('pos_orders', ['uuid' => $uuid]);
});

it('skips stock deduction when POS profile has no stock location', function () {
    $profileWithoutLocation = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'stock_location_id' => null,
    ]);

    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
    ]);

    $session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $profileWithoutLocation->id,
        'status' => 'opened',
    ]);

    $uuid = Str::uuid()->toString();
    $payload = [
        'orders' => [
            [
                'uuid' => $uuid,
                'order_number' => 'POS-NOLOC-001',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => 500,
                'total_tax' => 0,
                'discount_amount' => 0,
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 500,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 500,
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    // Should sync but skip stock deduction
    postJson('/api/pos/sync/orders', $payload)
        ->assertOk()
        ->assertJsonFragment(['synced' => [$uuid]]);

    assertDatabaseHas('pos_orders', ['uuid' => $uuid]);

    // No stock move should exist
    expect(\Kezi\Inventory\Models\StockMove::where('source_type', \Kezi\Pos\Models\PosOrder::class)->count())->toBe(0);
});

it('does not duplicate stock deduction on idempotent order sync', function () {
    $product = Product::factory()->create([
        'company_id' => $this->company->id,
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $this->inventoryAccount->id,
        'default_cogs_account_id' => $this->cogsAccount->id,
        'average_cost' => \Brick\Money\Money::of(5, 'USD'),
    ]);

    StockQuant::factory()->create([
        'company_id' => $this->company->id,
        'product_id' => $product->id,
        'location_id' => $this->warehouseLocation->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
    ]);

    $session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $this->profile->id,
        'status' => 'opened',
    ]);

    $uuid = Str::uuid()->toString();
    $payload = [
        'orders' => [
            [
                'uuid' => $uuid,
                'order_number' => 'POS-IDEM-001',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => 500,
                'total_tax' => 0,
                'discount_amount' => 0,
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                        'unit_price' => 100,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'total_amount' => 500,
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    // First sync
    postJson('/api/pos/sync/orders', $payload)->assertOk();

    // Second sync (same UUID) — should be idempotent, no duplicate deduction
    postJson('/api/pos/sync/orders', $payload)->assertOk();

    // Quant deducted only once (100 - 5 = 95)
    $quant = StockQuant::where('product_id', $product->id)
        ->where('location_id', $this->warehouseLocation->id)
        ->first();
    expect((float) $quant->quantity)->toBe(95.0);

    // Only one stock move created
    expect(\Kezi\Inventory\Models\StockMove::where('source_type', \Kezi\Sales\Models\Invoice::class)->count())->toBe(1);
});
