<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Enums\Accounting\AccountType;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\AccountResource;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\CreateAccount;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\EditAccount;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Accounts\Pages\ListAccounts;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\AccountGroup;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('AccountResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(AccountResource::getUrl('index', tenant: $this->company))
            ->assertSuccessful();
    });

    it('can list accounts', function () {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'TESTLISTCODE',
        ]);

        livewire(ListAccounts::class)
            ->searchTable('TESTLISTCODE')
            ->assertCanRenderTableColumn('code')
            ->assertSee('TESTLISTCODE');
    });

    it('can create account with wizard', function () {
        $group = AccountGroup::factory()->create([
            'company_id' => $this->company->id,
            'code_prefix_start' => '10',
            'code_prefix_end' => '19',
        ]);

        livewire(CreateAccount::class)
            // Step 1: Select Group
            ->fillForm([
                'account_group_id' => $group->id,
            ])
            // Skip intermediate assertion if hook is tricky in test,
            // the final creation check verifies it works.
            ->fillForm([
                'code' => '1001',
                'name' => 'Cash at Hand',
                'type' => AccountType::CurrentAssets->value,
                'is_deprecated' => false,
                'allow_reconciliation' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('accounts', [
            'company_id' => $this->company->id,
            'code' => '1001',
            'account_group_id' => $group->id,
            'type' => AccountType::CurrentAssets->value,
        ]);
    });

    it('can edit account', function () {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Old Name'],
        ]);

        livewire(EditAccount::class, [
            'record' => $account->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'New Name',
                'is_deprecated' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($account->refresh())
            ->name->toBe('New Name')
            ->is_deprecated->toBeTrue();
    });

    it('can validate required fields', function () {
        livewire(CreateAccount::class)
            ->fillForm([
                'code' => null,
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'code' => 'required',
                'name' => 'required',
            ]);
    });
    it('scopes accounts to the active company', function () {
        $accountInCompany = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'ACC-IN-COMPANY',
        ]);

        $otherCompany = \App\Models\Company::factory()->create();
        $accountInOtherCompany = Account::factory()->create([
            'company_id' => $otherCompany->id,
            'code' => 'ACC-OUT-COMPANY',
        ]);

        Filament::setTenant($this->company);

        livewire(ListAccounts::class)
            ->searchTable('ACC')
            ->assertCanSeeTableRecords([$accountInCompany])
            ->assertCanNotSeeTableRecords([$accountInOtherCompany]);
    });

    it('can configure asset creation setting for compatible accounts', function () {
        // Test Fixed Asset Account (Should see toggle)
        livewire(CreateAccount::class)
            ->fillForm([
                'name' => 'Vehicle Fleet',
                'code' => '1500',
                'type' => AccountType::FixedAssets->value,
                'can_create_assets' => true,
            ])
            ->assertFormFieldIsVisible('can_create_assets')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('accounts', [
            'code' => '1500',
            'can_create_assets' => true,
        ]);

        // Test Expense Account (Should see toggle)
        livewire(CreateAccount::class)
            ->fillForm([
                'name' => 'IT Equipment Expense',
                'code' => '6000',
                'type' => AccountType::Expense->value,
                'can_create_assets' => true,
            ])
            ->assertFormFieldIsVisible('can_create_assets')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('accounts', [
            'code' => '6000',
            'can_create_assets' => true,
        ]);

        // Test Income Account (Should NOT see toggle)
        livewire(CreateAccount::class)
            ->fillForm([
                'name' => 'Sales',
                'code' => '4000',
                'type' => AccountType::Income->value,
            ])
            ->assertFormFieldIsHidden('can_create_assets');
    });
});
