<?php

use App\Casts\MoneyCast;
use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentDocumentLink;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillLine;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear the static cache in MoneyCast for test isolation
    MoneyCast::clearCache();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->iqd = Currency::where('code', 'IQD')->first();
    $this->usd = Currency::where('code', 'USD')->first();
    $this->company = Company::factory()->create(['currency_id' => $this->iqd->id]);
    $this->partner = Partner::factory()->create(['company_id' => $this->company->id]);
    $this->account = Account::factory()->create(['company_id' => $this->company->id]);
    $this->journal = Journal::factory()->create(['company_id' => $this->company->id, 'currency_id' => $this->iqd->id]);
});

/**
 * Generic test runner for a given model and field.
 *
 * @param class-string<Model> $modelClass
 */
function runTestForModel(string $modelClass, string $moneyField, array $parentRelations = []): void
{
    // --- Test with IQD (3 decimal places) ---
    $majorAmountIqd = 5_000_000;
    $expectedMinorAmountIqd = 5_000_000_000;
    $modelIqd = createModelWithCurrency($modelClass, test()->iqd, $parentRelations);

    // Test with an INTEGER value.
    // NOTE: This test will likely FAIL with the current MoneyCast implementation because `is_int`
    // is checked before `is_numeric`, and it uses `Money::ofMinor()`. This treats the integer
    // as a minor value (e.g., 5,000,000 minor units = 5,000.000 IQD), which is incorrect.
    // The fix is to ensure numeric values (including integers) are treated as major units
    // by using `Money::of()` instead of `Money::ofMinor()`.
    $modelIqd->{$moneyField} = $majorAmountIqd;
    if ($modelClass === \App\Models\AdjustmentDocumentLine::class) {
        $modelIqd::withoutEvents(fn () => $modelIqd->save());
    } else {
        $modelIqd->save();
    }

    test()->assertDatabaseHas($modelIqd->getTable(), [
        'id' => $modelIqd->id,
        $moneyField => $expectedMinorAmountIqd,
    ]);
    expect($modelIqd->fresh()->{$moneyField})->toEqual(Money::of($majorAmountIqd, 'IQD'));


    // --- Test with USD (2 decimal places) ---
    $majorAmountUsd = 5_000_000;
    $expectedMinorAmountUsd = 500_000_000; // 5,000,000 * 100
    $modelUsd = createModelWithCurrency($modelClass, test()->usd, $parentRelations);

    // Test with an INTEGER value for USD. This will also likely fail for the same reason.
    $modelUsd->{$moneyField} = $majorAmountUsd;
    if ($modelClass === \App\Models\AdjustmentDocumentLine::class) {
        $modelUsd::withoutEvents(fn () => $modelUsd->save());
    } else {
        $modelUsd->save();
    }

    test()->assertDatabaseHas($modelUsd->getTable(), [
        'id' => $modelUsd->id,
        $moneyField => $expectedMinorAmountUsd,
    ]);
    expect($modelUsd->fresh()->{$moneyField})->toEqual(Money::of($majorAmountUsd, 'USD'));


    // --- Test with a NUMERIC STRING to confirm correct behavior ---
    // This test should pass, as `is_numeric` is handled correctly with `Money::of()`.
    $modelIqd->{$moneyField} = (string) $majorAmountIqd;
    if ($modelClass === \App\Models\AdjustmentDocumentLine::class) {
        $modelIqd::withoutEvents(fn () => $modelIqd->save());
    } else {
        $modelIqd->save();
    }
    test()->assertDatabaseHas($modelIqd->getTable(), [
        'id' => $modelIqd->id,
        $moneyField => $expectedMinorAmountIqd,
    ]);
    expect($modelIqd->fresh()->{$moneyField})->toEqual(Money::of($majorAmountIqd, 'IQD'));
}

/**
 * Helper to create a model instance with the correct currency context.
 *
 * @param class-string<Model> $modelClass
 * @param array<string> $parentRelations
 * @return \Illuminate\Database\Eloquent\Model
 */
