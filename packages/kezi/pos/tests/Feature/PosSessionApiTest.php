<?php

use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

/**
 * @property \Kezi\Pos\Models\PosProfile $posProfile
 * @property \App\Models\User $user
 */
uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->posProfile = PosProfile::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Main POS',
        'is_active' => true,
    ]);

    \Spatie\Permission\Models\Permission::findOrCreate('create_pos_session', 'web');
    \Spatie\Permission\Models\Permission::findOrCreate('view_any_pos_session', 'web');
    setPermissionsTeamId($this->company->id);
    $this->user->givePermissionTo(['create_pos_session', 'view_any_pos_session']);
});

it('can open a new session', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('api.pos.sessions.open'), [
            'pos_profile_id' => $this->posProfile->id,
            'opening_cash' => 10000, // 100.00
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['session' => ['id', 'status', 'opened_at']]);

    $this->assertDatabaseHas('pos_sessions', [
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->posProfile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
        'opening_cash' => 10000,
    ]);
});

it('prevents opening multiple sessions for same user', function () {
    // Open first session
    $this->actingAs($this->user)->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->posProfile->id,
        'opening_cash' => 0,
    ]);

    // Try opening another
    $response = $this->actingAs($this->user)->postJson(route('api.pos.sessions.open'), [
        'pos_profile_id' => $this->posProfile->id,
        'opening_cash' => 0,
    ]);

    $response->assertStatus(409);
});

it('can retrieve current session', function () {
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->posProfile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('api.pos.sessions.current'));

    $response->assertStatus(200)
        ->assertJsonPath('session.id', $session->id);
});

it('can close a session', function () {
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'pos_profile_id' => $this->posProfile->id,
        'user_id' => $this->user->id,
        'status' => 'opened',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('api.pos.sessions.close', $session), [
            'closing_cash' => 15000,
            'closing_notes' => 'End of day',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('pos_sessions', [
        'id' => $session->id,
        'status' => 'closed',
        'closing_cash' => 15000,
        'closing_notes' => 'End of day',
    ]);
});
