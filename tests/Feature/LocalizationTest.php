<?php

test('it can switch languages', function () {
    $response = $this->get('/lang/ckb');

    $response->assertSessionHas('locale', 'ckb');
    $response->assertRedirect();
});

test('homepage loads in kurdish by default', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('One Core.');
    $response->assertDontSee('یەک ئامانج.');
    $response->assertSee('dir="ltr"', false);
});

test('homepage loads in kurdish when session is set', function () {
    $response = $this->withSession(['locale' => 'ckb'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('یەک ئامانج.');
    $response->assertSee('Fully Open Source.');
    $response->assertSee('dir="rtl"', false);
});

test('homepage loads in arabic when session is set', function () {
    $response = $this->withSession(['locale' => 'ar'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('نواة واحدة.');
    $response->assertSee('Fully Open Source.');
    $response->assertSee('dir="rtl"', false);
});
