<?php

namespace Jmeryar\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages\CreateFiscalPosition;
use Jmeryar\Accounting\Filament\Clusters\Accounting\Resources\FiscalPositions\Pages\ListFiscalPositions;
use Jmeryar\Accounting\Models\FiscalPosition;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

use Filament\Facades\Filament;

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
    $this->actingAs($this->user);
});

describe('FiscalPositionResource', function () {

    it('can render list page', function () {
        FiscalPosition::factory()->for($this->company)->create(['name' => 'Local']);

        Livewire::test(ListFiscalPositions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(FiscalPosition::all());
    });

    it('can render create page', function () {
        Livewire::test(CreateFiscalPosition::class)
            ->assertSuccessful();
    });

    it('can create a fiscal position with auto-apply criteria', function () {
        Livewire::test(CreateFiscalPosition::class)
            ->fillForm([
                'company_id' => $this->company->id,
                'name' => 'EU VAT',
                'auto_apply' => true,
                'vat_required' => true,
                'country' => 'DE',
            ])
            ->set('data.name', ['en' => 'EU VAT']) // Explicitly handle translations if needed, but fillForm might handle it
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('fiscal_positions', [
            'auto_apply' => 1,
            'vat_required' => 1,
            'country' => 'DE',
        ]);
    });

    it('scopes fiscal positions to the active company', function () {
        $positionInCompany = FiscalPosition::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'POSITION-IN-COMPANY',
        ]);

        $otherCompany = \App\Models\Company::factory()->create();
        $positionInOtherCompany = FiscalPosition::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'POSITION-OUT-COMPANY',
        ]);

        Livewire::test(ListFiscalPositions::class)
            ->searchTable('POSITION')
            ->assertCanSeeTableRecords([$positionInCompany])
            ->assertCanNotSeeTableRecords([$positionInOtherCompany]);
    });
});
