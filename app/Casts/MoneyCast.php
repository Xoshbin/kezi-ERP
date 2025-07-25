<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * This method is called when retrieving the value from the database.
     * It converts the stored integer (e.g., 12345) into a float with two decimal places (e.g., 123.45).
     *
     * Example:
     *   Database value: 12345
     *   Returned value: 123.45
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get($model, string $key, $value, array $attributes): float
    {
        // Transform the integer stored in the database (e.g., 12345) into a float (e.g., 123.45).
        return round(floatval($value) / 100, 2);
    }

    /**
     * Prepare the given value for storage.
     *
     * This method is called when saving the value to the database.
     * It converts the float (e.g., 123.45) into an integer (e.g., 12345) for storage.
     *
     * Example:
     *   Input value: 123.45
     *   Stored value: 12345
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): int
    {
        // Transform the float into an integer for storage.
        // For example, 123.45 becomes 12345
        return (int) round(floatval($value) * 100);
    }
}
