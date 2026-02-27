<?php

namespace Kezi\Pos\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\Pos\Enums\PosSessionStatus;
use Kezi\Pos\Models\PosProfile;
use Kezi\Pos\Models\PosSession;

class PosSessionFactory extends Factory
{
    protected $model = PosSession::class;

    public function definition(): array
    {
        return [
            'pos_profile_id' => PosProfile::factory(),
            'user_id' => User::factory(),
            'opened_at' => now(),
            'opening_cash' => 10000,
            'status' => PosSessionStatus::Opened,
        ];
    }
}
