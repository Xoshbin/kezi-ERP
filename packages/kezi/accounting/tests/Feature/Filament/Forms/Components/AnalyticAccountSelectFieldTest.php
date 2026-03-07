<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Forms\Components;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Forms\Components\AnalyticAccountSelectField;
use Kezi\Accounting\Models\AnalyticAccount;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

describe('AnalyticAccountSelectField', function () {
    it('can extract schema', function () {
        $field = AnalyticAccountSelectField::make('analytic_account_id');

        expect($field->getName())->toBe('analytic_account_id')
            ->and($field->getLabel())->toBe(__('accounting::analytic_account.label'));
    });

    it('has create option form with correct fields', function () {
        $field = AnalyticAccountSelectField::make('analytic_account_id');

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

    it('can create analytic account using createOptionUsing with tenant company_id', function () {
        $field = AnalyticAccountSelectField::make('analytic_account_id');
        $data = [
            'name' => 'Test Analytic Account',
            'code' => 'TAA001',
        ];

        /** @var \Closure $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        expect($id)->toBeInt();
        $this->assertDatabaseHas('analytic_accounts', [
            'id' => $id,
            'name' => json_encode(['en' => 'Test Analytic Account']),
            'code' => 'TAA001',
            'company_id' => $this->company->id,
        ]);
    });

    it('scopes analytic accounts to current company', function () {
        $otherCompany = \App\Models\Company::factory()->create();

        AnalyticAccount::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => ['en' => 'Other Analytic Account'],
        ]);

        AnalyticAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => ['en' => 'My Analytic Account'],
        ]);

        $field = AnalyticAccountSelectField::make('analytic_account_id');
        $options = collect($field->getOptions());

        expect($options->values())->toContain('My Analytic Account')
            ->and($options->values())->not->toContain('Other Analytic Account');
    });
});
