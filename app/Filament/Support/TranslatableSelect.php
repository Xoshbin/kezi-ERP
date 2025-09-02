<?php

namespace App\Filament\Support;

use InvalidArgumentException;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class TranslatableSelect
 *
 * Provides helper methods for creating Filament select components with multi-locale search functionality.
 * Standardizes the implementation across all resources for consistent behavior.
 *
 * @package App\Filament\Support
 */
class TranslatableSelect
{
    /**
     * Create a select component for translatable models with smart multi-locale search.
     *
     * @param string $name Field name
     * @param string $modelClass Model class that uses TranslatableSearch trait
     * @param string|null $label Field label (will be auto-generated if null)
     * @param string $labelField Field to use for option labels (default: 'name')
     * @param array|null $searchFields Fields to search in (uses model default if null)
     * @param callable|null $formatter Custom formatter for option labels
     * @param array $additionalOptions Additional select component options
     * @return Select
     */
    public static function make(
        string $name,
        string $modelClass,
        ?string $label = null,
        string $labelField = 'name',
        ?array $searchFields = null,
        ?callable $formatter = null,
        array $additionalOptions = []
    ): Select {
        // Auto-generate label if not provided
        if (!$label) {
            $label = __(Str::snake(class_basename($modelClass)) . '.' . $name);
        }

        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($modelClass, $labelField, $searchFields, $formatter): array {
                // Ensure the model uses the TranslatableSearch trait
                if (!method_exists($modelClass, 'getFilamentSearchResults')) {
                    throw new InvalidArgumentException(
                        "Model {$modelClass} must use the TranslatableSearch trait to use TranslatableSelect."
                    );
                }

                if ($formatter) {
                    return $modelClass::getFormattedSearchResults($search, 50, $formatter, $searchFields);
                }

                return $modelClass::getFilamentSearchResults($search, 50, $labelField, $searchFields);
            })
            ->getOptionLabelUsing(function ($value) use ($modelClass, $labelField): ?string {
                $model = $modelClass::find($value);
                return $model?->getTranslatedLabel($labelField);
            });

        // Apply additional options
        foreach ($additionalOptions as $method => $arguments) {
            if (method_exists($select, $method)) {
                $select = is_array($arguments) ? $select->$method(...$arguments) : $select->$method($arguments);
            }
        }

