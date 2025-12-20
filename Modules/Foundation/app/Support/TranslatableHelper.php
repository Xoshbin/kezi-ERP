<?php

namespace Modules\Foundation\Support;

/**
 * Helper class for working with Spatie Translatable fields.
 *
 * This helper is particularly useful when working with raw database queries
 * where the HasTranslations trait's magic accessor is not available.
 */
final class TranslatableHelper
{
    /**
     * Extract the localized value from a translatable field.
     *
     * This method handles both:
     * - JSON-encoded strings from raw DB queries (e.g., DB::table())
     * - Arrays from Eloquent models with HasTranslations trait
     *
     * @param  string|array<string, string>|null  $value  The translatable field value
     * @param  string|null  $locale  The locale to extract (defaults to current app locale)
     * @return string The localized value, or empty string if not available
     */
    public static function getLocalizedValue(string|array|null $value, ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // If it's a string, try to decode as JSON (raw DB query result)
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (! is_array($decoded)) {
                // Not JSON, return as-is (already a plain string)
                return $value;
            }
            $value = $decoded;
        }

        $locale ??= app()->getLocale();

        // Try current locale first, then fallback to 'en', then first available
        return $value[$locale]
            ?? $value['en']
            ?? (empty($value) ? '' : (string) array_values($value)[0]);
    }
}
