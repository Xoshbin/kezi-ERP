---
name: jmeryar-architecture
description: Technical architecture, patterns (Service-Action-DTO), and domain-specific workflows. Use when designing or implementing features.
---

# Copilot Instructions - JMeryar ERP System

## Project Overview

This is a **headless accounting ERP system** built on Laravel 12 with Filament 4. The system enforces **immutability**, **auditability**, and strict **double-entry bookkeeping** principles. Once financial documents are posted, they become immutable - corrections are made through reversing entries.

## Architecture Patterns

### Service-Action-DTO Pattern

-   **Services** (`app/Services/`) - Business orchestration and domain logic enforcement
-   **Actions** (`app/Actions/`) - Atomic business operations following Command pattern
-   **DTOs** (`app/DataTransferObjects/`) - Immutable data contracts with `readonly` properties

```php
// Standard Action pattern
class CreateInvoiceAction {
    public function execute(CreateInvoiceDTO $dto): Invoice {
        return DB::transaction(function () use ($dto) {
            // 1. Validate business rules
            // 2. Create entities
            // 3. Return result
        });
    }
}
```

### Money Handling (Critical)

-   Use `Brick\Money\Money` objects throughout - **never floats for financial data**
-   Custom `BaseCurrencyMoneyCast` for database storage as integers
-   Always compare Money objects with `isEqualTo()`, never `==`

```php
// Correct Money usage
$total = Money::zero($currencyCode);
$total = $total->plus($lineAmount);
if (!$debitTotal->isEqualTo($creditTotal)) { /* unbalanced */ }
```

### Immutability Enforcement

-   Posted documents (`is_posted = true`) cannot be modified
-   Use `JournalEntryObserver` to prevent updates to posted entries
-   Create reversing entries for corrections via dedicated services

## Key Development Workflows

### Testing

-   **Framework**: Pest (not PHPUnit syntax)
-   **Command**: `composer test` (clears config first)
-   **Structure**: Feature tests in domain folders (`tests/Feature/Accounting/`, `tests/Feature/Sales/`)
-   **Patterns**: Use Builders pattern for complex test data (`tests/Builders/`)

### Development Server

```bash
composer dev  # Runs server, queue, logs, and Vite concurrently
```

### Code Quality

-   **Static Analysis**: Larastan (`vendor/bin/phpstan`)
-   **Code Style**: Laravel Pint (`vendor/bin/pint`)
-   **IDE Helpers**: Auto-generated via `composer post-update-cmd`

## Domain-Specific Conventions

### Journal Entry Creation

All financial transactions create journal entries through `CreateJournalEntryAction`:

-   Validates period lock dates via `LockDateService`
-   Enforces debit/credit balance before posting
-   Creates cryptographic hash chain for audit trail
-   Links to source documents (`source_type`/`source_id`)

### Filament Integration

-   Resources delegate ALL business logic to Services/Actions
-   Form data → DTOs → Actions (never direct model manipulation)
-   Use `RelationManagers` for line items (invoice lines, journal entry lines)
-   Authorization through Laravel Policies

### Multi-Currency Support

-   All amounts stored in company base currency in `journal_entry_lines`
-   Original currency preserved in source documents
-   Use `CurrencyConverterService` for rate conversions
-   Exchange gain/loss handled via `ExchangeGainLossService`

## Critical Files & Patterns

### Core Services

-   `JournalEntryService` - Double-entry bookkeeping engine
-   `InvoiceService` - Sales document lifecycle
-   `VendorBillService` - Purchase document processing
-   `BankReconciliationService` - Bank statement matching

### Models with Observers

-   `JournalEntry` - `JournalEntryObserver` (hash chain, immutability)
-   All models have `AuditLogObserver` for change tracking

### Exception Handling

-   `PeriodIsLockedException` - Prevents modifications in closed periods
-   `DeletionNotAllowedException` - Blocks deletion of referenced records
-   Custom exceptions inherit from base Laravel exceptions

## Testing Patterns

```php
// Feature test structure
it('creates journal entry when invoice is confirmed', function () {
    $invoice = Invoice::factory()->draft()->create();

    app(InvoiceService::class)->confirm($invoice, $user);

    expect($invoice->fresh())
        ->status->toBe(Invoice::STATUS_POSTED)
        ->journalEntry->not->toBeNull();
});
```

## Documentation System

-   Markdown docs in `/docs` with YAML frontmatter
-   `DocumentationService` handles parsing and caching
-   Route: `/docs/{slug}` for accessing documentation

## Important Constraints

-   Never modify posted financial documents directly
-   Always use Service layer for business operations
-   Validate monetary calculations with Money objects
-   Test financial workflows with real accounting scenarios
-   Follow domain-driven organization in Actions/Services/DTOs