        return $select;
    }

    /**
     * Create a select component for relationship fields with multi-locale search.
     *
     * @param string $name Field name
     * @param string $relationship Relationship method name
     * @param string $modelClass Related model class
     * @param string|null $label Field label
     * @param string $labelField Field to use for option labels
     * @param array|null $searchFields Fields to search in
     * @param callable|null $queryModifier Callback to modify the base query
     * @param array $additionalOptions Additional select component options
     * @return Select
     */
    public static function relationship(
        string $name,
        string $relationship,
        string $modelClass,
        ?string $label = null,
        string $labelField = 'name',
        ?array $searchFields = null,
        ?callable $queryModifier = null,
        array $additionalOptions = []
    ): Select {
        if (!$label) {
            $label = __(Str::snake(class_basename($modelClass)) . '.' . $name);
        }

        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($modelClass, $labelField, $searchFields, $queryModifier): array {
                if (!method_exists($modelClass, 'getFilamentSearchResults')) {
                    throw new InvalidArgumentException(
                        "Model {$modelClass} must use the TranslatableSearch trait to use TranslatableSelect."
                    );
                }

                $query = $modelClass::searchTranslatable($search, $searchFields);

                if ($queryModifier) {
                    $query = $queryModifier($query);
                }

                return $query->limit(50)
                    ->get()
                    ->mapWithKeys(function ($model) use ($labelField) {
                        $label = $model->getTranslatedLabel($labelField);
                        return [$model->id => $label];
                    })
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value) use ($modelClass, $labelField): ?string {
                $model = $modelClass::find($value);
                return $model?->getTranslatedLabel($labelField);
            });

        // Apply additional options
        foreach ($additionalOptions as $method => $arguments) {
            if (method_exists($select, $method)) {
                $select = is_array($arguments) ? $select->$method(...$arguments) : $select->$method($arguments);
            }
        }

        return $select;
    }

    /**
     * Create a select component with complex formatting for option labels.
     * Useful for cases where you need to show additional context (e.g., "Account Name (Code)").
     *
     * @param string $name Field name
     * @param string $modelClass Model class
     * @param callable $formatter Formatter function that receives the model and returns [id => label]
     * @param string|null $label Field label
     * @param array|null $searchFields Fields to search in
     * @param array $additionalOptions Additional select component options
     * @return Select
     */
    public static function withFormatter(
        string $name,
        string $modelClass,
        callable $formatter,
        ?string $label = null,
        ?array $searchFields = null,
        array $additionalOptions = []
    ): Select {
        if (!$label) {
            $label = __(Str::snake(class_basename($modelClass)) . '.' . $name);
        }

        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($modelClass, $formatter, $searchFields): array {
                if (!method_exists($modelClass, 'getFormattedSearchResults')) {
                    throw new InvalidArgumentException(
                        "Model {$modelClass} must use the TranslatableSearch trait to use TranslatableSelect."
                    );
                }

                return $modelClass::getFormattedSearchResults($search, 50, $formatter, $searchFields);
            })
            ->getOptionLabelUsing(function ($value) use ($modelClass, $formatter): ?string {
                $model = $modelClass::find($value);
                if (!$model) {
                    return null;
                }

                $formatted = $formatter($model);
                return is_array($formatted) ? $formatted[$model->id] ?? null : $formatted;
            });

        // Apply additional options
        foreach ($additionalOptions as $method => $arguments) {
            if (method_exists($select, $method)) {
                $select = is_array($arguments) ? $select->$method(...$arguments) : $select->$method($arguments);
            }
        }

        return $select;
    }

    /**
     * Create a select component for non-translatable models with standard search.
     * Provides consistency with translatable selects but for regular string fields.
     *
     * @param string $name Field name
     * @param string $modelClass Model class
     * @param array $searchFields Fields to search in
     * @param string|null $label Field label
     * @param string $labelField Field to use for option labels
     * @param callable|null $queryModifier Callback to modify the base query
     * @param array $additionalOptions Additional select component options
     * @return Select
     */
    public static function standard(
        string $name,
        string $modelClass,
        array $searchFields,
        ?string $label = null,
        string $labelField = 'name',
        ?callable $queryModifier = null,
        array $additionalOptions = []
    ): Select {
        if (!$label) {
            $label = __(Str::snake(class_basename($modelClass)) . '.' . $name);
        }

        $select = Select::make($name)
            ->label($label)
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($modelClass, $searchFields, $labelField, $queryModifier): array {
                $query = $modelClass::query();

                if (!empty($search)) {
                    $query->where(function ($subQuery) use ($search, $searchFields) {
                        foreach ($searchFields as $field) {
                            $subQuery->orWhere($field, 'LIKE', '%' . $search . '%');
                        }
                    });
                }

                if ($queryModifier) {
                    $query = $queryModifier($query);
                }

                return $query->limit(50)
                    ->get()
                    ->mapWithKeys(function ($model) use ($labelField) {
                        return [$model->id => $model->$labelField];
                    })
                    ->toArray();
            })
            ->getOptionLabelUsing(function ($value) use ($modelClass, $labelField): ?string {
                $model = $modelClass::find($value);
                return $model?->$labelField;
            });

        // Apply additional options
        foreach ($additionalOptions as $method => $arguments) {
            if (method_exists($select, $method)) {
                $select = is_array($arguments) ? $select->$method(...$arguments) : $select->$method($arguments);
            }
        }

        return $select;
    }
}
