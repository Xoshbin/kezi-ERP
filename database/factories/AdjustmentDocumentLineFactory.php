<?php

namespace Database\Factories;

use App\Models\Tax;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Product;
use App\Models\Currency;
use App\Models\AdjustmentDocument;
use App\Models\AdjustmentDocumentLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdjustmentDocumentLineFactory extends Factory
{
    protected $model = AdjustmentDocumentLine::class;

    public function definition(): array
    {
        return [
            'adjustment_document_id' => AdjustmentDocument::factory(),
            'product_id' => Product::factory(),
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => Money::of($this->faker->randomFloat(2, 10, 100), 'USD'),
            'tax_id' => Tax::factory(),
            'subtotal' => Money::of($this->faker->randomFloat(2, 100, 10000), 'USD'),
            'total_line_tax' => Money::of($this->faker->randomFloat(2, 0, 100), 'USD'),
            'account_id' => Account::factory(),
        ];
    }
}
