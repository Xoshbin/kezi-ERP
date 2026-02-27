<?php

namespace Kezi\Pos\Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Foundation\Models\Currency;
use Kezi\Foundation\Models\Partner;
use Kezi\Pos\Enums\PosOrderStatus;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosSession;

class PosOrderFactory extends Factory
{
    protected $model = PosOrder::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'pos_session_id' => PosSession::factory(),
            'company_id' => Company::factory(),
            'customer_id' => Partner::factory(),
            'currency_id' => Currency::factory(),
            'order_number' => 'ORD-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => PosOrderStatus::Paid,
            'ordered_at' => now(),
            'total_amount' => 5000,
            'total_tax' => 500,
            'sector_data' => [],
            'notes' => $this->faker->sentence,
        ];
    }
}
