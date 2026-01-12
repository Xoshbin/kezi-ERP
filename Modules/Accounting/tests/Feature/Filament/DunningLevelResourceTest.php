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

    it('can create a dunning level with late fees', function () {
        $product = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Late Fee Service',
            'type' => \Modules\Product\Enums\Products\ProductType::Service,
        ]);

        Livewire::test(CreateDunningLevel::class)
            ->fillForm([
                'name' => 'Fee Level',
                'days_overdue' => 15,
                'send_email' => false,
                'charge_fee' => true,
                'fee_product_id' => $product->id,
                'fee_amount' => 50,
                'fee_percentage' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dunning_levels', [
            'company_id' => $this->company->id,
            'name' => 'Fee Level',
            'charge_fee' => 1,
            'fee_product_id' => $product->id,
            'fee_percentage' => 5,
        ]);

        $level = DunningLevel::where('name', 'Fee Level')->first();
        expect($level->fee_amount->getAmount()->toFloat())->toBe(50.0);
    });

    it('can update a dunning level to add late fees', function () {
        $product = \Modules\Product\Models\Product::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Late Fee Service',
            'type' => \Modules\Product\Enums\Products\ProductType::Service,
        ]);

        $level = DunningLevel::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Level 1',
            'charge_fee' => false,
        ]);

        Livewire::test(EditDunningLevel::class, [
            'record' => $level->getRouteKey(),
        ])
            ->fillForm([
                'charge_fee' => true,
            ])
            ->fillForm([
                'fee_product_id' => $product->id,
                'fee_amount' => 25,
                'fee_percentage' => 2.5,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dunning_levels', [
            'id' => $level->id,
            'charge_fee' => 1,
            'fee_product_id' => $product->id,
            'fee_percentage' => 2.5,
        ]);

        $level->refresh();
        expect($level->fee_amount->getAmount()->toFloat())->toBe(25.0);
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
