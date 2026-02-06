<?php

test('welcome page returns a successful response and contains premium CTAs', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Kezi');
    $response->assertSee('One Platform.');
    $response->assertSee('Limitless Efficiency.');
    $response->assertSee('/kezi/register');
    $response->assertDontSee('Open Source');

    // Iraq Localization Assertions
    $response->assertSee('Iraq');
    $response->assertSee('UAS');
    $response->assertSee('Native Dual-Currency');
});
