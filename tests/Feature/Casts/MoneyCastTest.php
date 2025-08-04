<?php

namespace Tests\Feature\Casts;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\DepreciationEntry;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// This expanded dataset defines all the scenarios we want to test.
// To test a new model, you simply add a new line here. No new test function is needed.
dataset('money_cast_scenarios', [
    // Description          Child Model              Parent Model        Relationship      Field          Currency   Input         Expected Minor
    'Invoice Line IQD'   => [InvoiceLine::class,      Invoice::class,      'invoice',      'unit_price',    'IQD',    5000000,      5000000000],
    'Invoice Line USD'   => [InvoiceLine::class,      Invoice::class,      'invoice',      'unit_price',    'USD',    '500.55',     50055],
    'Vendor Bill Line IQD' => [VendorBillLine::class,   VendorBill::class,   'vendorBill',   'unit_price',    'IQD',    1234,         1234000],
    'Vendor Bill Line USD' => [VendorBillLine::class,   VendorBill::class,   'vendorBill',   'unit_price',    'USD',    '199.99',     19999],
    'Journal Entry Line IQD' => [JournalEntryLine::class, JournalEntry::class, 'journalEntry', 'debit',         'IQD',    '2500.500',   2500500],
    'Journal Entry Line USD' => [JournalEntryLine::class, JournalEntry::class, 'journalEntry', 'credit',        'USD',    1000,         100000],
    'Depreciation Entry USD' => [DepreciationEntry::class, Asset::class,       'asset',        'amount',        'USD',    '75.50',      7550],
]);

it('correctly casts money fields on various related models', function (
    string $modelClass,
    string $parentModelClass,
    string $relationship,
    string $moneyField,
    string $currencyCode,
    $inputValue,
    int $expectedMinor
) {
    // Arrange: Create the currency and the parent document (e.g., Invoice, VendorBill).
    $currency = Currency::factory()->create([
        'code' => $currencyCode,
        'decimal_places' => $currencyCode === 'IQD' ? 3 : 2,
    ]);
    $parent = $parentModelClass::factory()->for($currency, 'currency')->create();

    // Act: Create the child model (e.g., InvoiceLine) linked to the parent.
    // The MoneyCast is triggered when we set the money field.
    $model = $modelClass::factory()
        ->for($parent, $relationship)
        ->create([
            $moneyField => $inputValue,
        ]);

    // Assert: Check the raw integer value stored in the database.
    $this->assertDatabaseHas($model->getTable(), [
        'id' => $model->id,
        $moneyField => $expectedMinor,
    ]);

    // Assert: Check that the value is correctly hydrated back into a Money object.
    $hydratedModel = $model->fresh();
    expect($hydratedModel->{$moneyField})->toBeInstanceOf(Money::class)
        ->and($hydratedModel->{$moneyField}->getMinorAmount()->toInt())->toBe($expectedMinor);

})->with('money_cast_scenarios');
