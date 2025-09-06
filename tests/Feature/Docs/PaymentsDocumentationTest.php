<?php

use App\Models\User;

it('shows payments documentation page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('docs.payments'));

    $response->assertStatus(200);
    $response->assertSee('Payments: Easy Guide for Everyone', false);
});

it('denies payments documentation page for guests (no auth routes -> 500)', function () {
    // In this app, unauthenticated access to protected routes results in 500 due to no login route
    $this->get(route('docs.payments'))->assertStatus(500);
});

