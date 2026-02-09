<?php

test('welcome page returns a successful response and contains premium CTAs', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Kezi');
    $response->assertSee('One Core.');
    $response->assertSee('Unlimited Capabilities.');
    $response->assertSee('/kezi/register');
    $response->assertDontSee('Open Source');

    // Iraq Localization Assertions
    $response->assertSee('Iraq');
    $response->assertSee('UAS');
    $response->assertSee('Iraqi Dual-Currency');

    // Abstract Dashboard Assertions
    $response->assertSee('Inventory Status');
    $response->assertSee('Recent Activity');
});
