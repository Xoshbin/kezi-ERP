<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Forms\Components;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\AccountType;
use Kezi\Accounting\Filament\Forms\Components\AccountSelectField;
use Kezi\Accounting\Models\Account;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('AccountSelectField', function () {
    it('can extract schema', function () {
        $field = AccountSelectField::make('account_id');

        expect($field->getName())->toBe('account_id')
            ->and($field->getLabel())->toBe(__('accounting::account.label'));
    });

    it('has create option form with correct fields', function () {
        $field = AccountSelectField::make('account_id');

        $mockHasForms = new class extends \Livewire\Component implements \Filament\Forms\Contracts\HasForms
        {
            use \Filament\Forms\Concerns\InteractsWithForms;

            public function render(): string
            {
                return '<div></div>';
            }
        };

        $schema = \Filament\Schemas\Schema::make($mockHasForms);
        $components = $field->getCreateOptionActionForm($schema);

        expect($components)->toBeArray()
            ->and($components)->not->toBeEmpty();
    });

    it('can create account using createOptionUsing', function () {
        $field = AccountSelectField::make('account_id');
        $data = [
            'code' => '1001',
            'name' => 'Test Account',
            'type' => AccountType::CurrentAssets->value,
            'company_id' => $this->company->id,
        ];

        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        expect($id)->toBeInt();
        $this->assertDatabaseHas('accounts', [
            'id' => $id,
            'code' => '1001',
            'company_id' => $this->company->id,
        ]);
    });

    it('can filter accounts by type', function () {
        $targetName = 'Unique Expense Account';
        Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => $targetName],
            'type' => AccountType::Expense->value,
        ]);

        $assetName = 'Unique Asset Account';
        Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => $assetName],
            'type' => AccountType::CurrentAssets->value,
        ]);

        $field = AccountSelectField::make('account_id')
            ->accountFilter(fn ($q) => $q->where('type', AccountType::Expense->value));

        $options = collect($field->getOptions());

        expect($options->values())->toContain($targetName)
            ->and($options->values())->not->toContain($assetName);
    });

    it('scopes accounts to current company', function () {
        $otherCompany = \App\Models\Company::factory()->create();
        $otherAccountName = 'Other Account';

        Account::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => ['en' => $otherAccountName],
        ]);

        $myAccountName = 'My Unique Account';
        Account::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => $myAccountName],
        ]);

        $field = AccountSelectField::make('account_id');
        $options = collect($field->getOptions());

        expect($options->values())->toContain($myAccountName)
            ->and($options->values())->not->toContain($otherAccountName);
    });
});
