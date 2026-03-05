<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Forms\Components;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Forms\Components\TaxSelectField;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\Tax;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
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

        $names = collect($components)->map(fn ($c) => method_exists($c, 'getName') ? $c->getName() : null)->filter()->values()->toArray();

        expect($names)->toContain('tax_account_id')
            ->toContain('name')
            ->toContain('rate')
            ->toContain('type')
            ->toContain('is_active');
    });

    it('can create tax using createOptionUsing with tenant company_id', function () {
        $account = Account::factory()->create(['company_id' => $this->company->id]);

        $field = TaxSelectField::make('tax_id');
        $data = [
            'name' => 'Test Tax',
            'rate' => 15.0,
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales->value,
            'tax_account_id' => $account->id,
            'is_active' => true,
        ];

        /** @var \Closure $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        expect($id)->toBeInt();
        $this->assertDatabaseHas('taxes', [
            'id' => $id,
            'name' => json_encode(['en' => 'Test Tax']),
            'company_id' => $this->company->id,
        ]);
    });

    it('ignores tampered company_id in createOptionUsing and enforces tenant', function () {
        $account = Account::factory()->create(['company_id' => $this->company->id]);
        $otherCompany = \App\Models\Company::factory()->create();

        $field = TaxSelectField::make('tax_id');
        $data = [
            'name' => 'Tampered Tax',
            'rate' => 10.0,
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Purchase->value,
            'tax_account_id' => $account->id,
            'is_active' => true,
            'company_id' => $otherCompany->id,
        ];

        /** @var \Closure $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        $this->assertDatabaseHas('taxes', [
            'id' => $id,
            'company_id' => $this->company->id,
        ]);
        $this->assertDatabaseMissing('taxes', [
            'id' => $id,
            'company_id' => $otherCompany->id,
        ]);
    });

    it('scopes taxes to current company', function () {
        $otherCompany = \App\Models\Company::factory()->create();

        Tax::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => ['en' => 'Other Company Tax'],
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => $this->company->id,
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
        Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Sales Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => $this->company->id,
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
        Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Sales Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Sales,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'Both Tax'],
            'type' => \Kezi\Accounting\Enums\Accounting\TaxType::Both,
            'is_active' => true,
        ]);

        Tax::factory()->create([
            'company_id' => $this->company->id,
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
