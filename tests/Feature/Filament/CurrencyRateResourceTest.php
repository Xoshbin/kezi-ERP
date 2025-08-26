<?php

use App\Filament\Clusters\Settings\Resources\CurrencyRates\CurrencyRateResource;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\WithConfiguredCompany;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('can render the list page', function () {
    $this->get(CurrencyRateResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(CurrencyRateResource::getUrl('create'))->assertSuccessful();
});

it('can create a currency rate', function () {
    $currency = Currency::factory()->create();

    $newData = [
        'currency_id' => $currency->id,
        'rate' => 1.2345,
        'effective_date' => now()->format('Y-m-d'),
        'source' => 'manual',
    ];

    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\CreateCurrencyRate::class)
        ->fillForm($newData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('currency_rates', [
        'currency_id' => $currency->id,
        'rate' => 1.2345,
        'source' => 'manual',
        'company_id' => $this->company->id,
    ]);

    // Check the effective_date separately since it's stored as datetime
    $currencyRate = CurrencyRate::where('currency_id', $currency->id)
        ->where('company_id', $this->company->id)
        ->first();
    expect($currencyRate->effective_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

it('can render the edit page', function () {
    $currencyRate = CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->get(CurrencyRateResource::getUrl('edit', ['record' => $currencyRate]))
        ->assertSuccessful();
});

it('can retrieve data', function () {
    $currencyRates = CurrencyRate::factory()->count(10)->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\ListCurrencyRates::class)
        ->assertCanSeeTableRecords($currencyRates);
});

it('can edit a currency rate', function () {
    $currencyRate = CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $newData = [
        'rate' => 2.5678,
        'source' => 'api',
    ];

    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\EditCurrencyRate::class, [
        'record' => $currencyRate->getRouteKey(),
    ])
        ->fillForm($newData)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($currencyRate->refresh())
        ->rate->toBe('2.5678000000')
        ->source->toBe('api');
});

it('can delete a currency rate', function () {
    $currencyRate = CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
    ]);

    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\EditCurrencyRate::class, [
        'record' => $currencyRate->getRouteKey(),
    ])
        ->callAction(\Filament\Actions\DeleteAction::class);

    $this->assertModelMissing($currencyRate);
});

it('validates required fields', function () {
    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\CreateCurrencyRate::class)
        ->fillForm([
            'currency_id' => null,
            'rate' => null,
            'effective_date' => null,
            'source' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'currency_id' => 'required',
            'rate' => 'required',
            'effective_date' => 'required',
            'source' => 'required',
        ]);
});

it('validates rate is numeric and positive', function () {
    $currency = Currency::factory()->create();

    livewire(\App\Filament\Clusters\Settings\Resources\CurrencyRates\Pages\CreateCurrencyRate::class)
        ->fillForm([
            'currency_id' => $currency->id,
            'rate' => -1.5,
            'effective_date' => now()->format('Y-m-d'),
            'source' => 'manual',
        ])
        ->call('create')
        ->assertHasFormErrors(['rate']);
});

it('is in the settings cluster', function () {
    expect(CurrencyRateResource::getCluster())
        ->toBe(\App\Filament\Clusters\Settings\SettingsCluster::class);
});

it('uses translations for labels', function () {
    expect(CurrencyRateResource::getLabel())
        ->toBe(__('currency.exchange_rates.label'));

    expect(CurrencyRateResource::getPluralLabel())
        ->toBe(__('currency.exchange_rates.plural_label'));
});
