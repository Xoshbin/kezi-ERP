<?php

namespace Modules\Accounting\Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages\CreateDunningLevel;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages\EditDunningLevel;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\DunningLevelResource\Pages\ListDunningLevels;
use Modules\Accounting\Models\DunningLevel;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('DunningLevelResource', function () {

    it('can render list page', function () {
        DunningLevel::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Reminder 1',
        ]);

        Livewire::test(ListDunningLevels::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(DunningLevel::where('company_id', $this->company->id)->get());
    });

    it('can render create page', function () {
        Livewire::test(CreateDunningLevel::class)
            ->assertSuccessful();
    });

    it('can create a dunning level', function () {
        Livewire::test(CreateDunningLevel::class)
            ->fillForm([
                'name' => 'First Reminder',
                'days_overdue' => 7,
                'send_email' => true,
                'email_subject' => 'Overdue Invoice Reminder',
                'email_body' => 'Your invoice is 7 days overdue.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dunning_levels', [
            'company_id' => $this->company->id,
            'name' => 'First Reminder',
            'days_overdue' => 7,
            'send_email' => 1,
        ]);
    });

    it('can render edit page', function () {
        $level = DunningLevel::factory()->create(['company_id' => $this->company->id]);

        Livewire::test(EditDunningLevel::class, [
            'record' => $level->getRouteKey(),
        ])
            ->assertSuccessful();
    });

    it('can update a dunning level', function () {
        $level = DunningLevel::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        Livewire::test(EditDunningLevel::class, [
            'record' => $level->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'New Name',
                'days_overdue' => 10,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dunning_levels', [
            'id' => $level->id,
            'name' => 'New Name',
            'days_overdue' => 10,
        ]);
    });
});
