<?php

namespace Modules\Accounting\Tests\Feature\Filament\Clusters\Accounting\Resources\Partners;

use App\Models\Company;
use Filament\Facades\Filament;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\CreatePartner;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use Modules\Foundation\Enums\Partners\PartnerType;
use Modules\Foundation\Models\Partner;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $user = \App\Models\User::factory()->create();
    $user->companies()->attach($this->company);

    $this->actingAs($user);
    Filament::setTenant($this->company);
});

it('can create a partner with linked company', function () {
    $otherCompany = Company::factory()->create(['name' => 'Subsidiary B']);

    livewire(CreatePartner::class)
        ->fillForm([
            'name' => 'Subsidiary B Partner',
            'type' => PartnerType::Vendor->value,
            'is_active' => true,
            'linked_company_id' => $otherCompany->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('partners', [
        'name' => 'Subsidiary B Partner',
        'linked_company_id' => $otherCompany->id,
        'company_id' => $this->company->id,
    ]);
});

it('can edit a partner to link to a company', function () {
    $partner = Partner::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Existing Partner',
    ]);

    $otherCompany = Company::factory()->create(['name' => 'Subsidiary C']);

    livewire(EditPartner::class, ['record' => $partner->getRouteKey()])
        ->fillForm([
            'linked_company_id' => $otherCompany->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($partner->fresh()->linked_company_id)->toBe($otherCompany->id);
});
