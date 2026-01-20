<?php

namespace Modules\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\Partners;

use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ListPartners;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\ViewPartner;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;
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
