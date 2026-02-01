<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Jmeryar\Foundation\Filament\Resources\Currencies\CurrencyResource;
use Jmeryar\Foundation\Filament\Resources\Currencies\Pages\CreateCurrency;
use Jmeryar\Foundation\Filament\Resources\Currencies\Pages\EditCurrency;
use Jmeryar\Foundation\Filament\Resources\Currencies\Pages\ListCurrencies;
use Jmeryar\Foundation\Models\Currency;
use Tests\Traits\WithConfiguredCompany;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

// ==========================================
// Table Listing Tests
// ==========================================

it('can render currency list page', function () {
    $this->get(CurrencyResource::getUrl('index'))
        ->assertSuccessful();
});

it('displays currencies in the table', function () {
    $currency = Currency::factory()->create([
        'code' => 'TST',
        'name' => 'Test Currency',
        'symbol' => 'T$',
        'is_active' => true,
    ]);

    Livewire::test(ListCurrencies::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$currency])
        ->assertTableColumnExists('code')
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('symbol')
        ->assertTableColumnExists('exchange_rate')
        ->assertTableColumnExists('is_active');
});

it('can search currencies by code', function () {
    $usdCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
    ]);

    $eurCurrency = Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
    ]);

    Livewire::test(ListCurrencies::class)
        ->searchTable('USD')
        ->assertCanSeeTableRecords([$usdCurrency])
        ->assertCanNotSeeTableRecords([$eurCurrency]);
});

it('can search currencies by name', function () {
    $usdCurrency = Currency::factory()->create([
        'code' => 'USD',
        'name' => 'US Dollar',
        'symbol' => '$',
    ]);

    $eurCurrency = Currency::factory()->create([
        'code' => 'EUR',
        'name' => 'Euro',
        'symbol' => '€',
    ]);

    Livewire::test(ListCurrencies::class)
        ->searchTable('Euro')
        ->assertCanSeeTableRecords([$eurCurrency])
        ->assertCanNotSeeTableRecords([$usdCurrency]);
});

// ==========================================
// Create Page Tests
// ==========================================

it('can render create currency page', function () {
    $this->get(CurrencyResource::getUrl('create'))
        ->assertSuccessful();
});

it('can create a currency with valid data', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'NEW',
            'name' => 'New Currency',
            'symbol' => 'N$',
            'exchange_rate' => 1.5,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('currencies', [
        'code' => 'NEW',
        'symbol' => 'N$',
        'is_active' => true,
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => '',
            'name' => '',
            'symbol' => '',
            'exchange_rate' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
            'symbol' => 'required',
            'exchange_rate' => 'required',
        ]);
});

it('validates code max length', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => str_repeat('A', 256),
            'name' => 'Test Currency',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'max']);
});

it('validates symbol max length', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'TST',
            'name' => 'Test Currency',
            'symbol' => 'TOOLONG',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['symbol' => 'max']);
});

it('validates exchange rate is numeric', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'code' => 'TST',
            'name' => 'Test Currency',
            'symbol' => '$',
            'exchange_rate' => 'not-a-number',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['exchange_rate' => 'numeric']);
});

// ==========================================
// Edit Page Tests
// ==========================================

it('can render edit currency page', function () {
    $currency = Currency::factory()->create([
        'code' => 'EDI',
        'name' => 'Edit Currency',
        'symbol' => 'E$',
    ]);

    $this->get(CurrencyResource::getUrl('edit', ['record' => $currency]))
        ->assertSuccessful();
});

it('can update an existing currency', function () {
    $currency = Currency::factory()->create([
        'code' => 'OLD',
        'name' => 'Old Currency',
        'symbol' => 'O$',
        'is_active' => true,
    ]);

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->fillForm([
            'code' => 'UPD',
            'name' => 'Updated Currency',
            'symbol' => 'U$',
            'exchange_rate' => 2.5,
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('currencies', [
        'id' => $currency->id,
        'code' => 'UPD',
        'symbol' => 'U$',
        'is_active' => false,
    ]);
});

it('loads existing currency data in edit form', function () {
    $currency = Currency::factory()->create([
        'code' => 'LOD',
        'name' => 'Load Currency',
        'symbol' => 'L$',
        'is_active' => true,
    ]);

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->assertFormSet([
            'code' => 'LOD',
            'symbol' => 'L$',
            'is_active' => true,
        ]);
});

// ==========================================
// Delete Tests
// ==========================================

it('can delete a currency from edit page', function () {
    $currency = Currency::factory()->create([
        'code' => 'DEL',
        'name' => 'Delete Currency',
        'symbol' => 'D$',
    ]);

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->callAction('delete')
        ->assertHasNoActionErrors();

    $this->assertDatabaseMissing('currencies', [
        'id' => $currency->id,
    ]);
});

it('can bulk delete currencies from list page', function () {
    $currencies = Currency::factory()->count(3)->sequence(
        ['code' => 'BK1', 'name' => 'Bulk 1', 'symbol' => '$1'],
        ['code' => 'BK2', 'name' => 'Bulk 2', 'symbol' => '$2'],
        ['code' => 'BK3', 'name' => 'Bulk 3', 'symbol' => '$3'],
    )->create();

    Livewire::test(ListCurrencies::class)
        ->callTableBulkAction('delete', $currencies)
        ->assertHasNoTableBulkActionErrors();

    foreach ($currencies as $currency) {
        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id,
        ]);
    }
});

// ==========================================
// Active/Inactive Status Tests
// ==========================================

it('displays active and inactive currencies', function () {
    $activeCurrency = Currency::factory()->create([
        'code' => 'ACT',
        'name' => 'Active Currency',
        'symbol' => 'A$',
        'is_active' => true,
    ]);

    $inactiveCurrency = Currency::factory()->create([
        'code' => 'INA',
        'name' => 'Inactive Currency',
        'symbol' => 'I$',
        'is_active' => false,
    ]);

    Livewire::test(ListCurrencies::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$activeCurrency, $inactiveCurrency]);
});

it('can toggle currency active status', function () {
    $currency = Currency::factory()->create([
        'code' => 'TGL',
        'name' => 'Toggle Currency',
        'symbol' => 'T$',
        'is_active' => true,
    ]);

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->fillForm([
            'is_active' => false,
            'exchange_rate' => 1.0,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $currency->refresh();
    expect($currency->is_active)->toBeFalse();
});
