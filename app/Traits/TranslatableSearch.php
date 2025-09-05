<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Trait TranslatableSearch
 *
 * Provides multi-locale search functionality for models using Spatie Laravel Translatable.
 * Searches across all translation locales and returns results formatted in current locale.
 */
trait TranslatableSearch
{
    /**
     * Get the available locales for translation search.
     *
     * @return array<int, string>
     */
    public function getSearchLocales(): array
    {
        return config('filament-spatie-translatable.default_locales', ['en', 'ckb', 'ar']);
    }

    /**
     * Get the translatable fields that should be searched.
     * Override this method in your model to customize searchable fields.
     *
     * @return array<int, string>
     */
    public function getTranslatableSearchFields(): array
    {
        return $this->translatable ?? ['name'];
    }

    /**
     * Get the non-translatable fields that should be searched.
     * Override this method in your model to include additional searchable fields.
     *
     * @return array<int, string>
     */
    public function getNonTranslatableSearchFields(): array
    {
        return [];
    }

    /**
     * Scope to search across all translation locales for translatable fields.
     *
     * @param Builder<static> $query
     * @param array<int, string>|null $fields
     * @return Builder<static>
     */
    public function scopeSearchTranslatable(Builder $query, string $search, ?array $fields = null): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $fields = $fields ?? $this->getTranslatableSearchFields();
        $locales = $this->getSearchLocales();
        $nonTranslatableFields = $this->getNonTranslatableSearchFields();

        return $query->where(function (Builder $subQuery) use ($search, $fields, $locales, $nonTranslatableFields) {
            // Search in translatable fields across all locales (only if model has translatable fields)
            if (! empty($fields) && ! empty($this->translatable)) {
                foreach ($fields as $field) {
                    foreach ($locales as $locale) {
                        // Use database-specific JSON extraction with proper column reference
                        if (config('database.default') === 'sqlite') {
                            $subQuery->orWhereRaw(
                                'LOWER(json_extract(`'.$field.'`, "$.'.$locale.'")) LIKE ?',
                                ['%'.strtolower($search).'%']
                            );
                        } else {
                            $subQuery->orWhereRaw(
                                'LOWER(JSON_UNQUOTE(JSON_EXTRACT(`'.$field.'`, "$.'.$locale.'"))) LIKE ?',
                                ['%'.strtolower($search).'%']
                            );
                        }
                    }
                }
            }

            // Search in non-translatable fields
            foreach ($nonTranslatableFields as $field) {
                $subQuery->orWhere($field, 'LIKE', '%'.$search.'%');
            }
        });
    }

    /**
     * Get search results for Filament select components.
     * Returns an array with model ID as key and formatted label as value.
     *
     * @param array<int, string>|null $searchFields
     * @return array<int, string>
     */
    public static function getFilamentSearchResults(
        string $search,
        int $limit = 50,
        ?string $labelField = null,
        ?array $searchFields = null
    ): array {
        $labelField = $labelField ?? 'name';

        return static::searchTranslatable($search, $searchFields)
            ->limit($limit)
            ->get()
            ->mapWithKeys(function ($model) use ($labelField) {
                $label = $model->getTranslatedLabel($labelField);

                return [$model->id => $label];
            })
            ->toArray();
    }

    /**
     * Get the translated label for a field in the current locale.
     * Falls back to the original field value if translation is not available.
     */
    public function getTranslatedLabel(string $field, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        // Check if the field is translatable and the model has the HasTranslations trait
        if (in_array($field, $this->translatable ?? []) && $this->hasTranslationsSupport()) {
            $translation = $this->getTranslation($field, $locale); // @phpstan-ignore-line

            return $translation ?: ($this->$field ?? '');
        }

        // Return the field value directly for non-translatable fields
        return $this->$field ?? '';
    }

    /**
     * Get formatted search results with additional context.
     * Useful for complex select options that need more than just the name.
     *
     * @param array<int, string>|null $searchFields
     * @return array<int, string>
     */
    public static function getFormattedSearchResults(
        string $search,
        int $limit = 50,
        ?callable $formatter = null,
        ?array $searchFields = null
    ): array {
        $results = static::searchTranslatable($search, $searchFields)
            ->limit($limit)
            ->get();

        if ($formatter) {
            return $results->mapWithKeys($formatter)->toArray();
        }

        return $results->mapWithKeys(function ($model) {
            $label = $model->getTranslatedLabel('name');

            return [$model->id => $label];
        })->toArray();
    }

    /**
     * Search for models and return a collection with translated labels.
     * Useful for API responses or other contexts where you need the full model data.
     *
     * @param array<int, string>|null $searchFields
     * @return Collection<int, static>
     */
    public static function searchWithTranslatedLabels(
        string $search,
        int $limit = 50,
        ?array $searchFields = null
    ): Collection {
        return static::searchTranslatable($search, $searchFields)
            ->limit($limit)
            ->get()
            ->map(function ($model) {
                // Use attribute bag to avoid dynamic property warning for static analysis tools
                $model->setAttribute('translated_label', $model->getTranslatedLabel('name'));

                return $model;
            });
    }

    /**
     * Get all available translations for a specific field.
     * Useful for debugging or administrative purposes.
     *
     * @return array<string, string>
     */
    public function getAllTranslations(string $field): array
    {
        if (! in_array($field, $this->translatable ?? []) || ! $this->hasTranslationsSupport()) {
            return [$field => $this->$field];
        }

        $translations = [];
        foreach ($this->getSearchLocales() as $locale) {
            $translation = $this->getTranslation($field, $locale); // @phpstan-ignore-line
            if ($translation) {
                $translations[$locale] = $translation;
            }
        }

        return $translations;
    }

    /**
     * Check if the model has translation support.
     * This method helps avoid PHPStan warnings about method_exists always being true.
     */
    private function hasTranslationsSupport(): bool
    {
        return method_exists($this, 'getTranslation'); // @phpstan-ignore-line
    }
}
