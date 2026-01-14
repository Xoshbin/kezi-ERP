---
name: jmeryar-coding-style
description: Official coding styles, architectural patterns, and best practices. Use when writing or refactoring code.
---

This document outlines the official coding styles, architectural patterns, and best practices for this application. Adherence to these standards is mandatory to ensure consistency, maintainability, and robustness.

## 1. Core Principles

- **Immutability is Law:** Posted financial records (invoices, bills, journal entries) can NEVER be edited or deleted. Corrections are made only through new, reversing transactions.
- **Manual Data Entry First:** The system relies on manual input. No third-party payment integrations.
- **Business Logic-Focused TDD:** All tests must focus on the core business logic (services, calculations, state changes) using Pest.
- **No Temporary Hacks:** All development must strictly adhere to the established architectural patterns (Actions, DTOs, Services), prioritize code reusability, and align with the overall system design.
- **Single Responsibility Principle (SRP):** Each class, method, or function should have only one reason to change.
- **Definitive Solution Pattern:** Business logic for pre-save calculations resides exclusively within a dedicated Action that accepts a DTO. Observers are reserved for side effects.
- **Explicit Context Pattern:** The responsibility for providing context to a new model instance must be shifted from the model itself to the calling code (the Action or Service).
- **Lowercase Enum & Option Values:** All enum values stored in the database **MUST** be lowercase, `snake_case`.
- **Architectural Consistency:** Analyze the codebase carefully and follow existing patterns.
- **Targeted Changes:** Modify only the code that needs to be changed.
- **Respect Accounting Principles:** Do not violate core accounting principles.
- **Preserve Comments:** Do not remove comments unless they are no longer relevant.

## 2. The Journal Entry as the Single Source of Truth

- **Financial Impact via Journal Entry:** Any model with financial impact (e.g., `Invoice`, `VendorBill`, `Payment`) **MUST** have a polymorphic relationship to the `JournalEntry` model.
- **Decoupled Creation and Posting:** Creation of a `JournalEntry` (in a `draft` state) **SHALL** be decoupled from its posting.
- **Source of Truth for Reports:** All financial reports (Trial Balance, P&L, Balance Sheet) **MUST** be generated exclusively from the `journal_entry_lines` table.
- **Data Consistency:** The `JournalEntry` **MUST** store key redundant data at the time of posting.

## 3. State Management: PHP 8.1+ Backed Enums

**Rule:** All state management **MUST** be implemented using PHP 8.1+ Backed Enums. Enum cases **MUST** be `PascalCase`, while their corresponding string values **MUST** be `snake_case`.

```php
// Modules/Accounting/app/Enums/InvoiceStatus.php
namespace Modules\Accounting\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';
}
```

## 4. Layered Architecture

The application follows a strict layered architecture within each module.

### 4.1. Actions Layer

**Location:** `Modules/{Module}/app/Actions/`

**Purpose:** Encapsulate a single, specific business operation (Command Pattern).

**Rules:**
- Each Action **MUST** have a single public `execute()` method.
- The `execute()` method **MUST** be wrapped in a `DB::transaction()`.
- Actions **SHOULD** accept a DTO for input.
- Actions are organized by domain subdirectory.

```php
// Modules/Accounting/app/Actions/Accounting/CreateJournalEntryAction.php
namespace Modules\Accounting\Actions\Accounting;

class CreateJournalEntryAction {
    public function execute(CreateJournalEntryDTO $dto): JournalEntry {
        return DB::transaction(function () use ($dto) {
            // Business logic here
        });
    }
}
```

### 4.2. Data Transfer Objects

**Location:** `Modules/{Module}/app/DataTransferObjects/`

**Purpose:** Provide type-safe, immutable data contracts for transferring data between layers.

**Rules:**
- All DTOs **MUST** be `readonly` classes.
- All properties **MUST** be `public readonly` with strict type hints.
- DTOs contain **NO** business logic.

