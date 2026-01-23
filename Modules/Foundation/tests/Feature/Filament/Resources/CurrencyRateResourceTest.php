<?php

namespace Modules\Foundation\Tests\Feature\Filament\Resources;

use Livewire\Livewire;
use Modules\Foundation\Filament\Resources\CurrencyRates\Pages\CreateCurrencyRate;
use Modules\Foundation\Filament\Resources\CurrencyRates\Pages\ListCurrencyRates;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

it('can render currency rates list', function () {
    $this->actingAs($this->user);

    CurrencyRate::factory()
        ->count(5)
        ->for($this->company)
        ->sequence(fn ($sequence) => [
            'effective_date' => now()->subDays($sequence->index)->format('Y-m-d'),
        ])
        ->create();

    Livewire::test(ListCurrencyRates::class)
        ->assertSuccessful();
});

it('can list currency rates', function () {
    $this->actingAs($this->user);

    $rates = CurrencyRate::factory()
        ->count(5)
        ->for($this->company)
        ->sequence(fn ($sequence) => [
            'effective_date' => now()->subDays($sequence->index)->format('Y-m-d'),
        ])
        ->create();

    Livewire::test(ListCurrencyRates::class)
        ->assertCanSeeTableRecords($rates);
});

it('can create a currency rate', function () {
    $this->actingAs($this->user);

    $currency = Currency::factory()->create();

    Livewire::test(CreateCurrencyRate::class)
        ->fillForm([
            'currency_id' => $currency->id,
            'rate' => 1.5,
            // Use a unique past date to avoid conflicts with other tests and satisfy maxDate validation
            'effective_date' => $effectiveDate = now()->subYears(10)->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('currency_rates', [
        'company_id' => $this->company->id,
        'currency_id' => $currency->id,
        'rate' => 1.5,
    ]);
});

it('validates currency rate input', function () {
    $this->actingAs($this->user);

    Livewire::test(CreateCurrencyRate::class)
        ->fillForm([
            'rate' => -1, // Invalid negative rate
        ])
        ->call('create')
        ->assertHasFormErrors(['rate']);
});
