---
name: jmeryar-architecture
description: Technical architecture, patterns (Service-Action-DTO), and domain-specific workflows. Use when designing or implementing features.
---

# JMeryar ERP Architecture

## Project Structure

This is an **ERP accounting system** built on Laravel 12 with Filament 4, organized into domain-specific modules.

### Modular Architecture

Code is organized using `nwidart/laravel-modules`:

```
Modules/
├── Accounting/          # Core accounting engine
├── Foundation/          # Shared infrastructure (Partners, Companies, Currencies)
├── Sales/               # Customer invoices, quotes
├── Purchase/            # Vendor bills, purchase orders
├── Inventory/           # Stock management, valuation
├── HR/                  # Employees, payroll, attendance
├── Payment/             # Payment processing
├── Product/             # Product catalog
├── ProjectManagement/   # Projects, timesheets
├── Manufacturing/       # BOMs, manufacturing orders
└── QualityControl/      # Quality checks
```

### Module Structure

Each module follows this structure:

```
Modules/{ModuleName}/
├── app/
│   ├── Actions/              # Business operations (Command Pattern)
│   ├── DataTransferObjects/  # Immutable data contracts
│   ├── Services/             # Business orchestration
│   ├── Models/               # Eloquent models
│   ├── Enums/                # State management (PHP 8.1+ backed enums)
│   ├── Filament/             # Admin panel resources
│   ├── Observers/            # Model lifecycle reactions
│   ├── Policies/             # Authorization
│   ├── Events/               # Domain events
│   └── Listeners/            # Event handlers
├── database/
│   ├── migrations/
│   └── factories/
├── tests/
│   ├── Feature/
│   └── Unit/
└── resources/
```

## Architecture Patterns

### Service-Action-DTO Pattern

- **Actions** (`Modules/{Module}/app/Actions/`) - Atomic business operations
- **DTOs** (`Modules/{Module}/app/DataTransferObjects/`) - Immutable data contracts
- **Services** (`Modules/{Module}/app/Services/`) - Business orchestration

```php
// Example: Modules/Accounting/app/Actions/Accounting/CreateJournalEntryAction.php
namespace Modules\Accounting\Actions\Accounting;

class CreateJournalEntryAction {
    public function execute(CreateJournalEntryDTO $dto): JournalEntry {
        return DB::transaction(function () use ($dto) {
            // 1. Validate business rules
            // 2. Create entities
            // 3. Return result
        });
    }
}
```

### Money Handling (Critical)

- Use `Brick\Money\Money` objects throughout - **never floats for financial data**
- Custom `MoneyCast` for database storage
- Compare Money objects with `isEqualTo()`, never `==`

```php
$total = Money::zero($currencyCode);
$total = $total->plus($lineAmount);
if (!$debitTotal->isEqualTo($creditTotal)) { /* unbalanced */ }
```

### Immutability Enforcement

- Posted documents (`is_posted = true`) cannot be modified
- Use `JournalEntryObserver` to prevent updates to posted entries
- Create reversing entries for corrections via dedicated services

## Key Development Workflows

### Testing

- **Framework:** Pest (not PHPUnit syntax)
- **Commands:**
  - `php artisan test --parallel` - Run all tests
  - `./vendor/bin/phpstan analyse` - Static analysis
- **Test Locations:**
  - Root: `tests/Feature/`, `tests/Unit/`
  - Module: `Modules/{Module}/tests/Feature/`, `Modules/{Module}/tests/Unit/`
- **Patterns:** Use Builders pattern for complex test data (`tests/Builders/`)

### Code Quality

- **Static Analysis:** Larastan (`./vendor/bin/phpstan analyse`)
- **Code Style:** Laravel Pint (`./vendor/bin/pint`)

## Domain-Specific Conventions

### Journal Entry Creation

All financial transactions create journal entries through `CreateJournalEntryAction`:

- Validates period lock dates via `LockDateService`
- Enforces debit/credit balance before posting
- Creates cryptographic hash chain for audit trail
- Links to source documents (`source_type`/`source_id`)

### Filament Integration

- Resources delegate ALL business logic to Services/Actions
- Form data → DTOs → Actions (never direct model manipulation)
- Use `RelationManagers` for line items
- Authorization through Policies + Filament Shield for RBAC

### Cross-Module Dependencies

Modules communicate through:
- **Models:** Import models from other modules as needed
- **Events:** Dispatch events that other modules can listen to
- **Services:** Inject services from Foundation or other base modules

```php
// Example: Importing from Foundation module
use Modules\Foundation\Models\Partner;
use Modules\Foundation\Services\CurrencyConverterService;
```

## Core Accounting Services

Key services in `Modules/Accounting/app/Services/`:

- `JournalEntryService` - Double-entry bookkeeping engine  
- `BankReconciliationService` - Bank statement matching
- `AssetService` - Asset management and depreciation
- `FiscalYearService` - Fiscal period management
- `LockDateService` - Period lock enforcement
- `ExchangeGainLossService` - Multi-currency gain/loss
- `Reports/` - Financial reporting (P&L, Balance Sheet, Trial Balance, etc.)

## Important Constraints

- Never modify posted financial documents directly
- Always use Service layer for business operations
- Validate monetary calculations with Money objects
- Test financial workflows with real accounting scenarios
- Place new features in the appropriate module
