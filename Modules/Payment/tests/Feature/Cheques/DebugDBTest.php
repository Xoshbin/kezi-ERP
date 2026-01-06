<?php

use Illuminate\Support\Facades\Schema;

// uses(\Tests\TestCase::class);

it('has users table', function () {
    expect(Schema::hasTable('users'))->toBeTrue();
});

it('has cheques table', function () {
    expect(Schema::hasTable('cheques'))->toBeTrue();
});

it('can create user via factory', function () {
    $user = \App\Models\User::factory()->create();
    expect($user->exists)->toBeTrue();
});

it('can create company via factory', function () {
    $company = \App\Models\Company::factory()->create();
    expect($company->exists)->toBeTrue();
});
