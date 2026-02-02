<?php

namespace Kezi\Foundation\Enums\Settings;

use Carbon\Carbon;

/**
 * NumberingType Enum
 *
 * Defines standard numbering formats for invoices and bills.
 * Each type provides a different format pattern that companies can choose from.
 */
enum NumberingType: string
{
    case SIMPLE = 'simple';
    case YEAR_PREFIX = 'year_prefix';
    case YEAR_SUFFIX = 'year_suffix';
    case YEAR_MONTH = 'year_month';
    case SLASH_SEPARATED = 'slash_separated';
    case SLASH_YEAR_MONTH = 'slash_year_month';
    case DOT_SEPARATED = 'dot_separated';

    /**
     * Get the human-readable label for the numbering type.
     */
    public function label(): string
    {
        return match ($this) {
            self::SIMPLE => __('foundation::numbering.types.simple'),
            self::YEAR_PREFIX => __('foundation::numbering.types.year_prefix'),
            self::YEAR_SUFFIX => __('foundation::numbering.types.year_suffix'),
            self::YEAR_MONTH => __('foundation::numbering.types.year_month'),
            self::SLASH_SEPARATED => __('foundation::numbering.types.slash_separated'),
            self::SLASH_YEAR_MONTH => __('foundation::numbering.types.slash_year_month'),
            self::DOT_SEPARATED => __('foundation::numbering.types.dot_separated'),
        };
    }

    /**
     * Get a description of the format with an example.
     */
    public function description(): string
    {
        return match ($this) {
            self::SIMPLE => __('foundation::numbering.descriptions.simple'),
            self::YEAR_PREFIX => __('foundation::numbering.descriptions.year_prefix'),
            self::YEAR_SUFFIX => __('foundation::numbering.descriptions.year_suffix'),
            self::YEAR_MONTH => __('foundation::numbering.descriptions.year_month'),
            self::SLASH_SEPARATED => __('foundation::numbering.descriptions.slash_separated'),
            self::SLASH_YEAR_MONTH => __('foundation::numbering.descriptions.slash_year_month'),
            self::DOT_SEPARATED => __('foundation::numbering.descriptions.dot_separated'),
        };
    }

    /**
     * Generate the formatted number based on the type.
     *
     * @param  string  $prefix  The document prefix (e.g., 'INV', 'BILL')
     * @param  int  $number  The sequential number
     * @param  int  $padding  The number padding (default 5)
     * @param  Carbon|null  $date  The document date (for date-based formats)
     * @return string The formatted number
     */
    public function formatNumber(string $prefix, int $number, int $padding = 5, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $paddedNumber = str_pad((string) $number, $padding, '0', STR_PAD_LEFT);

        return match ($this) {
            self::SIMPLE => "{$prefix}-{$paddedNumber}",
            self::YEAR_PREFIX => "{$date->year}-{$prefix}-{$paddedNumber}",
            self::YEAR_SUFFIX => "{$prefix}-{$paddedNumber}-{$date->year}",
            self::YEAR_MONTH => "{$date->year}{$date->format('m')}-{$prefix}-{$paddedNumber}",
            self::SLASH_SEPARATED => "{$prefix}/{$date->year}/{$paddedNumber}",
            self::SLASH_YEAR_MONTH => "{$prefix}/{$date->year}/{$date->format('m')}/{$paddedNumber}",
            self::DOT_SEPARATED => "{$prefix}.{$date->year}.{$paddedNumber}",
        };
    }

    /**
     * Get example format for display purposes.
     *
     * @param  string  $prefix  The document prefix (e.g., 'INV', 'BILL')
     * @return string Example formatted number
     */
    public function getExample(string $prefix = 'INV'): string
    {
        return $this->formatNumber($prefix, 1, 5, now());
    }

    /**
     * Get all available numbering types with their labels and examples.
     *
     * @param  string  $prefix  The document prefix for examples
     * @return array<string, array{label: string, description: string, example: string}>
     */
    public static function getOptionsWithExamples(string $prefix = 'INV'): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = [
                'label' => $type->label(),
                'description' => $type->description(),
                'example' => $type->getExample($prefix),
            ];
        }

        return $options;
    }

    /**
     * Get options formatted for Filament select components.
     *
     * @param  string  $prefix  The document prefix for examples
     * @return array<string, string>
     */
    public static function getFilamentOptions(string $prefix = 'INV'): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->getExample($prefix).' ('.ucfirst(str_replace('_', ' ', $type->value)).')';
        }

        return $options;
    }
}
