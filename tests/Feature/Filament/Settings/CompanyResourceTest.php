<?php

use App\Filament\Clusters\Settings\Resources\Companies\CompanyResource;
use App\Filament\Clusters\Settings\Resources\Companies\Pages\CreateCompany;
use App\Filament\Clusters\Settings\Resources\Companies\Pages\EditCompany;
use App\Filament\Clusters\Settings\Resources\Companies\Pages\ListCompanies;
use App\Filament\Clusters\Settings\Resources\Companies\RelationManagers\AccountsRelationManager;
use App\Filament\Clusters\Settings\Resources\Companies\RelationManagers\UsersRelationManager;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Journal;
use Kezi\Foundation\Models\Currency;
use Kezi\Inventory\Enums\Inventory\InventoryAccountingMode;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
});

it('can render list companies page', function () {
    /** @var \Tests\TestCase $this */
    get(CompanyResource::getUrl('index'))
        ->assertSuccessful();
});

it('can list companies', function () {
    /** @var \Tests\TestCase $this */
    // Create 2 more companies
    $companies = Company::factory()->count(2)->create();

    // Attach the current user to these new companies so they are visible
    // Filament's tenancy scoping usually filters by companies the user belongs to.
    /** @phpstan-ignore-next-line */
    $this->user->companies()->attach($companies);

    Livewire::test(ListCompanies::class)
        ->assertCanSeeTableRecords($companies)
        ->assertCountTableRecords(Company::count());
});

it('can render create company page', function () {
    /** @var \Tests\TestCase $this */
    get(CompanyResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create company', function () {
    /** @var \Tests\TestCase $this */
    /** @var Currency $currency */
    $currency = Currency::factory()->createSafely();

    Livewire::test(CreateCompany::class)
        ->fillForm([
            'name' => 'New Test Company',
            'fiscal_country' => 'US',
            'tax_id' => '123-456-789',
            'currency_id' => $currency->id,
            'inventory_accounting_mode' => InventoryAccountingMode::AUTO_RECORD_ON_BILL->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('companies', [
        'name' => 'New Test Company',
        'fiscal_country' => 'US',
        'tax_id' => '123-456-789',
        'currency_id' => $currency->id,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateCompany::class)
        ->fillForm([
            'name' => null,
            'fiscal_country' => null,
            'currency_id' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'fiscal_country', 'currency_id']);
});

it('can render edit company page', function () {
    /** @var \Tests\TestCase $this */
    /** @phpstan-ignore-next-line */
    get(CompanyResource::getUrl('edit', ['record' => $this->company]))
        ->assertSuccessful();
});

it('can edit company defaults', function () {
    /** @var \Tests\TestCase $this */
    /** @var Account $account */
    /** @phpstan-ignore-next-line */
    $account = Account::factory()->create(['company_id' => $this->company->id]);
    /** @var Journal $journal */
    /** @phpstan-ignore-next-line */
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);

    /** @phpstan-ignore-next-line */
    Livewire::test(EditCompany::class, ['record' => $this->company->getRouteKey()])
        ->fillForm([
            'default_accounts_payable_id' => $account->id,
            'default_sales_journal_id' => $journal->id,
        ])
        ->call('save') // PHPStan thinks call is undefined on Testable livewire? No, likely fine.
        ->assertHasNoFormErrors();

    assertDatabaseHas('companies', [
        /** @phpstan-ignore-next-line */
        'id' => $this->company->id,
        'default_accounts_payable_id' => $account->id,
        'default_sales_journal_id' => $journal->id,
    ]);
});

it('registers relations', function () {
    $relations = CompanyResource::getRelations();

    $this->assertContains(AccountsRelationManager::class, $relations);
    $this->assertContains(UsersRelationManager::class, $relations);
});
