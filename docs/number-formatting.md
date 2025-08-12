# Number Formatting Configuration

This application provides configurable number formatting that is independent from the application locale. This allows you to display numbers in English format (0123456789) even when the application is set to Kurdish or other locales.

## Configuration

The number formatting is controlled by environment variables in your `.env` file:

```env
# Number formatting locale (independent from APP_LOCALE)
# Set to 'en' to always use English numerals (0123456789)
# Set to 'auto' to use the current application locale
NUMBER_FORMATTING_LOCALE=en
CURRENCY_FORMATTING_LOCALE=en
```

## Available Options

- `en` - Always use English numerals (0123456789) regardless of application locale
- `auto` - Use the current application locale for number formatting
- Any valid locale string (e.g., `de`, `fr`, `ar`, etc.)

## Usage

### In Report Pages

The report pages automatically use the configured number formatting locale:

```php
use App\Support\NumberFormatter;

// Format Money objects
$formatted = NumberFormatter::formatMoney($money);
$formatted = NumberFormatter::formatMoneyTo($money);

// Format regular numbers
$formatted = NumberFormatter::formatNumber(1234.56);

// Format percentages
$formatted = NumberFormatter::formatPercentage(25.5);
```

### In Filament Components

The existing Filament components (MoneyColumn, MoneyInput) continue to work as before and will respect the global configuration.

## Benefits

1. **Consistent Display**: Numbers always display in the same format regardless of user's language preference
2. **Easy Configuration**: Change number format globally with a single environment variable
3. **Laravel Integration**: Uses Laravel's built-in Number utility class for reliable formatting
4. **Backward Compatibility**: Existing code continues to work without changes

## Examples

With `NUMBER_FORMATTING_LOCALE=en`:
- Kurdish locale: Numbers display as `1,234.56` (English numerals)
- English locale: Numbers display as `1,234.56` (English numerals)

With `NUMBER_FORMATTING_LOCALE=auto`:
- Kurdish locale: Numbers display as `١٬٢٣٤٫٥٦` (Kurdish numerals)
- English locale: Numbers display as `1,234.56` (English numerals)

## Implementation Details

The `App\Support\NumberFormatter` class provides a centralized way to format numbers using Laravel's `Number` utility class with configurable locale settings. This ensures consistency across the entire application while maintaining flexibility for different deployment scenarios.
