<?php

namespace Kezi\QualityControl\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Models\QualityCheck;

/**
 * @extends Factory<QualityCheck>
 */
class QualityCheckFactory extends Factory
{
    protected $model = QualityCheck::class;

    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        return [
            'number' => sprintf('QC-%06d', $counter),
            'status' => QualityCheckStatus::Draft,
            'source_type' => 'App\\Models\\DummySource',
            'source_id' => 1,
        ];
    }
}
