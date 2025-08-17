# Multi-Locale Search Implementation

This document describes the robust, DRY solution for smart multi-locale search in Filament select relationships that searches across all translation locales and returns results in the current locale.

## Overview

The solution consists of three main components:

1. **TranslatableSearch Trait** - Provides multi-locale search functionality to models
2. **TranslatableSelect Helper** - Standardizes Filament select component implementation
3. **Updated Models** - All translatable models now use the trait for consistent search behavior

## Architecture

### 1. TranslatableSearch Trait (`app/Traits/TranslatableSearch.php`)

This trait provides the core functionality for searching across all translation locales:

#### Key Features:
- Searches across all configured locales (`en`, `ckb`, `ar`)
- Returns results formatted in the current application locale
- Supports both translatable and non-translatable fields
- Database-agnostic (works with MySQL and SQLite)
- Configurable search fields per model
- Performance optimized with proper query building

#### Key Methods:
- `scopeSearchTranslatable()` - Query scope for multi-locale search
- `getFilamentSearchResults()` - Returns formatted results for Filament selects
- `getFormattedSearchResults()` - Custom formatting support
- `getTranslatedLabel()` - Gets translated label in current locale
- `getAllTranslations()` - Debugging helper for all translations

### 2. TranslatableSelect Helper (`app/Filament/Support/TranslatableSelect.php`)

This helper class provides consistent implementation for Filament select fields:

#### Available Methods:
- `make()` - Basic translatable select
- `relationship()` - Relationship select with query modification
- `withFormatter()` - Custom label formatting
- `standard()` - For non-translatable models

### 3. Updated Models

The following models now use the TranslatableSearch trait:

#### Translatable Models:
- **Account** - Searches: `name` (translatable), `code` (non-translatable)
- **Currency** - Searches: `name` (translatable)
- **Tax** - Searches: `name`, `label_on_invoices` (both translatable)
- **Journal** - Searches: `name` (translatable)
- **FiscalPosition** - Searches: `name` (translatable)
- **AnalyticPlan** - Searches: `name` (translatable)

#### Non-Translatable Models (using standard search):
- **Partner** - Searches: `name`, `email`, `contact_person`
- **Product** - Searches: `name`, `sku`, `description`

## Usage Examples

### Basic Translatable Select

```php
use App\Filament\Support\TranslatableSelect;

// Simple translatable select
TranslatableSelect::make('currency_id', \App\Models\Currency::class, 'Currency')
```

### Relationship Select with Query Modification

```php
// Account select filtered by type
TranslatableSelect::relationship(
    'receivable_account_id',
    'receivableAccount',
    \App\Models\Account::class,
    __('partner.receivable_account'),
    'name',
    null,
    fn($query) => $query->where('type', AccountType::Receivable)
)
```

### Custom Formatted Select

```php
// Account select with code in parentheses
TranslatableSelect::withFormatter(
    'account_id',
    \App\Models\Account::class,
    fn($account) => [$account->id => $account->getTranslatedLabel('name') . ' (' . $account->code . ')'],
    'Account'
)
```

### Non-Translatable Model Select

```php
// Partner select with multiple search fields
TranslatableSelect::standard(
    'customer_id',
    \App\Models\Partner::class,
    ['name', 'email', 'contact_person'],
    __('invoice.customer')
)
```

## Configuration

### Model Configuration

To add multi-locale search to a translatable model:

```php
use App\Traits\TranslatableSearch;

class YourModel extends Model
{
    use HasTranslations, TranslatableSearch;
    
    public array $translatable = ['name', 'description'];
    
    // Optional: Customize translatable search fields
    public function getTranslatableSearchFields(): array
    {
        return ['name']; // Only search in name, not description
    }
    
    // Optional: Add non-translatable search fields
    public function getNonTranslatableSearchFields(): array
    {
        return ['code', 'reference'];
    }
}
```

### Locale Configuration

The search uses locales configured in the Filament Spatie Translatable plugin:

```php
// In FilamentPanelProvider
SpatieTranslatablePlugin::make()
    ->defaultLocales(['en', 'ckb', 'ar'])
```

## Search Behavior

### How It Works

1. **Input**: User types search term in any language
2. **Search**: System searches across all locale fields in JSON columns
3. **Results**: Returns matches formatted in current application locale
4. **Fallback**: If translation missing, falls back to available translation

