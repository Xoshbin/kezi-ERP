<?php

namespace Modules\Inventory\Database\Factories;

use App\Models\AdjustmentDocumentLine;
use App\Models\Tax;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdjustmentDocumentLineFactory extends Factory
{
    protected $model = AdjustmentDocumentLine::class;

    public function definition(): array
    {
        return [
            'adjustment_document_id' => \Modules\Inventory\Models\AdjustmentDocument::factory(),
            'company_id' => function (array $attributes) {
                return \Modules\Inventory\Models\AdjustmentDocument::find($attributes['adjustment_document_id'])->company_id;
            },
            'product_id' => \Modules\Product\Models\Product::factory(),
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => function (array $attributes) {
                $adjustmentDocument = \Modules\Inventory\Models\AdjustmentDocument::find($attributes['adjustment_document_id']);
                $currency = $adjustmentDocument->currency;

                return Money::of($this->faker->randomFloat(2, 10, 100), $currency->code);
            },
            'tax_id' => Tax::factory(),
            // Don't set subtotal and total_line_tax - let the model calculate them
            'account_id' => \Modules\Accounting\Models\Account::factory(),
        ];
    }
}
