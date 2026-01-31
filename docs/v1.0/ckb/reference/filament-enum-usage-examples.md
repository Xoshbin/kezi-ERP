# Filament Enum Usage Examples

This document shows how to use the new enum translation pattern in Filament resources.

## Pattern Overview

All enums now have a `label()` method that returns translated strings. Use this pattern in Filament:

```php
Forms\Components\Select::make('field_name')
    ->label(__('model.field_name'))
    ->required()
    ->options(
        collect(EnumClass::cases())
            ->mapWithKeys(fn (EnumClass $enum) => [$enum->value => $enum->label()])
    )
    ->searchable()
```

## Complete Examples

### AccountType in AccountResource

```php
use App\Enums\Accounting\AccountType;

Forms\Components\Select::make('type')
    ->label(__('account.type'))
    ->required()
    ->options(
        collect(AccountType::cases())
            ->mapWithKeys(fn (AccountType $type) => [$type->value => $type->label()])
    )
    ->searchable()
```

### PartnerType in PartnerResource

```php
use App\Enums\Partners\PartnerType;

Forms\Components\Select::make('type')
    ->label(__('partner.type'))
    ->required()
    ->options(
        collect(PartnerType::cases())
            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
    )
```

### ProductType in ProductResource

```php
use App\Enums\Products\ProductType;

Forms\Components\Select::make('type')
    ->label(__('product.type'))
    ->required()
    ->options(
        collect(ProductType::cases())
            ->mapWithKeys(fn (ProductType $type) => [$type->value => $type->label()])
    )
    ->searchable()
```

### VendorBillStatus in VendorBillResource

```php
use App\Enums\Purchases\VendorBillStatus;

Forms\Components\Select::make('status')
    ->label(__('vendor_bill.status'))
    ->options(
        collect(VendorBillStatus::cases())
            ->mapWithKeys(fn (VendorBillStatus $status) => [$status->value => $status->label()])
    )
    ->disabled()
    ->dehydrated(false)
```

### JournalType in JournalResource

```php


Forms\Components\Select::make('type')
    ->label(__('journal.type'))
    ->required()
    ->options(
        collect(JournalType::cases())
            ->mapWithKeys(fn (JournalType $type) => [$type->value => $type->label()])
    )
    ->searchable()
```

### DepreciationMethod in AssetResource

```php
use App\Enums\Assets\DepreciationMethod;

Forms\Components\Select::make('depreciation_method')
    ->label(__('asset.depreciation_method'))
    ->options(
        collect(DepreciationMethod::cases())
            ->mapWithKeys(fn (DepreciationMethod $method) => [$method->value => $method->label()])
    )
    ->required()
```

### LockDateType in LockDateResource

```php
use App\Enums\Accounting\LockDateType;

Forms\Components\Select::make('lock_type')
    ->options(
        collect(LockDateType::cases())
            ->mapWithKeys(fn (LockDateType $type) => [$type->value => $type->label()])
    )
    ->required()
```

## Table Columns

For table columns, you can also use the enum labels:

```php
Tables\Columns\SelectColumn::make('type')
    ->label(__('partner.type'))
    ->searchable()
    ->options(
        collect(PartnerType::cases())
            ->mapWithKeys(fn (PartnerType $type) => [$type->value => $type->label()])
    )
```

## Benefits

1. **Automatic Translation**: Labels change based on user's selected language
2. **Type Safety**: Full IDE support and type checking
3. **Consistency**: Same pattern across all enums
4. **Maintainability**: Centralized translation management
5. **Clean Code**: No hardcoded strings in Filament resources

## Migration Notes

-   Replace `EnumClass::class` with the new pattern
-   Replace `Model::getTypes()` static methods with enum pattern
-   Update any hardcoded options arrays
-   Ensure proper enum imports in resource files
