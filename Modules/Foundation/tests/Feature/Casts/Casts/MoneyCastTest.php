<?php

namespace Modules\Foundation\Tests\Feature\Casts;

use Brick\Money\Money;
use App\Models\Company;
use Modules\Sales\Models\Invoice;
use Modules\Accounting\Models\Asset;
use Modules\Sales\Models\InvoiceLine;
use Modules\Foundation\Models\Currency;
use Modules\Purchase\Models\VendorBill;
use Modules\Accounting\Models\JournalEntry;
use Modules\Purchase\Models\VendorBillLine;
use Modules\Accounting\Models\JournalEntryLine;
use Modules\Accounting\Models\DepreciationEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// This expanded dataset defines all the scenarios we want to test.
// To test a new model, you simply add a new line here. No new test function is needed.
dataset('money_cast_scenarios', [
    // Description          Child Model              Parent Model        Relationship      Field          Currency   Input         Expected Minor
    'Invoice Line IQD' => [InvoiceLine::class,      Invoice::class,      'invoice',      'unit_price',    'IQD',    5000000,      5000000000],
    'Invoice Line USD' => [InvoiceLine::class,      Invoice::class,      'invoice',      'unit_price',    'USD',    '500.55',     50055],
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
    int $expectedMinor,
) {
    // Arrange: Create the currency and the parent document (e.g., Invoice, VendorBill).
    $currency = Currency::factory()->create([
        'code' => $currencyCode,
        'decimal_places' => $currencyCode === 'IQD' ? 3 : 2,
    ]);

    // For models that use BaseCurrencyMoneyCast, ensure the company has the same base currency as the test expects
    if ($parentModelClass === JournalEntry::class) {
        $company = Company::factory()->for($currency, 'currency')->create();
        $parent = $parentModelClass::factory()->for($company, 'company')->for($currency, 'currency')->create();
    } elseif ($parentModelClass === Asset::class) {
        $company = Company::factory()->for($currency, 'currency')->create();
        $parent = $parentModelClass::factory()->for($company, 'company')->create();
    } else {
        $parent = $parentModelClass::factory()->for($currency, 'currency')->create();
    }

    // Act: Create the child model (e.g., InvoiceLine) linked to the parent.
    // Create the model first without the money field to avoid casting during creation
    $factory = $modelClass::factory()->for($parent, $relationship);

    // Special case: DepreciationEntry should be created as Draft to allow updates
    if ($modelClass === DepreciationEntry::class) {
        $factory = $factory->state(['status' => DepreciationEntryStatus::Draft]);
    }

    $model = $factory->create();

    // Eager-load the required relationships for the casting system
    if ($modelClass === JournalEntryLine::class) {
        $model->load('journalEntry.company.currency');
    } elseif ($modelClass === DepreciationEntry::class) {
        $model->load('asset.company.currency');
    } elseif ($modelClass === InvoiceLine::class) {
        $model->load('invoice.currency');
    } elseif ($modelClass === VendorBillLine::class) {
        $model->load('vendorBill.currency');
    }

    // Debug: Check if relationships are loaded
    if ($modelClass === InvoiceLine::class) {
        expect($model->relationLoaded('invoice'))->toBeTrue();
        expect($model->invoice->relationLoaded('currency'))->toBeTrue();
    }
    if ($modelClass === JournalEntryLine::class) {
        expect($model->relationLoaded('journalEntry'))->toBeTrue();
        expect($model->journalEntry->relationLoaded('company'))->toBeTrue();
        expect($model->journalEntry->company->relationLoaded('currency'))->toBeTrue();
    }

    // Now set the money field - the cast will handle currency resolution
    $model->{$moneyField} = $inputValue;
    $model->save();

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
