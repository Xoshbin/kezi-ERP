<?php

use App\Models\User;

it('shows payments documentation page for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('docs.show', ['slug' => 'User Guide/payments']));

    $response->assertStatus(200);
    $response->assertSee('Payments: Payments', false);
});

it('allows payments documentation page for guests (docs are public)', function () {
    // Documentation is publicly accessible
    $this->get(route('docs.show', ['slug' => 'User Guide/payments']))->assertStatus(200);
});

