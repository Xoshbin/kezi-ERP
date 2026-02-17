<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    Sanctum::actingAs($this->user, ['*']);

    $this->profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
});

it('can sync an order with real tax amounts', function () {
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
                'order_number' => 'POS-TEST-001',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => '1150',
                'total_tax' => '150',
                'notes' => 'Test Order',
                'customer_id' => null,
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'sector_data' => [],
                'lines' => [
                    [
                        'product_id' => 1,
                        'quantity' => 1,
                        'unit_price' => '1000',
                        'tax_amount' => '150',
                        'total_amount' => '1150',
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    postJson('/api/pos/sync/orders', $payload)
        ->assertOk()
        ->assertJsonFragment(['synced' => [$uuid]]);

    assertDatabaseHas('pos_orders', [
        'uuid' => $uuid,
        'total_amount' => 1150,
        'total_tax' => 150,
        'pos_session_id' => $session->id,
    ]);

    assertDatabaseHas('pos_order_lines', [
        'unit_price' => 1000,
        'tax_amount' => 150,
        'total_amount' => 1150,
    ]);
});

it('can sync multiple orders in a single request', function () {
    $session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $this->profile->id,
        'status' => 'opened',
    ]);

    $uuid1 = Str::uuid()->toString();
    $uuid2 = Str::uuid()->toString();

    $payload = [
        'orders' => [
            [
                'uuid' => $uuid1,
                'order_number' => 'POS-TEST-BATCH-1',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => '500',
                'total_tax' => '0',
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => 1,
                        'quantity' => 1,
                        'unit_price' => '500',
                        'tax_amount' => '0',
                        'total_amount' => '500',
                        'metadata' => [],
                    ],
                ],
            ],
            [
                'uuid' => $uuid2,
                'order_number' => 'POS-TEST-BATCH-2',
                'status' => 'paid',
                'ordered_at' => now()->toIso8601String(),
                'total_amount' => '2300',
                'total_tax' => '300',
                'currency_id' => $this->currency->id,
                'pos_session_id' => $session->id,
                'lines' => [
                    [
                        'product_id' => 2,
                        'quantity' => 2,
                        'unit_price' => '1000',
                        'tax_amount' => '150',
                        'total_amount' => '2300',
                        'metadata' => [],
                    ],
                ],
            ],
        ],
    ];

    postJson('/api/pos/sync/orders', $payload)
        ->assertOk()
        ->assertJsonFragment(['synced' => [$uuid1, $uuid2]]);

    assertDatabaseHas('pos_orders', ['uuid' => $uuid1]);
    assertDatabaseHas('pos_orders', ['uuid' => $uuid2]);
});