```php
// Modules/Accounting/app/DataTransferObjects/DunningLevelDTO.php
namespace Modules\Accounting\DataTransferObjects;

readonly class DunningLevelDTO
{
    public function __construct(
        public string $name,
        public int $daysOverdue,
        public float $feePercentage,
    ) {}
}
```

### 4.3. Service Layer

**Location:** `Modules/{Module}/app/Services/`

**Purpose:** Orchestrate complex business workflows that may involve multiple Actions.

**Rules:**
- Services contain high-level business process logic.
- Services call Actions to perform data modifications.
- Services are responsible for dispatching domain events.
- Services do **NOT** directly modify data.

### 4.4. Observers

**Location:** `Modules/{Module}/app/Observers/`

**Purpose:** React to Eloquent model lifecycle events for system-level data integrity.

**Rules:**
- Used for **System Reactions**, NOT business rule authorization.
- Register using the `#[ObservedBy]` attribute on the model.

### 4.5. Policies

**Location:** `Modules/{Module}/app/Policies/`

**Purpose:** Handle all user Authorization via Filament Shield.

**Rules:**
- All authorization checks **MUST** be handled by a Policy.
- Do **NOT** place authorization logic in Observers, Services, or Actions.

## 5. Module Placement Guide

When adding new features, use this guide:

| Feature Type | Module |
|-------------|--------|
| Journal entries, fiscal periods, taxes | Accounting |
| Partners, companies, currencies, settings | Foundation |
| Customer invoices, quotes | Sales |
| Vendor bills, purchase orders | Purchase |
| Stock operations, valuation | Inventory |
| Employees, payroll, leaves | HR |
| Payment allocation | Payment |
| Product catalog | Product |
| Projects, timesheets | ProjectManagement |
| BOMs, manufacturing orders | Manufacturing |
| Quality checks | QualityControl |

## 6. Financial Calculations

**Rule:** All monetary values **MUST** be handled using `Brick\Money` objects. Never use floats.

```php
use Brick\Money\Money;

// Correct usage
$total = Money::zero('IQD');
foreach ($lines as $line) {
    $total = $total->plus($line->amount);
}

// Never do this
// $total = 0.0;
// $total += $line->amount->getAmount()->toFloat();
```

## 7. Namespace Conventions

All module code uses the `Modules\{ModuleName}` namespace:

```php
// Models
use Modules\Accounting\Models\JournalEntry;
use Modules\Foundation\Models\Partner;

// Actions
use Modules\Accounting\Actions\Accounting\CreateJournalEntryAction;

// Services
use Modules\Accounting\Services\JournalEntryService;

// DTOs
use Modules\Accounting\DataTransferObjects\CreateJournalEntryDTO;

// Enums
use Modules\Accounting\Enums\InvoiceStatus;
```

## 8. Internationalization and Localization

**Rule:** All UI strings **MUST** be localized and scoped to their respective modules.

- **No Global Translations:** Do not use `lang/en.json`, `lang/ckb.json`, or any global translation files at the root level. All translations must reside within the modules.
- **Modular Scoping:** Use the `module::file.key` syntax for all translations (e.g., `__('accounting::cheque.label')`).
- **No Hardcoded Strings:** UI strings (labels, titles, placeholders, helper texts, notifications, buttons) **MUST NOT** be hardcoded.
- **Consistency Across Locales:** Ensure translation keys exist and are translated in all supported locales:
    - `en` (English)
    - `ckb` (Kurdish Sorani)
    - `ar` (Arabic)
- **Filament Integration:** Always use translation keys in Filament components:
    - `getModelLabel()`, `getPluralModelLabel()`
    - `getNavigationLabel()`, `getNavigationGroup()`
    - `label()`, `placeholder()`, `helperText()`
    - `Action::make('name')->label(__('module::file.key'))`
- **Enum Translations:** Translate enum values in the UI using the `module::file.enum_case` pattern or by implementing a `getLabel()` method on the enum that returns `__('module::file.status.' . $this->value)`.
- **Naming Convention:** Translation files within `Modules/{Module}/resources/lang/{locale}/` should be named after the feature or entity they describe (e.g., `cash_advance.php` for HR cash advances).
