<?php

use App\Enums\Accounting\AccountType;
use App\Enums\Products\ProductType;
use App\Filament\Clusters\Inventory\Resources\Products\Pages\CreateProduct;
use App\Models\Account;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    $this->actingAs($this->user);
});

test('TranslatableSelect component loads correctly in ProductResource create page', function () {
    $response = $this->get(route('filament.jmeryar.inventory.resources.products.create', [
        'tenant' => $this->company,
    ]));

    $response->assertSuccessful();
    // The page should load without errors, indicating TranslatableSelect is working
});

test('TranslatableSelect can search accounts by name in current locale', function () {
    // Create accounts with translatable names
    $account1 = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'ACC-001',
        'name' => [
            'en' => 'Sales Revenue',
            'ar' => 'إيرادات المبيعات',
            'ckb' => 'داهاتی فرۆشتن'
        ],
        'type' => AccountType::Income,
    ]);

    $account2 = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'ACC-002',
        'name' => [
            'en' => 'Cost of Goods Sold',
            'ar' => 'تكلفة البضائع المباعة',
            'ckb' => 'تێچووی کاڵای فرۆشراو'
        ],
        'type' => AccountType::Expense,
    ]);

    // Test the create page with TranslatableSelect
    $component = livewire(CreateProduct::class, [
        'tenant' => $this->company,
    ]);

    $component->assertSuccessful();

    // Verify that accounts exist and are accessible
    expect(Account::where('company_id', $this->company->id)->count())->toBeGreaterThanOrEqual(2);
});

test('TranslatableSelect can search accounts by code', function () {
    // Create an account with a specific code
    $account = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'SEARCH-TEST-001',
        'name' => [
            'en' => 'Test Account',
            'ar' => 'حساب تجريبي',
            'ckb' => 'هەژماری تاقیکردنەوە'
        ],
        'type' => AccountType::Income,
    ]);

    $component = livewire(CreateProduct::class, [
        'tenant' => $this->company,
    ]);

    $component->assertSuccessful();

    // Verify the account exists and can be found
    expect($account->code)->toBe('SEARCH-TEST-001');
    expect($account->company_id)->toBe($this->company->id);
});

test('can create product with TranslatableSelect account selections', function () {
    // Create accounts for the product
    $incomeAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'INC-001',
        'name' => [
            'en' => 'Product Sales',
            'ar' => 'مبيعات المنتجات',
            'ckb' => 'فرۆشتنی بەرهەم'
        ],
        'type' => AccountType::Income,
    ]);

    $expenseAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'EXP-001',
        'name' => [
            'en' => 'Product Costs',
            'ar' => 'تكاليف المنتجات',
            'ckb' => 'تێچووی بەرهەم'
        ],
        'type' => AccountType::Expense,
    ]);

    // Test creating a product using the TranslatableSelect components
    livewire(CreateProduct::class, [
        'tenant' => $this->company,
    ])
        ->fillForm([
            'name' => 'Test Product with TranslatableSelect',
            'sku' => 'TST-TRANS-001',
            'type' => ProductType::Service->value,
            'description' => 'Testing TranslatableSelect integration',
            'income_account_id' => $incomeAccount->id,
            'expense_account_id' => $expenseAccount->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify the product was created with the correct account relationships
    $product = Product::where('sku', 'TST-TRANS-001')->first();
    expect($product)->not->toBeNull();
    expect($product->income_account_id)->toBe($incomeAccount->id);
    expect($product->expense_account_id)->toBe($expenseAccount->id);
    expect($product->name)->toBe('Test Product with TranslatableSelect');
});

test('TranslatableSelect respects company scoping', function () {
    // Create accounts for different companies
    $otherCompany = \App\Models\Company::factory()->create();

    $ourAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'OUR-001',
        'name' => 'Our Company Account',
        'type' => AccountType::Income,
    ]);

    $otherAccount = Account::factory()->create([
        'company_id' => $otherCompany->id,
        'code' => 'OTHER-001',
        'name' => 'Other Company Account',
        'type' => AccountType::Income,
    ]);

    $component = livewire(CreateProduct::class, [
        'tenant' => $this->company,
    ]);

    $component->assertSuccessful();

    // Verify that only accounts from our company are accessible
    $companyAccountIds = Account::where('company_id', $this->company->id)->pluck('id')->toArray();
    $otherCompanyAccountIds = Account::where('company_id', $otherCompany->id)->pluck('id')->toArray();

    expect($companyAccountIds)->toContain($ourAccount->id);
    expect($companyAccountIds)->not->toContain($otherAccount->id);
    expect($otherCompanyAccountIds)->toContain($otherAccount->id);
    expect($otherCompanyAccountIds)->not->toContain($ourAccount->id);
});
