<?php

namespace Modules\Purchase\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Product;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

/**
 * @extends Factory<PurchaseOrderLine>
 */
class PurchaseOrderLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PurchaseOrderLine::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 100);
        $unitPriceRaw = $this->faker->randomFloat(2, 10, 1000);
        $subtotalRaw = $quantity * $unitPriceRaw;
        $taxRaw = $subtotalRaw * 0.1; // 10% tax
        $totalRaw = $subtotalRaw + $taxRaw;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => function (array $attributes) use ($unitPriceRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->currency->code : 'USD';

                return Money::of($unitPriceRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'subtotal' => function (array $attributes) use ($subtotalRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->currency->code : 'USD';

                return Money::of($subtotalRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'total_line_tax' => function (array $attributes) use ($taxRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->currency->code : 'USD';

                return Money::of($taxRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'total' => function (array $attributes) use ($totalRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->currency->code : 'USD';

                return Money::of($totalRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'unit_price_company_currency' => function (array $attributes) use ($unitPriceRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->company->currency->code : 'USD';

                return Money::of($unitPriceRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'subtotal_company_currency' => function (array $attributes) use ($subtotalRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->company->currency->code : 'USD';

                return Money::of($subtotalRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'total_line_tax_company_currency' => function (array $attributes) use ($taxRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->company->currency->code : 'USD';

                return Money::of($taxRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'total_company_currency' => function (array $attributes) use ($totalRaw) {
                $po = PurchaseOrder::find($attributes['purchase_order_id']);
                $currencyCode = $po ? $po->company->currency->code : 'USD';

                return Money::of($totalRaw, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP);
            },
            'quantity_received' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the line has been partially received.
     */
    public function partiallyReceived(?int $receivedQuantity = null): static
    {
        return $this->state(function (array $attributes) use ($receivedQuantity) {
            $totalQuantity = $attributes['quantity'];
            $received = $receivedQuantity ?? $this->faker->numberBetween(1, $totalQuantity - 1);

            return [
                'quantity_received' => min($received, $totalQuantity),
            ];
        });
    }

    /**
     * Indicate that the line has been fully received.
     */
    public function fullyReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_received' => $attributes['quantity'],
        ]);
    }

    /**
     * Set a specific unit price.
     */
    public function withUnitPrice(float $price): static
    {
        return $this->state(function (array $attributes) use ($price) {
            $quantity = $attributes['quantity'];
            $subtotal = $quantity * $price;
            $tax = $subtotal * 0.1; // 10% tax
            $total = $subtotal + $tax;

            $po = PurchaseOrder::find($attributes['purchase_order_id']);
            $currencyCode = $po ? $po->currency->code : 'USD';
            $companyCurrencyCode = $po ? $po->company->currency->code : 'USD';

            return [
                'unit_price' => Money::of($price, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'subtotal' => Money::of($subtotal, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_line_tax' => Money::of($tax, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total' => Money::of($total, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'unit_price_company_currency' => Money::of($price, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'subtotal_company_currency' => Money::of($subtotal, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_line_tax_company_currency' => Money::of($tax, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_company_currency' => Money::of($total, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
            ];
        });
    }

    /**
     * Set a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $po = PurchaseOrder::find($attributes['purchase_order_id']);
            $currencyCode = $po ? $po->currency->code : 'USD';
            $companyCurrencyCode = $po ? $po->company->currency->code : 'USD';

            // unit_price might be a Money object or a string/float
            $unitPrice = $attributes['unit_price'];
            if ($unitPrice instanceof Money) {
                $unitPriceValue = $unitPrice->getAmount()->toFloat();
            } else {
                $unitPriceValue = (float) $unitPrice;
            }

            $subtotal = $quantity * $unitPriceValue;
            $tax = $subtotal * 0.1; // 10% tax
            $total = $subtotal + $tax;

            return [
                'quantity' => $quantity,
                'subtotal' => Money::of($subtotal, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_line_tax' => Money::of($tax, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total' => Money::of($total, $currencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'subtotal_company_currency' => Money::of($subtotal, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_line_tax_company_currency' => Money::of($tax, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
                'total_company_currency' => Money::of($total, $companyCurrencyCode, null, \Brick\Math\RoundingMode::HALF_UP),
            ];
        });
    }
}
