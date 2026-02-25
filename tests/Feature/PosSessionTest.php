<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup basic data
    $this->currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
    $this->company = Company::factory()->create(['currency_id' => $this->currency->id]);
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_session', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo(['create_pos_session', 'view_any_pos_session']);
});

test('cannot open session without authentication', function () {
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);

    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 10000,
    ]);

    $response->assertStatus(401);
});

test('cannot open session with inactive profile', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 10000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['pos_profile_id']);
});

test('cannot open session for profile from another company', function () {
    Sanctum::actingAs($this->user, ['*']);
    $otherCompany = Company::factory()->create();
    $profile = PosProfile::factory()->create(['company_id' => $otherCompany->id]);

    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 10000,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['pos_profile_id']);
});

test('cannot open duplicate session', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);

    // Open first session
    $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 10000,
    ])->assertStatus(201);

    // Attempt second session on same profile
    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profile->id,
        'opening_cash' => 20000,
    ]);

    $response->assertStatus(409)
        ->assertJsonFragment(['message' => 'User already has an open session on profile: '.$profile->name]);
});

test('cannot open second session on different profile', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profileA = PosProfile::factory()->create(['company_id' => $this->company->id, 'name' => 'Profile A']);
    $profileB = PosProfile::factory()->create(['company_id' => $this->company->id, 'name' => 'Profile B']);

    // Open session on profile A
    $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profileA->id,
        'opening_cash' => 10000,
    ])->assertStatus(201);

    // Attempt session on profile B
    $response = $this->postJson('/api/pos/sessions/open', [
        'pos_profile_id' => $profileB->id,
        'opening_cash' => 20000,
    ]);

    $response->assertStatus(409)
        ->assertJsonFragment(['message' => 'User already has an open session on profile: '.$profileA->name]);
});

test('close session returns summary', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    // Create some orders
    PosOrder::factory()->count(3)->create([
        'pos_session_id' => $session->id,
        'company_id' => $this->company->id,
        'currency_id' => $this->currency->id,
        'total_amount' => 10, // $10.00 each -> 1000 minor units
    ]);

    $response = $this->postJson("/api/pos/sessions/{$session->id}/close", [
        'closing_cash' => 13000,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'session',
            'summary' => [
                'order_count',
                'total_revenue',
            ],
        ])
        ->assertJsonFragment([
            'order_count' => 3,
            'total_revenue' => 3000,
        ]);
});

test('close session with zero orders returns zero summary', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $response = $this->postJson("/api/pos/sessions/{$session->id}/close", [
        'closing_cash' => 10000,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'order_count' => 0,
            'total_revenue' => 0,
        ]);
});

test('cannot close already closed session', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'user_id' => $this->user->id,
        'status' => 'closed',
        'closed_at' => now(),
    ]);

    $response = $this->postJson("/api/pos/sessions/{$session->id}/close", [
        'closing_cash' => 10000,
    ]);

    $response->assertStatus(409)
        ->assertJsonFragment(['message' => 'Session is already closed or not open']);
});

test('cannot close another users session', function () {
    Sanctum::actingAs($this->user, ['*']);
    $otherUser = User::factory()->create();
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'user_id' => $otherUser->id,
        'status' => 'opened',
    ]);

    $response = $this->postJson("/api/pos/sessions/{$session->id}/close", [
        'closing_cash' => 10000,
    ]);

    $response->assertStatus(403);
});

test('current session returns 404 when no open session', function () {
    Sanctum::actingAs($this->user, ['*']);

    $response = $this->getJson('/api/pos/sessions/current');

    $response->assertStatus(404);
});

test('current session returns session with profile details', function () {
    Sanctum::actingAs($this->user, ['*']);
    $profile = PosProfile::factory()->create(['company_id' => $this->company->id]);
    $session = PosSession::factory()->create([
        'pos_profile_id' => $profile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $response = $this->getJson('/api/pos/sessions/current');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'session' => [
                'id',
                'status',
                'profile' => ['id', 'name', 'settings', 'features'],
            ],
            'order_count',
        ])
        ->assertJsonFragment(['id' => $session->id]);
});
