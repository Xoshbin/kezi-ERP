<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Partners\Pages\EditPartner;
use Kezi\Accounting\Models\FiscalPosition;
use Kezi\Foundation\Models\Partner;
use Livewire\Livewire;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

describe('PartnerResource Fiscal Position', function () {

    it('can set fiscal position on partner', function () {
        $partner = Partner::factory()->for($this->company)->create([
            'tax_id' => null, // Avoid validation error as it expects a foreign key
        ]);
        $fp = FiscalPosition::factory()->for($this->company)->create(['name' => 'Foreign']);

        Livewire::test(EditPartner::class, [
            'record' => $partner->id,
        ])
            ->fillForm([
                'fiscal_position_id' => $fp->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($partner->fresh()->fiscal_position_id)->toBe($fp->id);
    });
});
