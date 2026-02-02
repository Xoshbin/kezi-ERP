<?php

namespace Kezi\Inventory\Tests\Feature\Inventory;

use App\Models\Company;
use Brick\Money\Money;
use Kezi\Inventory\Enums\Inventory\CostSource;
use Kezi\Inventory\Enums\Inventory\ValuationMethod;
use Kezi\Inventory\Models\StockMove;
use Kezi\Inventory\Models\StockMoveValuation;
use Kezi\Product\Models\Product;

it('can store large cost impact values', function () {
    $company = Company::factory()->create();
    $product = Product::factory()->create(['company_id' => $company->id]);
    $stockMove = StockMove::factory()->create(['company_id' => $company->id]);

    // Value from the error: 2,500,000,000,000 (2.5 Trillion)
    // This is treated as minor units (integer) by MoneyCast
    $largeValue = '2500000000000';

    // We expect this to NOT throw an exception
    $valuation = StockMoveValuation::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'stock_move_id' => $stockMove->id,
        'quantity' => 1000,
        // When passing a string/int to create(), MoneyCast::set() converts it.
        // If we pass an integer directly, it's stored as is.
        // MoneyCast expects numeric or Money instance.
        'cost_impact' => $largeValue,
        'valuation_method' => ValuationMethod::FIFO,
        'move_type' => 'incoming',
        'cost_source' => CostSource::VendorBill,
        'source_type' => 'Kezi\Purchase\Models\VendorBill', // Dummy source
        'source_id' => 1,
    ]);

    expect($valuation->exists)->toBeTrue();

    // Refresh to read back from DB
    $valuation->refresh();

    // Verify the Money object
    expect($valuation->cost_impact)->toBeInstanceOf(Money::class);
    // The amount in minor units should match what we passed (if passed as minor)
    // Wait, MoneyCast::set() logic:
    // if numeric -> Money::of($value, currency) -> getMinorAmount()->toInt()
    // So if we pass 2500000000000 as "numeric", it treats it as MAJOR units by default in Money::of()?
    // Let's check MoneyCast again.

    // MoneyCast.php:
    // if (is_numeric($value)) {
    //     $currency = $this->resolveCurrency($model);
    //     $money = Money::of($value, $currency->code);
    //     return [$key => $money->getMinorAmount()->toInt()];
    // }

    // So if I pass 2.5T, it creates Money::of(2.5T), which is 2.5T Major units.
    // converted to minor (e.g. IQD has 3 decimals? or 0? if 3, then * 1000)
    // That makes it even bigger!

    // The user error showed: values (..., 2500000000000, ...)
    // If the SQL tried to insert 2500000000000, that was the INT value.

    // Let's pass a Money object to be explicit and safe in the test.
    $currency = $company->currency;
    $moneyValue = Money::of('2500000000000', $currency->code); // 2.5T Major units

    $valuation2 = StockMoveValuation::create([
        'company_id' => $company->id,
        'product_id' => $product->id,
        'stock_move_id' => $stockMove->id,
        'quantity' => 1000,
        'cost_impact' => $moneyValue,
        'valuation_method' => ValuationMethod::FIFO,
        'move_type' => 'incoming',
        'cost_source' => CostSource::VendorBill,
        'source_type' => 'Kezi\Purchase\Models\VendorBill', // Dummy source
        'source_id' => 1,
    ]);

    expect($valuation2->exists)->toBeTrue();
    expect($valuation2->cost_impact->isEqualTo($moneyValue))->toBeTrue();

});
