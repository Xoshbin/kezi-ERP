<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\TaxType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\Pages\CreateTax;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\Pages\EditTax;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Taxes\Pages\ListTaxes;
use Kezi\Accounting\Models\Tax;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render the tax list page', function () {
    livewire(ListTaxes::class)
        ->assertSuccessful();
});

it('can list taxes', function () {
    $taxes = Tax::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListTaxes::class)
        ->assertCanSeeTableRecords($taxes)
        ->assertCountTableRecords(3);
});

it('can render create tax page', function () {
    livewire(CreateTax::class)
        ->assertSuccessful();
});

it('can create a new tax', function () {
    $taxAccount = \Kezi\Accounting\Models\Account::factory()->for($this->company)->create();

    $newData = [
        'name' => 'VAT 15%',
        'rate' => 15,
        'type' => TaxType::Sales->value,
        'is_active' => true,
        'is_group' => false,
        'report_tag' => 'VAT15',
        'tax_account_id' => $taxAccount->id,
    ];

    livewire(CreateTax::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('taxes', [
        'company_id' => $this->company->id,
        'name' => json_encode(['en' => 'VAT 15%']),
        'rate' => 15,
    ]);
});

it('can render edit tax page', function () {
    $tax = Tax::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditTax::class, [
        'record' => $tax->getRouteKey(),
    ])
        ->assertSuccessful();
});

it('can update a tax', function () {
    $tax = Tax::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditTax::class, [
        'record' => $tax->getRouteKey(),
    ])
        ->fillForm([
            'name' => 'Updated VAT',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tax->refresh()->getTranslation('name', 'en'))->toBe('Updated VAT');
});

it('can delete a tax', function () {
    $tax = Tax::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(EditTax::class, [
        'record' => $tax->getRouteKey(),
    ])
        ->callAction('delete')
        ->assertHasNoActionErrors();

    $this->assertDatabaseMissing('taxes', [
        'id' => $tax->id,
    ]);
});

it('scopes taxes to the active company', function () {
    $taxInCompany = Tax::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $taxInOtherCompany = Tax::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    livewire(ListTaxes::class)
        ->assertCanSeeTableRecords([$taxInCompany])
        ->assertCanNotSeeTableRecords([$taxInOtherCompany]);
});
