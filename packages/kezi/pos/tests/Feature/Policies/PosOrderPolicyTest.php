<?php

use Kezi\Pos\Models\PosOrder;
use Spatie\Permission\Models\Permission;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setUpWithConfiguredCompany();

    // Create necessary permissions for POS Orders
    Permission::findOrCreate('view_any_pos_order', 'web');
    Permission::findOrCreate('view_pos_order', 'web');
    Permission::findOrCreate('create_pos_order', 'web');
});

test('user can view any pos order if they have permission', function () {
    $this->user->givePermissionTo('view_any_pos_order');

    expect($this->user->can('viewAny', PosOrder::class))->toBeTrue();
});

test('user cannot view any pos order without permission', function () {
    // Revoke super_admin for negative test
    $this->user->removeRole('super_admin');
    $this->user->revokePermissionTo('view_any_pos_order');

    expect($this->user->can('viewAny', PosOrder::class))->toBeFalse();
});

test('user can view pos order from their company with permission', function () {
    $order = PosOrder::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->user->givePermissionTo('view_pos_order');

    expect($this->user->can('view', $order))->toBeTrue();
});

test('user cannot view pos order from another company even with permission', function () {
    $otherCompany = \App\Models\Company::factory()->create();
    $order = PosOrder::factory()->create([
        'company_id' => $otherCompany->id,
        'currency_id' => $this->company->currency_id, // Reuse same currency to avoid unique constraint
    ]);

    $this->user->givePermissionTo('view_pos_order');
    $this->user->removeRole('super_admin');

    expect($this->user->can('view', $order))->toBeFalse();
});

test('user can create/sync pos order with permission', function () {
    $this->user->givePermissionTo('create_pos_order');

    expect($this->user->can('create', PosOrder::class))->toBeTrue();
    expect($this->user->can('syncOrders', PosOrder::class))->toBeTrue();
});
