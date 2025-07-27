<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    /**
     * Get the raw integer value (in cents) from the database.
     *
     * This ensures that all internal calculations are performed on integers,
     * preventing floating-point inaccuracies.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * Prepare the value for storage.
     *
     * This method handles being passed a float (e.g., 123.45) from user input
     * or an integer that is already in cents (e.g., 12345) from internal calculations.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        // If the value is already an integer, we assume it's in cents and pass it through.
        // This prevents double-multiplication when models are saved and re-saved.
        if (is_int($value)) {
            return $value;
        }

        // Otherwise, assume it's a standard decimal value and convert to cents.
        return (int) round((float)$value * 100);
    }
}
