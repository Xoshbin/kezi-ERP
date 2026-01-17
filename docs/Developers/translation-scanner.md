---
title: Translation Scanner
description: Find missing and untranslated strings across locales
category: Development Tools
---

# Translation Scanner

## Overview

The Translation Scanner is a custom Artisan command that helps you find missing translation strings by comparing your base locale (default: English) against other locales in your application.

## Features

- ✅ **Automatic scanning** of all translation files (PHP arrays & JSON)
- ✅ **Multi-locale support** - Check specific locales or all at once
- ✅ **Nested key detection** - Recursively checks nested translation arrays
- ✅ **Type mismatch detection** - Identifies when base is array but target is string (or vice versa)
- ✅ **Empty value detection** - Finds translations that exist but are empty
- ✅ **Vendor translations** - Optional scanning of vendor package translations
- ✅ **Export functionality** - Export results to JSON or CSV
- ✅ **Beautiful console output** - Color-coded, organized results with icons

## Usage

### Basic Usage

```bash
php artisan translations:find-missing
```

This compares all locales against English (en) as the base and **automatically saves results** to `storage/app/translations/missing-translations-{timestamp}.json`.

### Options

#### Specify Base Locale

```bash
php artisan translations:find-missing --base=ar
```

#### Check Specific Locales Only

```bash
php artisan translations:find-missing --locales=ar --locales=ckb
```

#### Change Output Format

By default, results are saved as JSON. You can change the format:

```bash
# Save as Markdown (great for documentation and GitHub)
php artisan translations:find-missing --format=md

# Save as CSV (great for spreadsheets)
php artisan translations:find-missing --format=csv
```

#### Disable Auto-Save

If you only want to see results in the console:

```bash
php artisan translations:find-missing --no-save
```

#### Include Vendor Translations

```bash
php artisan translations:find-missing --include-vendor
```

### Combined Examples

```bash
# Check only Arabic against English, save as markdown
php artisan translations:find-missing --base=en --locales=ar --format=md

# Check all locales including vendor translations, save as CSV
php artisan translations:find-missing --include-vendor --format=csv

# Just view in console without saving
php artisan translations:find-missing --no-save
```

## Saved Files Location

All reports are automatically saved to:
```
storage/app/translations/missing-translations-{timestamp}.{format}
```

For example:
- `storage/app/translations/missing-translations-2025-12-16_172442.json`
- `storage/app/translations/missing-translations-2025-12-16_172442.md`
- `storage/app/translations/missing-translations-2025-12-16_172442.csv`

## Output Explanation

The command identifies three types of issues:

### ✗ Missing Key
The translation key exists in the base locale but is completely missing from the target locale.

```
✗ company.enable_reconciliation
   Type: missing_key
   Base: Enable Reconciliation
```

### ⚠ Empty Value
The key exists in both locales, but the target locale's value is empty.

```
⚠ user.welcome_message
   Type: empty_value
   Base: Welcome to our application
```

### ⚡ Type Mismatch
The key exists in both, but one is an array while the other is a string.

```
⚡ navigation.groups
   Type: type_mismatch
   Base: [array]
   Target: Navigation Groups
```

## Output Formats

The scanner supports three output formats, each suited for different use cases:

### JSON Format (Default)

Perfect for:
- Programmatic processing
- Version control tracking
- CI/CD integration
- Automated workflows

```json
{
  "scanned_at": "2025-12-16T17:24:42+00:00",
  "total_missing": 19,
  "affected_locales": ["ar", "ckb"],
  "missing_translations": {
    "ar": [
      {
        "key": "company.enable_reconciliation",
        "type": "missing_key",
        "base_value": "Enable Reconciliation"
      }
    ]
  }
}
```

### Markdown Format

Perfect for:
- Documentation
- GitHub issues/PRs
- Team communication
- Human-readable reports

Features:
- Organized tables grouped by file
- Icons for different issue types (❌ missing, ⚠️ empty, ⚡ mismatch)
- Clear summary and next steps
- Easy to read in any markdown viewer

### CSV Format

Perfect for:
- Spreadsheet analysis
- Sharing with non-technical stakeholders
- Sorting and filtering in Excel/Google Sheets
- Bulk translation management

```csv
Locale,Key,Type,Base Value,Target Value
ar,company.enable_reconciliation,missing_key,Enable Reconciliation,
ar,user.singular,missing_key,User,
```

## How It Works

1. **Scans** the `resources/lang` directory for all locale folders
2. **Loads** all PHP and JSON translation files from base locale
3. **Compares** each target locale against the base locale
4. **Recursively checks** nested arrays to find missing keys at any depth
5. **Reports** all discrepancies with context and original values

## Integration with CI/CD

You can integrate this into your CI/CD pipeline:

```yaml
# Example GitHub Actions workflow
- name: Check for missing translations
  run: php artisan translations:find-missing --export=json
```

## File Structure Support

The scanner supports:
- `resources/lang/{locale}/*.php` - Standard PHP translation files
- `resources/lang/{locale}.json` - JSON translation files
- `resources/lang/vendor/{package}/{locale}/*.php` - Vendor translations (with `--include-vendor`)

## Tips

- Run this regularly during development to catch missing translations early
- Use `--export=csv` for easy viewing in spreadsheets
- Use `--export=json` for programmatic processing or version control tracking
- Compare the JSON export between commits to see translation coverage changes
