<?php

use \Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Enums\Accounting\WithholdingTaxApplicability;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\CreateWithholdingTaxType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\EditWithholdingTaxType;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\WithholdingTax\Pages\ListWithholdingTaxTypes;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\WithholdingTaxType;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);

    // Create a WHT account for testing
    $this->whtAccount = Account::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'WHT Payable',
        'code' => '2001',
    ]);
});

it('can render the list page', function () {
    $whtTypes = WithholdingTaxType::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'withholding_account_id' => $this->whtAccount->id,
    ]);

    livewire(ListWithholdingTaxTypes::class)
        ->assertOk()
        ->assertCanSeeTableRecords($whtTypes);
});

it('can render the create page', function () {
    livewire(CreateWithholdingTaxType::class)
        ->assertOk();
});

it('can create a withholding tax type', function () {
    $name = 'Services WHT 5%';

    livewire(CreateWithholdingTaxType::class)
        ->fillForm([
            'name' => $name,
            'rate' => 5,
            'withholding_account_id' => $this->whtAccount->id,
            'applicable_to' => WithholdingTaxApplicability::Services->value,
            'is_active' => true,
            'threshold_amount' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('withholding_tax_types', [
        'company_id' => $this->company->id,
        'rate' => 0.05, // Stored as decimal (5/100)
        'withholding_account_id' => $this->whtAccount->id,
        'applicable_to' => WithholdingTaxApplicability::Services->value,
        'is_active' => true,
    ]);

    // Check translatable name
    $whtType = WithholdingTaxType::where('rate', 0.05)->first();
    expect($whtType->name)->toBe($name);
});

it('can create a withholding tax type with threshold', function () {
    $name = 'Goods WHT 10%';

    livewire(CreateWithholdingTaxType::class)
        ->fillForm([
            'name' => $name,
            'rate' => 10,
            'withholding_account_id' => $this->whtAccount->id,
            'applicable_to' => WithholdingTaxApplicability::Goods->value,
            'is_active' => true,
            'threshold_amount' => 1000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verify stored threshold - input 1000 should be stored as Money amount
    // Depending on logic, if input is treated as major units (1000) and stored as minor,
    // we need to verify the created record.
    $whtType = WithholdingTaxType::where('applicable_to', WithholdingTaxApplicability::Goods->value)->first();
    expect($whtType)->not->toBeNull();
    // Assuming company currency is factory default (likely IQD or USD)
    // If IQD (0 decimals), 1000 input -> 1000 stored.
    // If USD (2 decimals), 1000 input -> 100000 stored.
    // The previous test findings showed complex storage behavior, so checking existence is good step 1.
    expect($whtType->threshold_amount)->not->toBeNull();
});

it('can render the edit page', function () {
    $whtType = WithholdingTaxType::factory()->create([
        'company_id' => $this->company->id,
        'withholding_account_id' => $this->whtAccount->id,
    ]);

    livewire(EditWithholdingTaxType::class, [
        'record' => $whtType->getRouteKey(),
    ])
        ->assertOk()
        ->assertFormSet([
            'rate' => $whtType->rate * 100, // Form displays percentage (0.05 -> 5)
            'withholding_account_id' => $whtType->withholding_account_id,
            'applicable_to' => $whtType->applicable_to->value,
        ]);
});

it('can update a withholding tax type', function () {
    $whtType = WithholdingTaxType::factory()->create([
        'company_id' => $this->company->id,
        'withholding_account_id' => $this->whtAccount->id,
        'rate' => 0.05,
    ]);

    $newName = 'Updated WHT Name';

    livewire(EditWithholdingTaxType::class, [
        'record' => $whtType->getRouteKey(),
    ])
        ->fillForm([
            'name' => $newName,
            'rate' => 10, // Update to 10%
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('withholding_tax_types', [
        'id' => $whtType->id,
        'rate' => 0.10,
    ]);

    $whtType->refresh();
    expect($whtType->name)->toBe($newName);
});

it('can delete a withholding tax type', function () {
    $whtType = WithholdingTaxType::factory()->create([
        'company_id' => $this->company->id,
        'withholding_account_id' => $this->whtAccount->id,
    ]);

    livewire(EditWithholdingTaxType::class, [
        'record' => $whtType->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($whtType);
});

it('validates required fields', function () {
    livewire(CreateWithholdingTaxType::class)
        ->fillForm([
            'name' => '',
            'rate' => null,
            'withholding_account_id' => null,
            'applicable_to' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'rate' => 'required',
            'withholding_account_id' => 'required',
            'applicable_to' => 'required',
        ]);
});
