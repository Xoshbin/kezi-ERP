<?php

use Kezi\Pos\Models\PosSession;
use Spatie\Permission\Models\Permission;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setUpWithConfiguredCompany();

    Permission::findOrCreate('view_any_pos_session', 'web');
    Permission::findOrCreate('view_pos_session', 'web');
    Permission::findOrCreate('create_pos_session', 'web');
    Permission::findOrCreate('manage_pos_sessions', 'web');
});

test('user can view any pos session with permission', function () {
    $this->user->givePermissionTo('view_any_pos_session');
    expect($this->user->can('viewAny', PosSession::class))->toBeTrue();
});

test('user can view session from their company with permission', function () {
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    $this->user->givePermissionTo('view_pos_session');
    expect($this->user->can('view', $session))->toBeTrue();
});

test('user can open session with permission', function () {
    $this->user->givePermissionTo('create_pos_session');
    expect($this->user->can('open', PosSession::class))->toBeTrue();
});

test('user can close their own session without manager permission', function () {
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    expect($this->user->can('close', $session))->toBeTrue();
});

test('manager can close others session in same company', function () {
    $otherUser = \App\Models\User::factory()->create();
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $otherUser->id,
    ]);

    $this->user->givePermissionTo('manage_pos_sessions');

    expect($this->user->can('close', $session))->toBeTrue();
});

test('user cannot close others session without manager permission', function () {
    $otherUser = \App\Models\User::factory()->create();
    $session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $otherUser->id,
    ]);

    $this->user->removeRole('super_admin');
    expect($this->user->can('close', $session))->toBeFalse();
});