function createModelWithCurrency(string $modelClass, Currency $currency, array $parentRelations)
{
    $factoryState = [];
    // Use the existing company for the given currency to avoid creating new currencies.
    $company = test()->company;
    if ($currency->code !== $company->currency->code) {
        $company = Company::factory()->create(['currency_id' => $currency->id]);
    }

    // Only assign company_id if the model is expected to have one.
    if (in_array('company_id', (new $modelClass)->getFillable())) {
        $factoryState['company_id'] = $company->id;
    }

    if (empty($parentRelations)) {
        // The model has a direct currency_id.
        if (method_exists($modelClass, 'currency')) {
            $factoryState['currency_id'] = $currency->id;
        }
    } else {
        // The model inherits currency from a parent.
        $parentModel = null;
        foreach ($parentRelations as $relationName) {
            $parentClass = get_class($modelClass::factory()->make()->{$relationName}()->getRelated());
            // The parent model MUST be associated with the new company.
            $parentFactoryState = ['company_id' => $company->id];

            // Special case for Payment which gets currency from its Journal.
            if ($parentClass === Payment::class) {
                $journal = Journal::factory()->create(['company_id' => $company->id, 'currency_id' => $currency->id]);
                $parentFactoryState['journal_id'] = $journal->id;
            } else if ($parentClass === \App\Models\BankStatement::class) {
                $parentFactoryState['currency_id'] = $currency->id;
            }
            else {
                // For all other models, ensure the currency is set directly on the parent.
                if (method_exists($parentClass, 'currency')) {
                    $parentFactoryState['currency_id'] = $currency->id;
                }
            }

            $parentModel = $parentClass::factory()->create($parentFactoryState);
            $relationForeignKey = $modelClass::factory()->make()->{$relationName}()->getForeignKeyName();
            $factoryState[$relationForeignKey] = $parentModel->id;
        }
    }

    // Special handling for PaymentDocumentLink to ensure it has a document to link to.
    if ($modelClass === PaymentDocumentLink::class) {
        // The invoice must belong to the same isolated company.
        $invoice = Invoice::factory()->create(['company_id' => $company->id, 'currency_id' => $currency->id]);
        $factoryState['invoice_id'] = $invoice->id;
        $factoryState['amount_applied'] = 0;
    }

    // Ensure necessary foreign keys are set for models that need them.
    if (in_array($modelClass, [InvoiceLine::class, VendorBillLine::class])) {
        $account = Account::factory()->create(['company_id' => $company->id]);
        if ($modelClass === InvoiceLine::class) {
            $factoryState['income_account_id'] = $account->id;
        }
        if ($modelClass === VendorBillLine::class) {
            $factoryState['expense_account_id'] = $account->id;
        }

    }

    if ($modelClass === JournalEntryLine::class) {
        $factoryState['account_id'] = Account::factory()->create(['company_id' => $company->id])->id;
    }

    if ($modelClass === \App\Models\AdjustmentDocumentLine::class) {
        $factoryState['account_id'] = Account::factory()->create(['company_id' => $company->id])->id;
    }

    // Create the model instance.
    if (in_array($modelClass, [Invoice::class, VendorBill::class, \App\Models\AdjustmentDocumentLine::class])) {
        $model = $modelClass::withoutEvents(fn() => $modelClass::factory()->create($factoryState));
    } else {
        $model = $modelClass::factory()->create($factoryState);
    }

    // Eager-load the relationship that provides the currency context. This is crucial.
    if (!empty($parentRelations)) {
        $model->load($parentRelations);
    } elseif (method_exists($modelClass, 'currency')) {
        $model->load('currency');
    } elseif (method_exists($modelClass, 'company')) {
        $model->load('company.currency');
    }

    return $model;
}


test('it casts money fields correctly for invoice model', function () {
    runTestForModel(Invoice::class, 'total_amount');
})->group('money-cast');

test('it casts money fields correctly for invoice line model', function () {
    runTestForModel(InvoiceLine::class, 'unit_price', ['invoice']);
})->group('money-cast');

test('it casts money fields correctly for vendor bill model', function () {
    runTestForModel(VendorBill::class, 'total_amount');
})->group('money-cast');

test('it casts money fields correctly for vendor bill line model', function () {
    runTestForModel(VendorBillLine::class, 'unit_price', ['vendorBill']);
})->group('money-cast');

test('it casts money fields correctly for payment model', function () {
    runTestForModel(Payment::class, 'amount');
})->group('money-cast');

test('it casts money fields correctly for adjustment document model', function () {
    runTestForModel(AdjustmentDocument::class, 'total_amount');
})->group('money-cast');

test('it casts money fields correctly for payment document link model', function () {
    runTestForModel(PaymentDocumentLink::class, 'amount_applied', ['payment']);
})->group('money-cast');

test('it casts money fields correctly for journal entry model', function () {
    runTestForModel(JournalEntry::class, 'total_debit');
})->group('money-cast');

test('it casts money fields correctly for journal entry line model', function () {
    runTestForModel(JournalEntryLine::class, 'debit', ['journalEntry']);
})->group('money-cast');

test('it casts money fields correctly for product model', function () {
    runTestForModel(Product::class, 'unit_price');
})->group('money-cast');

test('it casts money fields correctly for asset model purchase value', function () {
    runTestForModel(Asset::class, 'purchase_value');
})->group('money-cast');

test('it casts money fields correctly for asset model salvage value', function () {
    runTestForModel(Asset::class, 'salvage_value');
})->group('money-cast');

test('it casts money fields correctly for bank statement starting balance', function () {
    runTestForModel(\App\Models\BankStatement::class, 'starting_balance');
})->group('money-cast');

test('it casts money fields correctly for bank statement ending balance', function () {
    runTestForModel(\App\Models\BankStatement::class, 'ending_balance');
})->group('money-cast');

test('it casts money fields correctly for bank statement line model', function () {
    runTestForModel(\App\Models\BankStatementLine::class, 'amount', ['bankStatement']);
})->group('money-cast');

test('it casts money fields correctly for adjustment document line unit price', function () {
    runTestForModel(\App\Models\AdjustmentDocumentLine::class, 'unit_price', ['adjustmentDocument']);
})->group('money-cast');

test('it casts money fields correctly for adjustment document line subtotal', function () {
    runTestForModel(\App\Models\AdjustmentDocumentLine::class, 'subtotal', ['adjustmentDocument']);
})->group('money-cast');

test('it casts money fields correctly for adjustment document line total line tax', function () {
    runTestForModel(\App\Models\AdjustmentDocumentLine::class, 'total_line_tax', ['adjustmentDocument']);
})->group('money-cast');

test('it casts money fields correctly for budget line budgeted amount', function () {
    runTestForModel(\App\Models\BudgetLine::class, 'budgeted_amount', ['budget']);
})->group('money-cast');

test('it casts money fields correctly for budget line achieved amount', function () {
    runTestForModel(\App\Models\BudgetLine::class, 'achieved_amount', ['budget']);
})->group('money-cast');

test('it casts money fields correctly for budget line committed amount', function () {
    runTestForModel(\App\Models\BudgetLine::class, 'committed_amount', ['budget']);
})->group('money-cast');

test('it casts money fields correctly for depreciation entry model', function () {
    runTestForModel(\App\Models\DepreciationEntry::class, 'amount', ['asset']);
})->group('money-cast');

