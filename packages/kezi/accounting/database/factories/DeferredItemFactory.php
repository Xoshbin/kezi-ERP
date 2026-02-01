<?php

namespace Kezi\Accounting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Accounting\Models\DeferredItem;

class DeferredItemFactory extends Factory
{
    protected $model = DeferredItem::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'type' => 'revenue',
            'name' => $this->faker->sentence,
            'original_amount' => 1000,
            'deferred_amount' => 1000,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'method' => 'linear',
            'deferred_account_id' => 1,
            'recognition_account_id' => 2,
            'source_type' => 'Kezi\Sales\Models\InvoiceLine',
            'source_id' => 1,
        ];
    }
}
