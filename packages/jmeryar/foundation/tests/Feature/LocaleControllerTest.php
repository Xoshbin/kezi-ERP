<?php

namespace Jmeryar\Foundation\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_set_locale_via_post_request()
    {
        $response = $this->post('/locale/ckb');

        $response->assertStatus(200);
        $this->assertEquals('ckb', Session::get('locale'));
        $this->assertEquals('ckb', app()->getLocale());
    }

    /** @test */
    public function it_returns_json_response_for_ajax_requests()
    {
        $response = $this->postJson('/locale/ar');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertEquals('ar', Session::get('locale'));
        $this->assertEquals('ar', app()->getLocale());
    }

    /** @test */
    public function it_provides_redirect_url_for_docs_pages()
    {
        $response = $this->postJson('/locale/ckb', [], [
            'referer' => 'http://localhost/docs/User Guide/payments',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'redirect_url' => 'http://localhost/docs/User Guide/payments.ckb',
            ]);
    }

    /** @test */
    public function it_handles_locale_suffix_conversion_correctly()
    {
        // Test converting from English to Kurdish
        $response = $this->postJson('/locale/ckb', [], [
            'referer' => 'http://localhost/docs/User Guide/payments',
        ]);

        $response->assertJsonPath('redirect_url', 'http://localhost/docs/User Guide/payments.ckb');

        // Test converting from Arabic to English
        $response = $this->postJson('/locale/en', [], [
            'referer' => 'http://localhost/docs/User Guide/payments.ar',
        ]);

        $response->assertJsonPath('redirect_url', 'http://localhost/docs/User Guide/payments');

        // Test converting from Kurdish to Arabic
        $response = $this->postJson('/locale/ar', [], [
            'referer' => 'http://localhost/docs/User Guide/payments.ckb',
        ]);

        $response->assertJsonPath('redirect_url', 'http://localhost/docs/User Guide/payments.ar');
    }

    /** @test */
    public function it_rejects_invalid_locales()
    {
        $response = $this->post('/locale/invalid');

        $response->assertStatus(400);
        $this->assertNull(Session::get('locale'));
    }

    /** @test */
    public function it_only_accepts_supported_locales()
    {
        $supportedLocales = ['en', 'ckb', 'ar'];

        foreach ($supportedLocales as $locale) {
            $response = $this->post('/locale/'.$locale);
            $response->assertStatus(200);
            $this->assertEquals($locale, Session::get('locale'));
        }
    }
}
