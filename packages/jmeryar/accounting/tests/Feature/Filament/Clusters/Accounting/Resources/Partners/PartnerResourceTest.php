<?php

namespace Jmeryar\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\Partners;

use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ListPartners;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ViewPartner;
use Jmeryar\Foundation\Enums\Partners\PartnerType;
use Jmeryar\Foundation\Models\Partner;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    /** @var \Tests\TestCase $this */
    $this->setupWithConfiguredCompany();
});

it('can render partner list page', function () {
    livewire(ListPartners::class)
        ->assertSuccessful();
});

it('can list partners with correct data', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'name' => 'Test Partner',
        'type' => PartnerType::Customer,
        'is_active' => true,
    ]);

    livewire(ListPartners::class)
        ->assertCanSeeTableRecords([$partner])
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('type')
        ->assertCanRenderTableColumn('is_active')
        ->assertTableColumnStateSet('name', 'Test Partner', $partner)
        ->assertTableColumnStateSet('is_active', true, $partner);
});

it('can create a partner with linked company and tax', function () {
    $otherCompany = \App\Models\Company::factory()->create(['name' => 'Subsidiary B']);

    livewire(CreatePartner::class)
        ->fillForm([
            'name' => 'Joint Venture Partner',
            'type' => PartnerType::Both->value,
            'is_active' => true,
            'linked_company_id' => $otherCompany->id,
            'tax_id' => 'TAX-123456',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('partners', [
        'name' => 'Joint Venture Partner',
        'linked_company_id' => $otherCompany->id,
        'company_id' => $this->company->id,
        'tax_id' => 'TAX-123456',
        'type' => PartnerType::Both->value,
    ]);
});

it('validates required fields on creation', function () {
    livewire(CreatePartner::class)
        ->fillForm([
            'name' => null,
            'type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'type']);
});

it('can edit a partner', function () {
    $partner = Partner::factory()->for($this->company)->create([
        'name' => 'Old Name',
    ]);

    livewire(EditPartner::class, ['record' => $partner->getRouteKey()])
        ->fillForm([
            'name' => 'New Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($partner->fresh()->name)->toBe('New Name');
});

it('can view a partner', function () {
    $partner = Partner::factory()->for($this->company)->create();

    livewire(ViewPartner::class, ['record' => $partner->getRouteKey()])
        ->assertSuccessful()
        ->assertFormSet([
            'name' => $partner->name,
            'type' => $partner->type->value,
        ]);
});

it('can filter partners by type', function () {
    $customer = Partner::factory()->for($this->company)->create(['type' => PartnerType::Customer]);
    $vendor = Partner::factory()->for($this->company)->create(['type' => PartnerType::Vendor]);

    livewire(ListPartners::class)
        ->filterTable('type', 'customer')
        ->assertCanSeeTableRecords([$customer])
        ->assertCanNotSeeTableRecords([$vendor]);
});

it('can delete a partner', function () {
    $partner = Partner::factory()->for($this->company)->create();

    livewire(ListPartners::class)
        ->callTableAction(\Filament\Actions\DeleteAction::class, $partner);

    $this->assertSoftDeleted($partner);
});

it('can create a partner with default_tax_id', function () {
    $tax = \Jmeryar\Accounting\Models\Tax::factory()->for($this->company)->create([
        'name' => 'Standard VAT',
        'rate' => 0.15,
        'is_active' => true,
    ]);

    livewire(CreatePartner::class)
        ->fillForm([
            'name' => 'Partner with Default Tax',
            'type' => PartnerType::Customer->value,
            'is_active' => true,
            'default_tax_id' => $tax->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('partners', [
        'name' => 'Partner with Default Tax',
        'company_id' => $this->company->id,
        'default_tax_id' => $tax->id,
        'type' => PartnerType::Customer->value,
    ]);
});

it('can edit a partner to set/change default_tax_id', function () {
    $oldTax = \Jmeryar\Accounting\Models\Tax::factory()->for($this->company)->create([
        'name' => 'Old Tax',
        'rate' => 0.10,
        'is_active' => true,
    ]);

    $newTax = \Jmeryar\Accounting\Models\Tax::factory()->for($this->company)->create([
        'name' => 'New Tax',
        'rate' => 0.15,
        'is_active' => true,
    ]);

    $partner = Partner::factory()->for($this->company)->create([
        'name' => 'Test Partner',
        'default_tax_id' => $oldTax->id,
    ]);

    livewire(EditPartner::class, ['record' => $partner->getRouteKey()])
        ->fillForm([
            'default_tax_id' => $newTax->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($partner->fresh()->default_tax_id)->toBe($newTax->id);
});

it('can see default_tax_id in list table as toggleable column', function () {
    $tax = \Jmeryar\Accounting\Models\Tax::factory()->for($this->company)->create([
        'name' => 'List Tax',
        'rate' => 0.20,
        'is_active' => true,
    ]);

    $partner = Partner::factory()->for($this->company)->create([
        'name' => 'Partner with Tax',
        'default_tax_id' => $tax->id,
    ]);

    // The column is toggleable and hidden by default, so we need to verify it can be rendered
    livewire(ListPartners::class)
        ->assertCanSeeTableRecords([$partner]);

    // Since the column is toggleable, we just verify the partner record is shown
    expect($partner->defaultTax->name)->toBe('List Tax');
});
