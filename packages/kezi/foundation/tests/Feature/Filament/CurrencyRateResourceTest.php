<?php

use App\Filament\Clusters\Settings\SettingsCluster;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kezi\Foundation\Filament\Resources\CurrencyRates\CurrencyRateResource;
use Kezi\Foundation\Filament\Resources\CurrencyRates\Pages\CreateCurrencyRate;
use Kezi\Foundation\Filament\Resources\CurrencyRates\Pages\EditCurrencyRate;
use Kezi\Foundation\Filament\Resources\CurrencyRates\Pages\ListCurrencyRates;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\CurrencyRate;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    \Filament\Facades\Filament::setTenant($this->company);
    // Acting as the authenticated user
    $this->actingAs($this->user);
});

it('scopes currency rates to the active company', function () {
    $rateInCompany = CurrencyRate::factory()->create([
        'company_id' => $this->company->id,
        'rate' => 1234.5678,
    ]);

    $otherCompany = \App\Models\Company::factory()->create();
    $rateInOtherCompany = CurrencyRate::factory()->create([
        'company_id' => $otherCompany->id,
        'rate' => 8765.4321,
    ]);

    livewire(ListCurrencyRates::class)
        ->assertCanSeeTableRecords([$rateInCompany])
        ->assertCanNotSeeTableRecords([$rateInOtherCompany]);
});

it('can render the list page', function () {
    $this->get(CurrencyRateResource::getUrl('index'))->assertSuccessful();
});

it('can render the create page', function () {
    $this->get(CurrencyRateResource::getUrl('create'))->assertSuccessful();
});

it('can create a currency rate', function () {
    // Create a new currency for this test with proper translatable name
    $currency = Currency::factory()->createSafely([
        'code' => 'TEST',
        'name' => [
            'en' => 'Test Currency',
            'ar' => 'عملة اختبار',
            'ckb' => 'دراوی تاقیکردنەوە',
        ],
        'symbol' => 'TST',
        'is_active' => true,
    ]);

    $newData = [
        'currency_id' => $currency->id,
        'rate' => 1.2345,
        'effective_date' => now()->format('Y-m-d'),
        'source' => 'manual',
    ];

    livewire(CreateCurrencyRate::class)
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
    // Create currency rates with unique dates to avoid constraint violations
    $currencyRates = collect();
    for ($i = 0; $i < 10; $i++) {
        $currencyRates->push(
            CurrencyRate::factory()->create([
                'company_id' => $this->company->id,
                'effective_date' => now()->subDays($i)->format('Y-m-d'),
            ])
        );
    }

    livewire(ListCurrencyRates::class)
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

    livewire(EditCurrencyRate::class, [
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

    livewire(EditCurrencyRate::class, [
        'record' => $currencyRate->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    $this->assertModelMissing($currencyRate);
});

it('validates required fields', function () {
    livewire(CreateCurrencyRate::class)
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
    // Create a new currency for this test with proper translatable name
    $currency = Currency::factory()->createSafely([
        'code' => 'TEST2',
        'name' => [
            'en' => 'Test Currency 2',
            'ar' => 'عملة اختبار ٢',
            'ckb' => 'دراوی تاقیکردنەوە ٢',
        ],
        'symbol' => 'TST2',
        'is_active' => true,
    ]);

    livewire(CreateCurrencyRate::class)
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
        ->toBe(SettingsCluster::class);
});

it('uses translations for labels', function () {
    expect(CurrencyRateResource::getLabel())
        ->toBe(__('foundation::currency.exchange_rates.label'));

    expect(CurrencyRateResource::getPluralLabel())
        ->toBe(__('foundation::currency.exchange_rates.plural_label'));
});
