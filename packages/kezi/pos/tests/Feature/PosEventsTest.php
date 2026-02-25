<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Events\PosOrderSynced;
use Kezi\Pos\Events\PosSessionClosed;
use Kezi\Pos\Events\PosSessionOpened;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('close_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_order', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo([
        'create_pos_session',
        'view_any_pos_session',
        'close_pos_session',
        'create_pos_order',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    $this->profile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
});

// --- Gap 5: Domain Events ---

it('dispatches PosSessionOpened event when a session is opened', function () {
    Event::fake([PosSessionOpened::class]);

    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 5000,
    ])->assertCreated();

    Event::assertDispatched(PosSessionOpened::class, function (PosSessionOpened $event) {
        return $event->session->user_id === $this->user->id
            && $event->session->status === 'opened';
    });
});

it('dispatches PosSessionClosed event when a session is closed', function () {
    Event::fake([PosSessionClosed::class]);

    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $this->postJson(route('api.pos.sessions.close', $session), [
        'closing_cash' => 5000,
    ])->assertOk();

    Event::assertDispatched(PosSessionClosed::class, function (PosSessionClosed $event) use ($session) {
        return $event->session->id === $session->id
            && $event->session->status === 'closed';
    });
});

it('does not dispatch PosSessionOpened event when user already has an open session', function () {
    Event::fake([PosSessionOpened::class]);

    // Open first session
    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertCreated();

    // Attempt a second session — should fail with 409
    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertStatus(409);

    // Event should only fire once (from the first successful open)
    Event::assertDispatchedTimes(PosSessionOpened::class, 1);
});

it('dispatches PosOrderSynced event for each successfully synced order', function () {
    Event::fake([PosOrderSynced::class]);

    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $uuid1 = Str::uuid()->toString();
    $uuid2 = Str::uuid()->toString();

    $orders = [
        ['uuid' => $uuid1, 'order_number' => 'POS-EVT-001', 'status' => 'paid', 'ordered_at' => now()->toIso8601String(), 'total_amount' => '1000', 'total_tax' => '0', 'currency_id' => $this->currency->id, 'pos_session_id' => $session->id, 'lines' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => '1000', 'tax_amount' => '0', 'total_amount' => '1000', 'metadata' => []]]],
        ['uuid' => $uuid2, 'order_number' => 'POS-EVT-002', 'status' => 'paid', 'ordered_at' => now()->toIso8601String(), 'total_amount' => '2000', 'total_tax' => '0', 'currency_id' => $this->currency->id, 'pos_session_id' => $session->id, 'lines' => [['product_id' => 1, 'quantity' => 2, 'unit_price' => '1000', 'tax_amount' => '0', 'total_amount' => '2000', 'metadata' => []]]],
    ];

    $this->postJson('/api/pos/sync/orders', ['orders' => $orders])->assertOk();

    // One event per successfully synced order
    Event::assertDispatchedTimes(PosOrderSynced::class, 2);

    Event::assertDispatched(PosOrderSynced::class, fn (PosOrderSynced $e) => $e->order->uuid === $uuid1);
    Event::assertDispatched(PosOrderSynced::class, fn (PosOrderSynced $e) => $e->order->uuid === $uuid2);
});

it('does not dispatch PosOrderSynced event for duplicate (already synced) orders', function () {
    Event::fake([PosOrderSynced::class]);

    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $uuid = Str::uuid()->toString();
    $order = ['uuid' => $uuid, 'order_number' => 'POS-DUP-001', 'status' => 'paid', 'ordered_at' => now()->toIso8601String(), 'total_amount' => '1000', 'total_tax' => '0', 'currency_id' => $this->currency->id, 'pos_session_id' => $session->id, 'lines' => [['product_id' => 1, 'quantity' => 1, 'unit_price' => '1000', 'tax_amount' => '0', 'total_amount' => '1000', 'metadata' => []]]];

    // First sync
    $this->postJson('/api/pos/sync/orders', ['orders' => [$order]])->assertOk();
    Event::assertDispatchedTimes(PosOrderSynced::class, 1);

    // Second sync with same UUID — idempotent: no new event
    $this->postJson('/api/pos/sync/orders', ['orders' => [$order]])->assertOk();
    Event::assertDispatchedTimes(PosOrderSynced::class, 1);
});

// --- Gap 6: Race Condition on Session Opening ---

it('prevents concurrent duplicate sessions via database-level lock', function () {
    // Verify that even if the open endpoint is called back-to-back, only one session is created
    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertCreated();

    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertStatus(409);

    // Only one session should exist in the database
    $this->assertDatabaseCount('pos_sessions', 1);
});

it('returns 201 and session data when first session is opened successfully', function () {
    $response = $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 10000,
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'session' => ['id', 'status', 'opened_at']]);

    $this->assertDatabaseHas('pos_sessions', [
        'user_id' => $this->user->id,
        'status' => 'opened',
        'opening_cash' => 10000,
    ]);
});

it('returns the existing session in the 409 conflict response', function () {
    // Open a session first
    $firstResponse = $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertCreated();

    $existingSessionId = $firstResponse->json('session.id');

    // Second attempt should return 409 with the existing session
    $this->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->profile->id,
        'opening_cash' => 0,
    ])->assertStatus(409)
        ->assertJsonPath('session.id', $existingSessionId);
});
