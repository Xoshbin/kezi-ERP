<?php

namespace Jmeryar\Accounting\Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Accounting\Enums\Accounting\RecurringFrequency;
use Jmeryar\Accounting\Enums\Accounting\RecurringStatus;
use Jmeryar\Accounting\Enums\Accounting\RecurringTargetType;
use Jmeryar\Accounting\Models\RecurringTemplate;

class RecurringTemplateFactory extends Factory
{
    protected $model = RecurringTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'frequency' => RecurringFrequency::Monthly,
            'interval' => 1,
            'start_date' => Carbon::now(),
            'next_run_date' => Carbon::now()->addMonth(),
            'status' => RecurringStatus::Active,
            'target_type' => RecurringTargetType::JournalEntry,
            'template_data' => [], // Populate in specific states
        ];
    }
}
