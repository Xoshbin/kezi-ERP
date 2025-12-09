<?php

namespace Modules\Inventory\Database\Factories;

use Brick\Money\Money;
use Modules\Accounting\Models\Tax;
use Modules\Product\Models\Product;
use Modules\Accounting\Models\Account;
use Modules\Inventory\Models\AdjustmentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Inventory\Models\AdjustmentDocumentLine;

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
