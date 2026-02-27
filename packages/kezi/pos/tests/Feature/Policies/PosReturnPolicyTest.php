<?php

use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosSession;
use Spatie\Permission\Models\Permission;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setUpWithConfiguredCompany();

    Permission::findOrCreate('view_any_pos_return', 'web');
    Permission::findOrCreate('view_pos_return', 'web');
    Permission::findOrCreate('create_pos_return', 'web');
    Permission::findOrCreate('approve_pos_return', 'web');
    Permission::findOrCreate('process_pos_return', 'web');

    // Create a base session and order for the tests to share
    // We pass company_id and avoid using user factory recursively if possible
    $this->testSession = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    $this->testOrder = PosOrder::factory()->create([
        'company_id' => $this->company->id,
        'pos_session_id' => $this->testSession->id,
        'currency_id' => $this->company->currency_id,
    ]);
});

test('user can view and create pos return with permission', function () {
    $this->user->givePermissionTo(['view_any_pos_return', 'create_pos_return']);

    expect($this->user->can('viewAny', PosReturn::class))->toBeTrue();
    expect($this->user->can('create', PosReturn::class))->toBeTrue();
});

test('user can submit draft return from their company', function () {
    $return = PosReturn::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'pos_session_id' => $this->testSession->id,
        'original_order_id' => $this->testOrder->id,
        'status' => PosReturnStatus::Draft,
    ]);

    $this->user->givePermissionTo('create_pos_return');

    expect($this->user->can('submit', $return))->toBeTrue();
});

test('manager can approve/reject pending return', function () {
    $return = PosReturn::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'pos_session_id' => $this->testSession->id,
        'original_order_id' => $this->testOrder->id,
        'status' => PosReturnStatus::PendingApproval,
    ]);

    $this->user->givePermissionTo('approve_pos_return');

    expect($this->user->can('approve', $return))->toBeTrue();
    expect($this->user->can('reject', $return))->toBeTrue();
});

test('authorized user can process approved return', function () {
    $return = PosReturn::factory()->create([
        'company_id' => $this->company->id,
        'currency_id' => $this->company->currency_id,
        'pos_session_id' => $this->testSession->id,
        'original_order_id' => $this->testOrder->id,
        'status' => PosReturnStatus::Approved,
    ]);

    $this->user->givePermissionTo('process_pos_return');

    expect($this->user->can('process', $return))->toBeTrue();
});

test('company isolation is enforced for all actions', function () {
    $otherCompany = \App\Models\Company::factory()->create();

    // We must manually create the dependencies for the other company too
    // to avoid the factory creating a new currency for the other company with same code
    $otherSession = PosSession::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $otherOrder = PosOrder::factory()->create([
        'company_id' => $otherCompany->id,
        'pos_session_id' => $otherSession->id,
        'currency_id' => $this->company->currency_id, // Reuse global currency
    ]);

    $return = PosReturn::factory()->create([
        'company_id' => $otherCompany->id,
        'pos_session_id' => $otherSession->id,
        'original_order_id' => $otherOrder->id,
        'currency_id' => $this->company->currency_id,
        'status' => PosReturnStatus::PendingApproval,
    ]);

    $this->user->givePermissionTo(['view_pos_return', 'approve_pos_return']);
    $this->user->removeRole('super_admin');

    expect($this->user->can('view', $return))->toBeFalse();
    expect($this->user->can('approve', $return))->toBeFalse();
});
