<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Kezi\Accounting\Filament\Forms\Components\TaxSelectField;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Tests\Traits\WithConfiguredCompany;

/** @var \Tests\TestCase $this */
uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    $test->setupWithConfiguredCompany();
});

describe('TaxSelectField', function () {
    it('can extract schema', function () {
        $field = TaxSelectField::make('tax_id');

        expect($field->getName())->toBe('tax_id')
            ->and($field->getLabel())->toBe(__('accounting::tax.label'));
    });

    it('has create option form with correct fields', function () {
        $field = TaxSelectField::make('tax_id');

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

        $names = collect((array) $components)->map(function (mixed $c) {
            return ($c instanceof Field) ? $c->getName() : null;
        })->filter()->values()->toArray();

        expect($names)->toContain('tax_account_id')
            ->toContain('name')
            ->toContain('rate')
            ->toContain('type')
            ->toContain('is_active');
    });

    it('can create tax using createOptionUsing with tenant company_id', function () {
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();
        $account = Account::factory()->create(['company_id' => (int) $company->getKey()]);

        $field = TaxSelectField::make('tax_id');
        $data = [
            'name' => 'Test Tax',
            'rate' => 15.0,
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales->value,
            'tax_account_id' => (int) $account->getKey(),
            'is_active' => true,
        ];

        /** @var \Closure $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        expect($id)->toBeInt();
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();
        /** @var \Tests\TestCase $test */
        $test = $this;
        $test->assertDatabaseHas('taxes', [
            'id' => $id,
            'name' => json_encode(['en' => 'Test Tax']),
            'company_id' => (int) $company->getKey(),
        ]);
    });

    it('ignores tampered company_id in createOptionUsing and enforces tenant', function () {
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();
        $account = Account::factory()->create(['company_id' => (int) $company->getKey()]);
        $otherCompany = \App\Models\Company::factory()->create();

        $field = TaxSelectField::make('tax_id');
        $data = [
            'name' => 'Tampered Tax',
            'rate' => 10.0,
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase->value,
            'tax_account_id' => (int) $account->getKey(),
            'is_active' => true,
            'company_id' => (int) $otherCompany->getKey(),
        ];

        /** @var \Closure $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();
        /** @var \Tests\TestCase $test */
        $test = $this;
        $test->assertDatabaseHas('taxes', [
            'id' => $id,
            'company_id' => (int) $company->getKey(),
        ]);
        $test->assertDatabaseMissing('taxes', [
            'id' => $id,
            'company_id' => (int) $otherCompany->getKey(),
        ]);
    });

    it('scopes taxes to current company', function () {
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();
        $otherCompany = \App\Models\Company::factory()->create();

        Tax::factory()->create([
            'company_id' => (int) $otherCompany->getKey(),
            'name' => ['en' => 'Other Company Tax'],
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'My Company Tax'],
            'is_active' => true,
        ]);

        $field = TaxSelectField::make('tax_id');
        $options = collect($field->getOptions());

        expect($options->values())->toContain('My Company Tax')
            ->and($options->values())->not->toContain('Other Company Tax');
    });
});

describe('TaxSelectField Filtering and Defaults', function () {
    it('can filter taxes by type', function () {
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'Sales Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'Purchase Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase,
            'is_active' => true,
        ]);

        $field = TaxSelectField::make('tax_id')
            ->taxFilter(\Kezi\Accounting\Enums\Accounting\TaxType::Sales);

        $options = collect($field->getOptions());

        expect($options->values())->toContain('Sales Tax')
            ->and($options->values())->not->toContain('Purchase Tax');
    });

    it('can filter taxes by multiple types', function () {
        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'Sales Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'Both Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Both,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => (int) $company->getKey(),
            'name' => ['en' => 'Purchase Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase,
            'is_active' => true,
        ]);

        $field = TaxSelectField::make('tax_id')
            ->taxFilter([\Kezi\Accounting\Enums\Accounting\TaxType::Sales, \Kezi\Accounting\Enums\Accounting\TaxType::Both]);

        $options = collect($field->getOptions());

        expect($options->values())->toContain('Sales Tax')
            ->toContain('Both Tax')
            ->and($options->values())->not->toContain('Purchase Tax');
    });

    it('sets default tax type for creation', function () {
        $field = TaxSelectField::make('tax_id')
            ->createOptionDefaultType(\Kezi\Accounting\Enums\Accounting\TaxType::Sales);

        expect($field->getDefaultTaxType())->toBe(\Kezi\Accounting\Enums\Accounting\TaxType::Sales);
    });
});
