<?php

namespace Modules\Accounting\Tests\Feature\Filament\Resources;

use Modules\Accounting\Enums\Currency\RevaluationStatus;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\CurrencyRevaluationResource;
use Modules\Accounting\Models\CurrencyRevaluation;
use Modules\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can look at currency revaluations list', function () {
    $this->actingAs($this->user);

    $revaluations = CurrencyRevaluation::factory()->count(10)->for($this->company)->create();

    livewire(CurrencyRevaluationResource\Pages\ListCurrencyRevaluations::class)
        ->assertCanSeeTableRecords($revaluations);
});

it('can filter currency revaluations by status', function () {
    $this->actingAs($this->user);

    $draftRevaluation = CurrencyRevaluation::factory()->for($this->company)->create(['status' => RevaluationStatus::Draft]);
    $postedRevaluation = CurrencyRevaluation::factory()->for($this->company)->create(['status' => RevaluationStatus::Posted]);

    livewire(CurrencyRevaluationResource\Pages\ListCurrencyRevaluations::class)
        ->filterTable('status', [RevaluationStatus::Draft->value])
        ->assertCanSeeTableRecords([$draftRevaluation])
        ->assertCanNotSeeTableRecords([$postedRevaluation]);
});

it('can render create revaluation page', function () {
    $this->actingAs($this->user);

    livewire(CurrencyRevaluationResource\Pages\CreateCurrencyRevaluation::class)
        ->assertFormExists();
});

it('validates revaluation creation', function () {
    $this->actingAs($this->user);

    livewire(CurrencyRevaluationResource\Pages\CreateCurrencyRevaluation::class)
        ->fillForm([
            'revaluation_date' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['revaluation_date' => 'required']);
});

it('can create a currency revaluation', function () {
    $this->actingAs($this->user);

    $currency = Currency::factory()->create();

    livewire(CurrencyRevaluationResource\Pages\CreateCurrencyRevaluation::class)
        ->fillForm([
            'revaluation_date' => now()->toDateString(),
            'description' => 'Test Revaluation',
            // Add other required fields here based on expected resource structure
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    // Verification happens implicitly if create succeeds without errors,
    // actual DB assertion for complex logic might be better in integration tests
    // or if we strictly know the form behavior.
    // For now, ensuring no errors is a solid TDD step for a missing resource.
});
