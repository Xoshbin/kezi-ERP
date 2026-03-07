<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Forms\Components;

/**
 * @property-read \App\Models\Company $company
 */
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Forms\Components\AnalyticAccountSelectField;
use Kezi\Accounting\Models\AnalyticAccount;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $test */
    $test = $this;
    $test->setupWithConfiguredCompany();
});

describe('AnalyticAccountSelectField', function () {
    it('can extract schema', function () {
        $field = AnalyticAccountSelectField::make('analytic_account_id');

        expect($field->getName())->toBe('analytic_account_id')
            ->and($field->getLabel())->toBe(__('accounting::analytic_account.analytic_account'));
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
            'reference' => 'TAA001',
        ];

        /** @var \Closure(array<string, mixed>): int $callback */
        $callback = $field->getCreateOptionUsing();
        $id = $callback($data);

        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        expect($id)->toBeInt();
        $this->assertDatabaseHas('analytic_accounts', [
            'id' => $id,
            'name' => 'Test Analytic Account',
            'reference' => 'TAA001',
            'company_id' => $company->id,
        ]);
    });

    it('scopes analytic accounts to current company', function () {
        /** @var \App\Models\Company $otherCompany */
        $otherCompany = \App\Models\Company::factory()->create();

        AnalyticAccount::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Analytic Account',
        ]);

        /** @var \App\Models\Company $company */
        $company = Filament::getTenant();

        AnalyticAccount::factory()->create([
            'company_id' => $company->id,
            'name' => 'My Analytic Account',
        ]);

        $field = AnalyticAccountSelectField::make('analytic_account_id');
        $options = collect($field->getOptions());

        expect($options->values())->toContain('My Analytic Account')
            ->and($options->values())->not->toContain('Other Analytic Account');
    });
});
