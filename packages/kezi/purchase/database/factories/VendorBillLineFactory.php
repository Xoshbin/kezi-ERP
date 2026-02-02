<?php

namespace Kezi\Purchase\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\Account;
use Kezi\Accounting\Models\AnalyticAccount;
use Kezi\Accounting\Models\Tax;
use Kezi\Product\Enums\Products\ProductType;
use Kezi\Product\Models\Product;
use Kezi\Purchase\Models\VendorBill;

/**
 * @extends Factory<VendorBillLine>
 */
class VendorBillLineFactory extends Factory
{
    protected $model = \Kezi\Purchase\Models\VendorBillLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPriceRaw = $this->faker->randomFloat(2, 10, 1000);
        $subtotalRaw = $quantity * $unitPriceRaw;
        $totalTaxRaw = $subtotalRaw * 0.1; // 10% tax mock

        return [
            'vendor_bill_id' => VendorBill::factory(),
            'company_id' => function (array $attributes) {
                $bill = VendorBill::find($attributes['vendor_bill_id']);

                return $bill ? $bill->company_id : \App\Models\Company::factory();
            },
            'product_id' => Product::factory()->state(['type' => ProductType::Service]),
            'description' => $this->faker->sentence(4),
            'quantity' => $quantity,
            'unit_price' => function (array $attributes) use ($unitPriceRaw) {
                $bill = VendorBill::find($attributes['vendor_bill_id']);
                $currencyCode = $bill ? $bill->currency->code : 'USD';

                return Money::of($unitPriceRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'tax_id' => Tax::factory(),
            'subtotal' => function (array $attributes) use ($subtotalRaw) {
                $bill = VendorBill::find($attributes['vendor_bill_id']);
                $currencyCode = $bill ? $bill->currency->code : 'USD';

                return Money::of($subtotalRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'total_line_tax' => function (array $attributes) use ($totalTaxRaw) {
                $bill = VendorBill::find($attributes['vendor_bill_id']);
                $currencyCode = $bill ? $bill->currency->code : 'USD';

                return Money::of($totalTaxRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'expense_account_id' => Account::factory(),
            'analytic_account_id' => AnalyticAccount::factory(), // Nullable, can be used for detailed tracking
        ];
    }
}
