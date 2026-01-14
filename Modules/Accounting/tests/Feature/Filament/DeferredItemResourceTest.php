<?php

use Modules\Accounting\Filament\Clusters\Accounting\Resources\DeferredItemResource;
use Modules\Accounting\Models\DeferredItem;

use function Pest\Livewire\livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class, \Tests\Traits\WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
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
