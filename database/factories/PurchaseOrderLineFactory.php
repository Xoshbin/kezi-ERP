<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrderLine>
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
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);
        $subtotal = $quantity * $unitPrice;
        $tax = $subtotal * 0.1; // 10% tax
        $total = $subtotal + $tax;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice * 100, // Store as cents for Money cast
            'subtotal' => $subtotal * 100, // Store as cents for Money cast
            'total_line_tax' => $tax * 100, // Store as cents for Money cast
            'total' => $total * 100, // Store as cents for Money cast
            'unit_price_company_currency' => $unitPrice * 100, // Store as cents for Money cast
            'subtotal_company_currency' => $subtotal * 100, // Store as cents for Money cast
            'total_line_tax_company_currency' => $tax * 100, // Store as cents for Money cast
            'total_company_currency' => $total * 100, // Store as cents for Money cast
            'quantity_received' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the line has been partially received.
     */
    public function partiallyReceived(int $receivedQuantity = null): static
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
        return $this->state(fn(array $attributes) => [
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

            return [
                'unit_price' => $price * 100, // Store as cents
                'subtotal' => $subtotal * 100, // Store as cents
                'total_line_tax' => $tax * 100, // Store as cents
                'total' => $total * 100, // Store as cents
                'unit_price_company_currency' => $price * 100, // Store as cents
                'subtotal_company_currency' => $subtotal * 100, // Store as cents
                'total_line_tax_company_currency' => $tax * 100, // Store as cents
                'total_company_currency' => $total * 100, // Store as cents
            ];
        });
    }

    /**
     * Set a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $unitPrice = $attributes['unit_price'] / 100; // Convert from cents
            $subtotal = $quantity * $unitPrice;
            $tax = $subtotal * 0.1; // 10% tax
            $total = $subtotal + $tax;

            return [
                'quantity' => $quantity,
                'subtotal' => $subtotal * 100, // Store as cents
                'total_line_tax' => $tax * 100, // Store as cents
                'total' => $total * 100, // Store as cents
                'subtotal_company_currency' => $subtotal * 100, // Store as cents
                'total_line_tax_company_currency' => $tax * 100, // Store as cents
                'total_company_currency' => $total * 100, // Store as cents
            ];
        });
    }
}
