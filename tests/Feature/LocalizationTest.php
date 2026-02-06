<?php

test('it can switch languages', function () {
    $response = $this->get('/lang/ckb');

    $response->assertSessionHas('locale', 'ckb');
    $response->assertRedirect();
});

test('homepage loads in english by default', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('One Platform.');
    $response->assertDontSee('یەک پلاتفۆرم.');
    $response->assertSee('dir="ltr"', false);
});

test('homepage loads in kurdish when session is set', function () {
    $response = $this->withSession(['locale' => 'ckb'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('یەک پلاتفۆرم.');
    $response->assertSee('کارایی بێ سنوور.');
    $response->assertSee('dir="rtl"', false);
});
