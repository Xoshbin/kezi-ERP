<?php

namespace Tests\Feature;

use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Xoshbin\TranslatableSelect\Services\LocaleResolver;
use Xoshbin\TranslatableSelect\Services\TranslatableSearchService;

class TranslatableSelectSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    /** @test */
    public function test_locale_resolver_detects_available_locales_correctly()
    {
        $localeResolver = app(LocaleResolver::class);

        $availableLocales = $localeResolver->getAvailableLocales();

        // Should include the locales from pertuk.supported_locales
        $this->assertContains('en', $availableLocales);
        $this->assertContains('ckb', $availableLocales);
        $this->assertContains('ar', $availableLocales);
    }

    /** @test */
    public function test_translatable_search_works_across_all_locales()
    {
        // Create an account with translations
        $account = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'TEST001',
            'name' => [
                'en' => 'Test Account',
                'ckb' => 'حسابی تاقیکردنەوە',
                'ar' => 'حساب اختبار',
            ],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
            'is_deprecated' => false,
        ]);

        $searchService = app(TranslatableSearchService::class);
        $localeResolver = app(LocaleResolver::class);

        $searchLocales = $localeResolver->getModelLocales(\Modules\Accounting\Models\Account::class);

        // Test English search
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'Test', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($account->id, $results);
        $this->assertEquals('Test Account', $results[$account->id]);

        // Test Kurdish search
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'تاقی', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($account->id, $results);
        $this->assertEquals('Test Account', $results[$account->id]);

        // Test Arabic search
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'اختبار', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($account->id, $results);
        $this->assertEquals('Test Account', $results[$account->id]);

        // Test code search (non-translatable field)
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'TEST001', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($account->id, $results);
        $this->assertEquals('Test Account', $results[$account->id]);
    }

    /** @test */
    public function test_search_handles_both_translatable_and_non_translatable_fields()
    {
        // Create accounts with different data
        $translatableAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'TRANS001',
            'name' => [
                'en' => 'Translatable Account',
                'ckb' => 'حسابی وەرگێڕراو',
                'ar' => 'حساب قابل للترجمة',
            ],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
            'is_deprecated' => false,
        ]);

        $codeAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'SPECIAL123',
            'name' => [
                'en' => 'Code Search Account',
                'ckb' => 'حسابی گەڕانی کۆد',
                'ar' => 'حساب البحث بالرمز',
            ],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::CurrentAssets,
            'is_deprecated' => false,
        ]);

        $searchService = app(TranslatableSearchService::class);
        $localeResolver = app(LocaleResolver::class);
        $searchLocales = $localeResolver->getModelLocales(\Modules\Accounting\Models\Account::class);

        // Search by translatable content
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'وەرگێڕراو', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($translatableAccount->id, $results);
        $this->assertArrayNotHasKey($codeAccount->id, $results);

        // Search by code (non-translatable)
        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'SPECIAL123', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertArrayHasKey($codeAccount->id, $results);
        $this->assertArrayNotHasKey($translatableAccount->id, $results);
    }

    /** @test */
    public function test_search_returns_empty_when_no_matches()
    {
        $searchService = app(TranslatableSearchService::class);
        $localeResolver = app(LocaleResolver::class);
        $searchLocales = $localeResolver->getModelLocales(\Modules\Accounting\Models\Account::class);

        $results = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, 'NonExistentSearch', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id),
            'limit' => 50,
        ]);

        $this->assertEmpty($results);
    }

    /** @test */
    public function test_product_resource_income_account_filtering()
    {
        // Create income accounts
        $incomeAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'INC001',
            'name' => ['en' => 'Test Income Account'],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::Income,
            'is_deprecated' => false,
        ]);

        // Create expense account (should not appear in income dropdown)
        $expenseAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'EXP001',
            'name' => ['en' => 'Test Expense Account'],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense,
            'is_deprecated' => false,
        ]);

        $searchService = app(TranslatableSearchService::class);
        $localeResolver = app(LocaleResolver::class);
        $searchLocales = $localeResolver->getModelLocales(\Modules\Accounting\Models\Account::class);

        // Test income account filtering (as used in ProductResource)
        $incomeResults = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, '', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id)
                ->whereIn('type', [\Modules\Accounting\Enums\Accounting\AccountType::Income, \Modules\Accounting\Enums\Accounting\AccountType::OtherIncome]),
            'limit' => 50,
        ]);

        // Should include income account but not expense account
        $this->assertArrayHasKey($incomeAccount->id, $incomeResults);
        $this->assertArrayNotHasKey($expenseAccount->id, $incomeResults);
    }

    /** @test */
    public function test_product_resource_expense_account_filtering()
    {
        // Create expense account
        $expenseAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'EXP001',
            'name' => ['en' => 'Test Expense Account'],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::Expense,
            'is_deprecated' => false,
        ]);

        // Create income account (should not appear in expense dropdown)
        $incomeAccount = \Modules\Accounting\Models\Account::create([
            'company_id' => $this->company->id,
            'code' => 'INC001',
            'name' => ['en' => 'Test Income Account'],
            'type' => \Modules\Accounting\Enums\Accounting\AccountType::Income,
            'is_deprecated' => false,
        ]);

        $searchService = app(TranslatableSearchService::class);
        $localeResolver = app(LocaleResolver::class);
        $searchLocales = $localeResolver->getModelLocales(\Modules\Accounting\Models\Account::class);

        // Test expense account filtering (as used in ProductResource)
        $expenseResults = $searchService->getFilamentSearchResults(\Modules\Accounting\Models\Account::class, '', [
            'searchFields' => ['name', 'code'],
            'labelField' => 'name',
            'searchLocales' => $searchLocales,
            'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id)
                ->whereIn('type', [\Modules\Accounting\Enums\Accounting\AccountType::Expense, \Modules\Accounting\Enums\Accounting\AccountType::Depreciation, \Modules\Accounting\Enums\Accounting\AccountType::CostOfRevenue]),
            'limit' => 50,
        ]);

        // Should include expense account but not income account
        $this->assertArrayHasKey($expenseAccount->id, $expenseResults);
        $this->assertArrayNotHasKey($incomeAccount->id, $expenseResults);
    }

    /** @test */
    public function it_debugs_search_functionality_with_different_terms()
    {
        // Test various search terms to understand the issue
        $searchTerms = ['Product', 'Sales', 'roduct', 'ales', 'Product Sales'];

        foreach ($searchTerms as $term) {
            $results = $this->searchService->getFilamentSearchResults(
                \Modules\Accounting\Models\Account::class,
                $term,
                [
                    'searchFields' => ['name', 'code'],
                    'labelField' => 'name',
                    'searchLocales' => ['en', 'ckb', 'ar'],
                    'queryModifier' => fn ($query) => $query->where('company_id', $this->company->id)
                        ->whereIn('type', [\Modules\Accounting\Enums\Accounting\AccountType::Income, \Modules\Accounting\Enums\Accounting\AccountType::OtherIncome]),
                    'limit' => 50,
                ]
            );

            echo "\nSearch term: '$term' - Results count: ".count($results)."\n";
            foreach ($results as $id => $name) {
                echo "  - $name\n";
            }
        }

        // This test is for debugging, so we'll just assert it runs
        $this->assertTrue(true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }
}
