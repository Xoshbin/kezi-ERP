<?php

test('welcome page returns a successful response and contains open source CTAs', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Kezi');
    $response->assertSee('One Core.');
    $response->assertSee('Fully Open Source.');
    $response->assertSee('/kezi/register');
    $response->assertSee('Open Source');

    // Iraq Localization Assertions
    $response->assertSee('Iraq');
    $response->assertSee('UAS');
    $response->assertSee('Iraqi Dual-Currency');

    // Abstract Dashboard Assertions
    $response->assertSee('Inventory Status');
    $response->assertSee('Recent Activity');
});
