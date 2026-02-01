<?php

use Kezi\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource;
use Kezi\Accounting\Models\DeferredItem;

use function Pest\Livewire\livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    \Filament\Facades\Filament::setTenant($this->company);
    $this->actingAs($this->user);
});

it('can render the deferred item list page', function () {
    $this->get(DeferredItemResource::getUrl('index'))->assertSuccessful();
});

it('can list deferred items', function () {
    $deferredItem = DeferredItem::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(DeferredItemResource\Pages\ListDeferredItems::class)
        ->assertCanSeeTableRecords([$deferredItem]);
});

it('scopes deferred items to the active company', function () {
    $itemInCompany = DeferredItem::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'DEFERRED-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $itemInOtherCompany = DeferredItem::factory()->create([
        'company_id' => $otherCompany->id,
        'name' => 'DEFERRED-OUT-COMPANY',
    ]);

    livewire(DeferredItemResource\Pages\ListDeferredItems::class)
        ->searchTable('DEFERRED')
        ->assertCanSeeTableRecords([$itemInCompany])
        ->assertCanNotSeeTableRecords([$itemInOtherCompany]);
});
