<?php

namespace Database\Factories;

use App\Models\Tax;
use Brick\Money\Money;
use App\Models\Account;
use App\Models\Product;
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
            'unit_price' => function (array $attributes) {
                $adjustmentDocument = AdjustmentDocument::find($attributes['adjustment_document_id']);
                $currency = $adjustmentDocument->currency;
                return Money::of($this->faker->randomFloat(2, 10, 100), $currency->code);
            },
            'tax_id' => Tax::factory(),
            // Don't set subtotal and total_line_tax - let the model calculate them
            'account_id' => Account::factory(),
        ];
    }
}
