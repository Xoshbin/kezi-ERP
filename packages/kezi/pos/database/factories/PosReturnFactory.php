<?php

namespace Kezi\Pos\Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Foundation\Models\Currency;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;

class PosReturnFactory extends Factory
{
    protected $model = PosReturn::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'company_id' => Company::factory(),
            'pos_session_id' => PosSessionFactory::new(),
            'original_order_id' => PosOrderFactory::new(),
            'currency_id' => Currency::factory(),
            'return_number' => 'RET-'.$this->faker->unique()->numberBetween(1000, 9999),
            'return_date' => now(),
            'status' => PosReturnStatus::Draft,
            'requested_by_user_id' => User::factory(),
            'refund_amount' => 1000,
            'restocking_fee' => 0,
            'refund_method' => 'cash',
        ];
    }
}
