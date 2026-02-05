<?php

namespace Kezi\Accounting\Tests\Feature\Filament\Resources;

use \Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages\CreateChequebook;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages\EditChequebook;
use Kezi\Accounting\Filament\Clusters\Accounting\Resources\Cheques\ChequebookResource\Pages\ListChequebooks;
use Kezi\Payment\Models\Chequebook;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render current company chequebooks only', function () {
    // Arrange
    $chequebooks = Chequebook::factory()
        ->for($this->company)
        ->count(2)
        ->create();

    $otherCompanyChequebook = Chequebook::factory()->create(); // Belongs to another company

    // Act & Assert
    livewire(ListChequebooks::class)
        ->assertOk()
        ->assertCanSeeTableRecords($chequebooks)
        ->assertCanNotSeeTableRecords([$otherCompanyChequebook]);
});

it('can create a chequebook', function () {
    $bankJournal = \Kezi\Accounting\Models\Journal::factory()->for($this->company)->create([
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
    ]);

    // Act
    livewire(CreateChequebook::class)
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'name' => 'Main Bank Chequebook',
            'bank_account_number' => '123456789',
            'bank_name' => 'KIB Bank',
            'is_active' => true,
            'prefix' => 'KIB',
            'digits' => 6,
            'start_number' => 100,
            'end_number' => 200,
            'next_number' => 100,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    // Assert
    $this->assertDatabaseHas('chequebooks', [
        'name' => 'Main Bank Chequebook',
        'bank_account_number' => '123456789',
        'company_id' => $this->company->id,
    ]);
});

it('can edit a chequebook', function () {
    $bankJournal = \Kezi\Accounting\Models\Journal::factory()->for($this->company)->create([
        'type' => \Kezi\Accounting\Enums\Accounting\JournalType::Bank,
    ]);

    $chequebook = Chequebook::factory()->for($this->company)->create([
        'journal_id' => $bankJournal->id,
        'name' => 'Old Name',
    ]);

    // Act
    livewire(EditChequebook::class, [
        'record' => $chequebook->getRouteKey(),
    ])
        ->fillForm([
            'journal_id' => $bankJournal->id,
            'name' => 'Updated Name',
            'end_number' => 500,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Assert
    expect($chequebook->refresh()->name)->toBe('Updated Name');
});

it('can delete a chequebook', function () {
    // Arrange
    $chequebook = Chequebook::factory()->for($this->company)->create();

    // Act
    livewire(EditChequebook::class, [
        'record' => $chequebook->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    // Assert
    $this->assertModelMissing($chequebook);
});
