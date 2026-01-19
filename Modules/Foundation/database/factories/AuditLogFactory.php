<?php

namespace Modules\Foundation\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Foundation\Models\AuditLog;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'auditable_type' => $this->faker->randomElement(['Modules\Foundation\Models\Post', 'Modules\Foundation\Models\User', 'Modules\Foundation\Models\Order']),
            'auditable_id' => $this->faker->numberBetween(1, 1000),
            'old_values' => json_encode(['field' => $this->faker->word]),
            'new_values' => json_encode(['field' => $this->faker->word]),
            'description' => $this->faker->sentence,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
        ];
    }
}
