<?php

namespace Jmeryar\Purchase\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Foundation\Models\Currency;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Jmeryar\Purchase\Models\PurchaseOrder;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'vendor_id' => Partner::factory()->vendor(),
            'currency_id' => Currency::factory()->createSafely(),
            'created_by_user_id' => User::factory(),
            'po_number' => null,
            'status' => PurchaseOrderStatus::Draft,
            'reference' => $this->faker->optional()->regexify('[A-Z]{2}-[0-9]{6}'),
            'po_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'expected_delivery_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'confirmed_at' => null,
            'cancelled_at' => null,
            'exchange_rate_at_creation' => null,
            'total_amount' => 0,
            'total_tax' => 0,
            'total_amount_company_currency' => null,
            'total_tax_company_currency' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'terms_and_conditions' => $this->faker->optional()->paragraph(),
            'delivery_location_id' => null,
        ];
    }

    /**
     * Indicate that the purchase order is an RFQ.
     */
    public function rfq(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::RFQ,
        ]);
    }

    /**
     * Indicate that the RFQ has been sent.
     */
    public function rfqSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::RFQSent,
        ]);
    }

    /**
     * Indicate that the purchase order has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Sent,
            'po_number' => 'PO-'.$this->faker->unique()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the purchase order is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::ToReceive,
            'confirmed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'po_number' => 'PO-'.$this->faker->unique()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the purchase order is waiting to receive goods.
     */
    public function toReceive(): static
    {
        return $this->confirmed()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::ToReceive,
        ]);
    }

    /**
     * Indicate that the purchase order is partially received.
     */
    public function partiallyReceived(): static
    {
        return $this->confirmed()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::PartiallyReceived,
        ]);
    }

    /**
     * Indicate that the purchase order is fully received.
     */
    public function fullyReceived(): static
    {
        return $this->confirmed()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::FullyReceived,
        ]);
    }

    /**
     * Indicate that the purchase order is ready to bill.
     */
    public function toBill(): static
    {
        return $this->fullyReceived()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::ToBill,
        ]);
    }

    /**
     * Indicate that the purchase order is partially billed.
     */
    public function partiallyBilled(): static
    {
        return $this->toBill()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::PartiallyBilled,
        ]);
    }

    /**
     * Indicate that the purchase order is fully billed.
     */
    public function fullyBilled(): static
    {
        return $this->partiallyBilled()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::FullyBilled,
        ]);
    }

    /**
     * Indicate that the purchase order is done/closed.
     */
    public function done(): static
    {
        return $this->fullyBilled()->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Done,
        ]);
    }

    /**
     * Indicate that the purchase order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Cancelled,
            'cancelled_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Indicate that the purchase order has a delivery location.
     */
    public function withDeliveryLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_location_id' => StockLocation::factory(),
        ]);
    }

    /**
     * Indicate that the purchase order has an exchange rate.
     */
    public function withExchangeRate(float $rate = 1.0): static
    {
        return $this->state(fn (array $attributes) => [
            'exchange_rate_at_creation' => $rate,
        ]);
    }
}
