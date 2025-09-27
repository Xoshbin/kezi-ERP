<?php

namespace Modules\Foundation\Tests\Feature;

use App\Enums\Accounting\AccountType;
use App\Enums\Partners\PartnerType;
use App\Models\Company;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslatableSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    /** @test */
    public function it_can_search_translatable_models_across_all_locales()
    {
        // Set locale to English for this test
        app()->setLocale('en');

        // Create a currency with translations
        $currency = \Modules\Foundation\Models\Currency::factory()->create([
            'name' => [
                'en' => 'US Dollar',
                'ckb' => 'دۆلاری ئەمریکی',
                'ar' => 'الدولار الأمريكي',
            ],
            'code' => 'USD',
            'symbol' => '$',
        ]);

        // Test search in English
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('Dollar');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('US Dollar', $results[$currency->id]);

        // Test search in Kurdish
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('دۆلار');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('US Dollar', $results[$currency->id]); // Should return in current locale (en)

        // Test search in Arabic
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('الدولار');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('US Dollar', $results[$currency->id]); // Should return in current locale (en)

        // Test partial search
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('دۆلا');
        $this->assertArrayHasKey($currency->id, $results);
    }

    /** @test */
    public function it_returns_results_in_current_locale()
    {
        $currency = \Modules\Foundation\Models\Currency::factory()->create([
            'name' => [
                'en' => 'Euro',
                'ckb' => 'یۆرۆ',
                'ar' => 'اليورو',
            ],
            'code' => 'EUR',
            'symbol' => '€',
        ]);

        // Set locale to Kurdish
        app()->setLocale('ckb');

        // Search in English but expect Kurdish result
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('Euro');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('یۆرۆ', $results[$currency->id]);

        // Set locale to Arabic
        app()->setLocale('ar');

        // Search in Kurdish but expect Arabic result
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('یۆرۆ');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('اليورو', $results[$currency->id]);
    }

    /** @test */
    public function it_can_search_accounts_with_code_and_name()
    {
        $account = \Modules\Accounting\Models\Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => [
                'en' => 'Cash Account',
                'ckb' => 'حسابی کاش',
                'ar' => 'حساب النقد',
            ],
            'code' => '1001',
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
        ]);

        // Search by code
        $results = \Modules\Accounting\Models\Account::getFilamentSearchResults('1001');
        $this->assertArrayHasKey($account->id, $results);

        // Search by name in different languages
        $results = \Modules\Accounting\Models\Account::getFilamentSearchResults('Cash');
        $this->assertArrayHasKey($account->id, $results);

        $results = \Modules\Accounting\Models\Account::getFilamentSearchResults('کاش');
        $this->assertArrayHasKey($account->id, $results);

        $results = \Modules\Accounting\Models\Account::getFilamentSearchResults('النقد');
        $this->assertArrayHasKey($account->id, $results);
    }

    /** @test */
    public function it_can_search_tax_with_multiple_translatable_fields()
    {
        $tax = Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => [
                'en' => 'VAT',
                'ckb' => 'باجی زیادکراو',
                'ar' => 'ضريبة القيمة المضافة',
            ],
            'label_on_invoices' => [
                'en' => 'Value Added Tax',
                'ckb' => 'باجی بەهای زیادکراو',
                'ar' => 'ضريبة القيمة المضافة',
            ],
            'rate' => 15.0,
        ]);

        // Search by name
        $results = Tax::getFilamentSearchResults('VAT');
        $this->assertArrayHasKey($tax->id, $results);

        // Search by label_on_invoices
        $results = Tax::getFilamentSearchResults('Value Added');
        $this->assertArrayHasKey($tax->id, $results);

        // Search in Kurdish
        $results = Tax::getFilamentSearchResults('باجی');
        $this->assertArrayHasKey($tax->id, $results);

        // Search in Arabic
        $results = Tax::getFilamentSearchResults('ضريبة');
        $this->assertArrayHasKey($tax->id, $results);
    }

    /** @test */
    public function it_can_search_non_translatable_models()
    {
        $partner = \Modules\Foundation\Models\Partner::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe Company',
            'email' => 'john@example.com',
            'contact_person' => 'John Doe',
            'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
        ]);

        // Search by name
        $results = \Modules\Foundation\Models\Partner::getFilamentSearchResults('John');
        $this->assertArrayHasKey($partner->id, $results);

        // Search by email
        $results = \Modules\Foundation\Models\Partner::getFilamentSearchResults('john@example');
        $this->assertArrayHasKey($partner->id, $results);

        // Search by contact person
        $results = \Modules\Foundation\Models\Partner::getFilamentSearchResults('Doe');
        $this->assertArrayHasKey($partner->id, $results);
    }

    /** @test */
    public function it_handles_missing_translations_gracefully()
    {
        $currency = \Modules\Foundation\Models\Currency::factory()->create([
            'name' => [
                'en' => 'British Pound',
                // Missing Kurdish and Arabic translations
            ],
            'code' => 'GBP',
            'symbol' => '£',
        ]);

        // Should still find the currency and return the available translation
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('British');
        $this->assertArrayHasKey($currency->id, $results);
        $this->assertEquals('British Pound', $results[$currency->id]);

        // Test with Kurdish locale but missing translation
        app()->setLocale('ckb');
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('British');
        $this->assertArrayHasKey($currency->id, $results);
        // Should fallback to the available translation
        $this->assertEquals('British Pound', $results[$currency->id]);
    }

    /** @test */
    public function it_can_use_formatted_search_results()
    {
        // Set locale to English for this test
        app()->setLocale('en');

        $account = \Modules\Accounting\Models\Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => [
                'en' => 'Bank Account',
                'ckb' => 'حسابی بانک',
                'ar' => 'حساب البنك',
            ],
            'code' => '1100',
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::BankAndCash,
        ]);

        $formatter = fn ($account) => [$account->id => $account->getTranslatedLabel('name').' ('.$account->code.')'];

        $results = \Modules\Accounting\Models\Account::getFormattedSearchResults('Bank', 50, $formatter);
        $this->assertArrayHasKey($account->id, $results);
        $this->assertEquals('Bank Account (1100)', $results[$account->id]);
    }

    /** @test */
    public function it_limits_search_results_correctly()
    {
        // Create multiple currencies
        for ($i = 1; $i <= 60; $i++) {
            \Modules\Foundation\Models\Currency::factory()->create([
                'name' => [
                    'en' => "Currency $i",
                    'ckb' => "دراو $i",
                    'ar' => "عملة $i",
                ],
                'code' => "CUR$i",
                'symbol' => "$i",
            ]);
        }

        // Test default limit (50)
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('Currency');
        $this->assertCount(50, $results);

        // Test custom limit
        $results = \Modules\Foundation\Models\Currency::getFilamentSearchResults('Currency', 10);
        $this->assertCount(10, $results);
    }

    /** @test */
    public function it_can_get_all_translations_for_debugging()
    {
        $currency = \Modules\Foundation\Models\Currency::factory()->create([
            'name' => [
                'en' => 'Japanese Yen',
                'ckb' => 'یەنی ژاپۆنی',
                'ar' => 'الين الياباني',
            ],
            'code' => 'JPY',
            'symbol' => '¥',
        ]);

        $translations = $currency->getAllTranslations('name');

        $this->assertArrayHasKey('en', $translations);
        $this->assertArrayHasKey('ckb', $translations);
        $this->assertArrayHasKey('ar', $translations);
        $this->assertEquals('Japanese Yen', $translations['en']);
        $this->assertEquals('یەنی ژاپۆنی', $translations['ckb']);
        $this->assertEquals('الين الياباني', $translations['ar']);
    }
}
