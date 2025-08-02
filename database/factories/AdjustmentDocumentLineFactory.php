<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AdjustmentDocument;
use App\Models\AdjustmentDocumentLine;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Tax;
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
            'unit_price' => $this->faker->randomFloat(2, 10, 100),
            'tax_id' => Tax::factory(),
            'subtotal' => $this->faker->randomFloat(2, 10, 1000),
            'total_line_tax' => $this->faker->randomFloat(2, 0, 100),
            'account_id' => Account::factory(),
        ];
    }
}
