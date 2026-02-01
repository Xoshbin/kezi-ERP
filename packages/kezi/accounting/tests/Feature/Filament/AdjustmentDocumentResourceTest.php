<?php

namespace Kezi\Accounting\Tests\Feature\Filament;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\CreateAdjustmentDocument;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\AdjustmentDocuments\Pages\ListAdjustmentDocuments;
use Kezi\Inventory\Models\AdjustmentDocument;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Filament::setTenant($this->company);
});

it('can render the adjustment document list page', function () {
    livewire(ListAdjustmentDocuments::class)
        ->assertSuccessful();
});

it('can list adjustment documents', function () {
    $documents = AdjustmentDocument::factory()->count(3)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(ListAdjustmentDocuments::class)
        ->assertCanSeeTableRecords($documents)
        ->assertCountTableRecords(3);
});

it('can render create adjustment document page', function () {
    livewire(CreateAdjustmentDocument::class)
        ->assertSuccessful();
});

it('scopes adjustment documents to the active company', function () {
    $docInCompany = AdjustmentDocument::factory()->create([
        'company_id' => $this->company->id,
        'reference_number' => 'ADJ-IN-COMPANY',
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $docInOtherCompany = AdjustmentDocument::factory()->create([
        'company_id' => $otherCompany->id,
        'reference_number' => 'ADJ-OUT-COMPANY',
    ]);

    livewire(ListAdjustmentDocuments::class)
        ->searchTable('ADJ')
        ->assertCanSeeTableRecords([$docInCompany])
        ->assertCanNotSeeTableRecords([$docInOtherCompany]);
});
