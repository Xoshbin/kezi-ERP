<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

/** Returns a valid, minimal order payload array. */
function validOrderPayload(int $sessionId, int $currencyId, ?string $uuid = null): array
{
    return [
        'uuid' => $uuid ?? Str::uuid()->toString(),
        'order_number' => 'POS-BATCH-'.Str::random(6),
        'status' => 'paid',
        'ordered_at' => now()->toIso8601String(),
        'total_amount' => '1000',
        'total_tax' => '0',
        'currency_id' => $currencyId,
        'pos_session_id' => $sessionId,
        'lines' => [
            [
                'product_id' => 1,
                'quantity' => 1,
                'unit_price' => '1000',
                'tax_amount' => '0',
                'total_amount' => '1000',
                'metadata' => [],
            ],
        ],
    ];
}

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    Sanctum::actingAs($this->user, ['*']);

    $this->profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $this->session = PosSession::factory()->create([
        'user_id' => $this->user->id,
        'pos_profile_id' => $this->profile->id,
        'company_id' => $this->company->id,
        'status' => 'opened',
    ]);
});

it('accepts a single batch of up to 50 orders and persists them all', function () {
    // Simulate a batch of 5 orders (representative of the 50-order chunk size).
    $orders = collect(range(1, 5))->map(
        fn () => validOrderPayload($this->session->id, $this->currency->id)
    )->all();

    $uuids = array_column($orders, 'uuid');

    postJson('/api/pos/sync/orders', ['orders' => $orders])
        ->assertOk()
        ->assertJsonPath('failed', [])
        ->assertJson(['synced' => $uuids]);

    foreach ($uuids as $uuid) {
        assertDatabaseHas('pos_orders', ['uuid' => $uuid]);
    }
});

it('isolates a malformed order and still syncs the valid ones in the same batch', function () {
    $goodUuid = Str::uuid()->toString();
    $badUuid = Str::uuid()->toString();

    $orders = [
        validOrderPayload($this->session->id, $this->currency->id, $goodUuid),
        // Malformed: session belongs to a non-existent user.
        array_merge(validOrderPayload($this->session->id, $this->currency->id, $badUuid), [
            'pos_session_id' => 999_999, // Does not exist.
        ]),
    ];

    postJson('/api/pos/sync/orders', ['orders' => $orders])
        ->assertOk()
        ->assertJsonFragment(['synced' => [$goodUuid]])
        ->assertJson(fn (\Illuminate\Testing\Fluent\AssertableJson $json) => $json->where('synced', [$goodUuid])
            ->has('failed', 1)
            ->has('failed.0.uuid')
            ->etc()
        );

    assertDatabaseHas('pos_orders', ['uuid' => $goodUuid]);
    assertDatabaseCount('pos_orders', 1); // Bad order was not persisted.
});

it('treats a duplicate uuid as already synced without creating a duplicate record', function () {
    $uuid = Str::uuid()->toString();

    $order = validOrderPayload($this->session->id, $this->currency->id, $uuid);

    // First sync — order is created.
    postJson('/api/pos/sync/orders', ['orders' => [$order]])->assertOk();
    assertDatabaseCount('pos_orders', 1);

    // Second sync — idempotent: same UUID is returned in 'synced' but NOT duplicated.
    postJson('/api/pos/sync/orders', ['orders' => [$order]])
        ->assertOk()
        ->assertJson(['synced' => [$uuid], 'failed' => []]);

    assertDatabaseCount('pos_orders', 1); // Still only one record.
});

it('returns an empty result when there are no pending orders to sync', function () {
    postJson('/api/pos/sync/orders', ['orders' => []])
        ->assertUnprocessable(); // Validation requires at least one order.
});
