<?php

use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosSession;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setUpWithConfiguredCompany();

    // Ensure permissions are NOT granted by default for negative tests
    $this->user->removeRole('super_admin');

    // Create models to test with
    $this->session = PosSession::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    $this->order = PosOrder::factory()->create([
        'company_id' => $this->company->id,
        'pos_session_id' => $this->session->id,
        'currency_id' => $this->company->currency_id,
    ]);

    $this->return = PosReturn::factory()->create([
        'company_id' => $this->company->id,
        'pos_session_id' => $this->session->id,
        'original_order_id' => $this->order->id,
        'currency_id' => $this->company->currency_id,
    ]);
});

test('MasterDataSync (index) returns 403 without permission', function () {
    $this->getJson(route('api.pos.sync.master-data'))->assertForbidden();
});

test('OrderSync (store) returns 403 without permission', function () {
    $this->postJson(route('api.pos.sync.orders'), ['orders' => []])->assertForbidden();
});

test('Session (open) returns 403 without permission', function () {
    $this->postJson(route('api.pos.sessions.open'), [])->assertForbidden();
});

test('Session (current) returns 403 without permission', function () {
    $this->getJson(route('api.pos.sessions.current'))->assertForbidden();
});

test('PosOrderSearch (search) returns 403 without permission', function () {
    $this->postJson(route('api.pos.orders.search'))->assertForbidden();
});

test('PosOrderSearch (details) returns 403 without permission', function () {
    $this->getJson(route('api.pos.orders.details', $this->order))->assertForbidden();
});

test('PosReturn (store) returns 403 without permission', function () {
    $this->postJson(route('api.pos.returns.store'), [])->assertForbidden();
});

test('PosReturn (approve) returns 403 without permission', function () {
    $this->postJson(route('api.pos.returns.approve', $this->return))->assertForbidden();
});

test('ManagerPin (verifyAndApprove) returns 403 without permission', function () {
    $this->postJson(route('api.pos.returns.verify-pin', $this->return), ['pin' => '1234'])->assertForbidden();
});
