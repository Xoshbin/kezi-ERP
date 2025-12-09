<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('loads sales order create page without error', function () {
    $company = \App\Models\Company::factory()->create();
    $user = User::factory()->create();
    $user->companies()->attach($company);

    actingAs($user)
        ->get("/jmeryar/{$company->id}/sales/sales-orders/create")
        ->assertStatus(200);
});