### Example Search Flow

```
User searches: "دۆلار" (Kurdish for "dollar")
System searches in:
- name->en: "US Dollar"
- name->ckb: "دۆلاری ئەمریکی" ✓ (matches)
- name->ar: "الدولار الأمريكي"

Current locale: English
Result displayed: "US Dollar"
```

## Database Compatibility

The solution works with both MySQL and SQLite:

- **MySQL**: Uses `JSON_UNQUOTE(JSON_EXTRACT())`
- **SQLite**: Uses `json_extract()`

## Performance Considerations

### Database Indexes

For optimal performance, consider adding indexes on JSON fields:

```sql
-- MySQL
ALTER TABLE currencies ADD INDEX idx_name_en ((JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))));
ALTER TABLE currencies ADD INDEX idx_name_ckb ((JSON_UNQUOTE(JSON_EXTRACT(name, '$.ckb'))));
ALTER TABLE currencies ADD INDEX idx_name_ar ((JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))));

-- For accounts with code search
ALTER TABLE accounts ADD INDEX idx_code (code);
```

### Query Optimization

- Results are limited to 50 by default
- Only searches when search term is not empty
- Uses efficient OR conditions for multiple locales

## Migration Guide

### From Old Implementation

Replace old custom search implementations:

```php
// OLD - Custom JSON search
Select::make('currency_id')
    ->searchable()
    ->getSearchResultsUsing(fn(string $search): array =>
        Currency::whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, "$.' . app()->getLocale() . '"))) LIKE ?', ['%' . strtolower($search) . '%'])
            ->limit(50)
            ->get()
            ->mapWithKeys(fn($currency) => [$currency->id => $currency->getTranslation('name', app()->getLocale())])
            ->toArray()
    )

// NEW - TranslatableSelect
TranslatableSelect::make('currency_id', \App\Models\Currency::class, 'Currency')
```

### Benefits of New Implementation

1. **DRY**: No code duplication across resources
2. **Consistent**: Same behavior everywhere
3. **Multi-locale**: Searches all languages, not just current
4. **Maintainable**: Centralized logic in trait and helper
5. **Extensible**: Easy to add new models and customize behavior
6. **Database-agnostic**: Works with different database engines

## Testing

The implementation includes comprehensive tests covering:

- Multi-locale search functionality
- Current locale result formatting
- Mixed translatable/non-translatable field search
- Missing translation handling
- Result limiting and formatting
- Database compatibility

## Troubleshooting

### Common Issues

1. **Trait not loaded**: Ensure proper import and use statement
2. **JSON malformed**: Check database JSON column format
3. **No results**: Verify locale configuration and data format
4. **Performance**: Add database indexes for large datasets

### Debug Helpers

```php
// Check all translations for a field
$model->getAllTranslations('name');

// Test search directly
Model::getFilamentSearchResults('search term');

// Check configured locales
$model->getSearchLocales();
```

## Quick Reference

### Adding Multi-Locale Search to New Model

1. Add trait to model:
```php
use App\Traits\TranslatableSearch;
class NewModel extends Model {
    use HasTranslations, TranslatableSearch;
    public array $translatable = ['name'];
}
```

2. Use in Filament resource:
```php
TranslatableSelect::make('new_model_id', \App\Models\NewModel::class, 'New Model')
```

### Common Patterns

```php
// Basic select
TranslatableSelect::make('field_id', ModelClass::class, 'Label')

// With query filter
TranslatableSelect::relationship('field_id', 'relation', ModelClass::class, 'Label', 'name', null, fn($q) => $q->where('active', true))

// With custom formatting
TranslatableSelect::withFormatter('field_id', ModelClass::class, fn($model) => [$model->id => $model->name . ' (' . $model->code . ')'])

// Non-translatable model
TranslatableSelect::standard('field_id', ModelClass::class, ['name', 'email'], 'Label')
```

## Future Enhancements

Potential improvements for future versions:

1. **Fuzzy Search**: Add support for approximate matching
2. **Search Highlighting**: Highlight matched terms in results
3. **Search Analytics**: Track popular search terms
4. **Caching**: Cache frequent search results
5. **Elasticsearch**: Integration for advanced search capabilities
