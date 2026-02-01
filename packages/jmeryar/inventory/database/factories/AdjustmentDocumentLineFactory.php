<?php

namespace Jmeryar\Inventory\Database\Factories;

use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Tax;
use Jmeryar\Inventory\Models\AdjustmentDocument;
use Jmeryar\Inventory\Models\AdjustmentDocumentLine;
use Jmeryar\Product\Models\Product;

class AdjustmentDocumentLineFactory extends Factory
{
    protected $model = AdjustmentDocumentLine::class;

    public function definition(): array
    {
        return [
            'adjustment_document_id' => AdjustmentDocument::factory(),
            'company_id' => function (array $attributes) {
                return AdjustmentDocument::find($attributes['adjustment_document_id'])->company_id;
            },
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
