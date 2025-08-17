# Headless Accounting ERP System

## Overview

This project is a robust, headless accounting and ERP system built on the Laravel framework. It is designed from the ground up with the core principles of **immutability**, **auditability**, and strict adherence to **double-entry bookkeeping standards**. Inspired by the reliability of enterprise-grade systems like Odoo, it is tailored for environments that demand strong manual controls, data integrity, and a transparent, unalterable audit trail.

The system features a comprehensive suite of accounting modules managed through a clean, service-oriented architecture. All business logic is encapsulated within dedicated service classes, ensuring that financial rules are consistently enforced across the application, whether actions are initiated via an API, the administrative panel, or console commands. The administrative interface is powered by **Filament**.

## Core Principles

The architecture is built upon a foundation of strict accounting principles:

-   **Immutability**: Once a financial transaction is posted (e.g., an invoice is confirmed), it cannot be altered or deleted. All corrections are handled through new, offsetting transactions (like Credit Notes or reversing journal entries), preserving a complete and tamper-proof financial history.

-   **Auditability & Traceability**:

    -   **Comprehensive Audit Logs**: An `AuditLogObserver` automatically records all significant events, such as the creation or modification of records, tracking which user performed the action and what was changed.
    -   **Cryptographic Hashing**: Journal entries are cryptographically linked in a blockchain-style chain. Each posted entry contains the hash of the preceding entry, making it computationally infeasible to alter historical records without detection.
    -   **Source Linking**: Every journal entry is linked back to its originating document (e.g., an Invoice, Vendor Bill, or Payment), providing a clear and traceable path from the business document to the general ledger.

-   **Data Integrity**:

    -   The system enforces business rules at multiple levels, including a `JournalEntryService` that validates that all entries are balanced (debits equal credits) before posting.
    -   It prevents the use of deprecated accounts in new transactions.
    -   **Period Locking**: The application allows for the locking of accounting periods, preventing any transactions from being created or modified within a closed period.

-   **Service-Oriented Architecture**: Business logic is cleanly separated from the presentation layer. Services like `InvoiceService`, `VendorBillService`, and `JournalEntryService` contain the core workflows, making the system modular, scalable, and easy to maintain.

-   **Test-Driven Development (TDD)**: The system's integrity is guaranteed by a comprehensive feature test suite written with **Pest**. These tests validate the core accounting logic, ensuring that principles like immutability and transaction balance are never violated.

## Key Features

The application includes a wide range of accounting and ERP features:

-   **Core Accounting Engine**:

    -   Manages the Chart of Accounts, including rules for deprecating accounts instead of deleting them.
    -   Handles financial Journals (e.g., Sales, Purchases, Bank, Cash).
    -   Creates immutable, hash-chained Journal Entries as the ultimate source of financial truth.

-   **Sales & Invoicing**:

    -   Full lifecycle management for customer invoices (Draft -> Posted -> Paid).
    -   Automatic generation of corresponding journal entries upon invoice confirmation.
    -   Support for "reset to draft" functionality for posted invoices, which reverses the original journal entry and logs the action for audit purposes.

-   **Purchases & Vendor Bills**:

    -   Manages the complete lifecycle of vendor bills, from creation to posting.
    -   Ensures accurate tracking of expenses and accounts payable.
    -   Automatically generates journal entries to reflect liabilities and expenses.

-   **Payments & Reconciliation**:

    -   Handles both inbound (customer) and outbound (vendor) payments.
    -   Links payments to one or more invoices/bills.
    -   Features a two-step reconciliation process for bank transactions, moving funds from an "outstanding" account to the main bank account upon confirmation.

-   **Asset Management & Depreciation**:

    -   Tracks fixed assets from acquisition to disposal.
    -   Automates the periodic generation of depreciation entries and their corresponding journal entries.

-   **Multi-Currency Support**:

    -   Handles transactions in foreign currencies.
    -   Automatically converts amounts to the company's base currency for general ledger posting while preserving the original transaction amounts for reconciliation and reporting.

-   **Analytic & Budgetary Accounting**:
    -   Provides a flexible layer for management accounting by allowing journal entry lines to be tagged with **Analytic Accounts**.
    -   Enables cost and revenue tracking by project, department, or any other defined dimension.

## Technology Stack

-   **Backend**: Laravel 11
-   **Admin Panel**: Filament 4
-   **Testing**: Pest

## Architectural Highlights

-   **Service Layer**: Centralizes all business logic for consistency and maintainability.
-   **Observers**: Uses Eloquent Observers (`JournalEntryObserver`, `AuditLogObserver`) to automatically trigger actions like hashing, validation, and logging.
-   **Custom Exceptions**: Employs custom exceptions (e.g., `PeriodIsLockedException`, `DeletionNotAllowedException`) for clear and predictable error handling.
-   **Policies**: Leverages Laravel Policies for fine-grained authorization control over sensitive actions like resetting a posted document to draft.
-   **Custom Casts**: Uses a custom `MoneyCast` to ensure financial data is handled with precision, storing amounts as integers to avoid floating-point inaccuracies.

## Code Architecture & Patterns

### **Actions Layer (`app/Actions/`)**

The application follows the **Command Pattern** with domain-driven organization for business operations:

#### **Structure & Organization:**
- **`Accounting/`** - Core double-entry bookkeeping operations (9 actions)
  - Journal entry creation for various document types (invoices, bills, payments, reconciliation)
  - Bank statement management
  - Depreciation processing
- **`Sales/`** - Customer invoice lifecycle management (2 actions)
- **`Purchases/`** - Vendor bill operations (2 actions)
- **`Payments/`** - Payment processing workflows (1 action)
- **`Adjustments/`** - Credit/debit note handling (2 actions)

#### **Key Patterns:**
- **Single Responsibility**: Each action has one `execute()` method with clear input/output contracts
- **Atomic Operations**: All actions wrap operations in database transactions
- **Rich Domain Logic**: Actions contain proper accounting rules and business validation
- **Dependency Injection**: Actions properly inject required services (e.g., `LockDateService`)
- **Type Safety**: Actions accept strongly-typed DTOs and return specific model instances

**Example Action Structure:**
```php
class CreateJournalEntryForInvoiceAction
{
    public function execute(Invoice $invoice, User $user): JournalEntry
    {
        return DB::transaction(function () use ($invoice, $user) {
            // 1. Load relationships efficiently
            // 2. Validate business rules
            // 3. Create journal entry with proper accounting logic
            // 4. Return created entity
        });
    }
}
```

### **Data Transfer Objects (`app/DataTransferObjects/`)**

DTOs provide **type-safe data contracts** for transferring data between layers:

#### **Structure & Organization:**
- Mirror the Actions structure for consistency (Accounting, Sales, Purchases, Payments, Adjustments, Reconciliation)
- Separate DTOs for Create and Update operations
- Line-item DTOs for documents with sub-items (e.g., `CreateInvoiceLineDTO`)

#### **Key Patterns:**
- **Immutable Design**: All properties are `readonly` preventing accidental mutation
- **Constructor Injection**: All data passed via constructor with proper type hints
- **Validation at Boundaries**: DTOs define the data contract without business logic
- **Composition**: Complex DTOs compose simpler DTOs (e.g., Invoice contains InvoiceLine DTOs)

**Example DTO Structure:**
```php
class CreateInvoiceDTO
{
    /**
     * @param CreateInvoiceLineDTO[] $lines
     */
    public function __construct(
        public readonly int $company_id,
        public readonly int $customer_id,
        public readonly int $currency_id,
        public readonly string $invoice_date,
        public readonly string $due_date,
        public readonly array $lines,
        public readonly ?int $fiscal_position_id,
    ) {}
}
```

### **Service Layer (`app/Services/`)**

The service layer implements **business orchestration** and **domain logic enforcement**:

#### **Core Services:**
- **`JournalEntryService`** - Core double-entry bookkeeping engine
- **`InvoiceService`** - Sales document lifecycle management
- **`VendorBillService`** - Purchase document processing
- **`PaymentService`** - Payment processing and reconciliation
- **`LockDateService`** - Shared validation logic (period locking, etc.)
- **`BankReconciliationService`** - Bank statement matching and reconciliation

#### **Key Patterns:**
- **Event-Driven Architecture**: Services dispatch events (e.g., `InvoiceConfirmed`, `PaymentConfirmed`) that trigger subsequent processing
- **Immutability Enforcement**: Services prevent modification of posted documents, requiring reversals instead
- **Dependency Injection**: Services inject other services and validation dependencies
- **Transaction Management**: Complex operations wrapped in database transactions
- **Audit Trail Creation**: Services create detailed audit logs for sensitive operations

**Example Service Pattern:**
```php
class InvoiceService
{
    public function confirm(Invoice $invoice, User $user): void
    {
        if ($invoice->status !== Invoice::STATUS_DRAFT) return;
        
        DB::transaction(function () use ($invoice, $user) {
            // 1. Generate invoice number
            // 2. Update status and timestamps
            // 3. Create journal entry via Action
            // 4. Link journal entry to invoice
            // 5. Dispatch confirmation event
        });
    }
}
```

### **Filament Integration (`app/Filament/`)**

The admin interface follows **clean separation** between UI and business logic:

#### **Structure & Organization:**
- **Resources**: 18 main resources covering all business entities
- **Pages**: Custom Create/Edit/List pages for each resource
- **RelationManagers**: Manage related entities (e.g., invoice lines, journal entry lines)
- **Forms/Components**: Reusable form components

#### **Key Patterns:**
- **Service Delegation**: Filament pages delegate all business operations to Services and Actions
- **DTO Transformation**: Form data is transformed into DTOs before passing to Actions
- **Consistent Patterns**: All resources follow similar patterns for CRUD operations
- **Authorization Integration**: Resources respect Laravel policies for access control

**Example Filament Integration:**
```php
class CreateInvoice extends CreateRecord
{
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Transform form data into line DTOs
        $lineDTOs = collect($data['invoiceLines'])->map(fn($line) =>
            new CreateInvoiceLineDTO(...)
        );
        
        // 2. Create main DTO
        $invoiceDTO = new CreateInvoiceDTO(...);
        
        // 3. Delegate to Action
        return (new CreateInvoiceAction())->execute($invoiceDTO);
    }
}
```

### **Livewire Integration (`app/Livewire/`)**

The system incorporates **Livewire components** for complex interactive functionality that requires real-time updates:

#### **Structure & Organization:**
- **Interactive Financial Components**: Complex calculations and data matching (e.g., `BankReconciliationMatcher`)
- **Real-time Updates**: Components that need instant feedback without full page reloads
- **Integration with Filament**: Livewire components work alongside Filament's administrative interface

#### **Key Patterns:**
- **Service Integration**: Livewire components inject and use the same Services as other parts of the application
- **Computed Properties**: Efficient reactive calculations using Livewire's computed properties
- **Money Object Handling**: Proper handling of `Brick\Money` objects in reactive contexts
- **Form Integration**: Components integrate with Filament actions for modal forms and complex workflows
- **Real-time Validation**: Immediate feedback on user inputs and selections

**Example Livewire Component:**
```php
class BankReconciliationMatcher extends Component
{
    public Collection $bankStatementLines;
    public Collection $availablePayments;
    public array $selectedPayments = [];
    
    public function __construct(
        private BankReconciliationService $reconciliationService
    ) {}
    
    #[Computed]
    public function totalSelectedAmount(): Money
    {
        return $this->reconciliationService->calculateTotalAmount(
            $this->getSelectedPayments()
        );
    }
    
    public function reconcileSelected(): void
    {
        $this->reconciliationService->reconcilePayments(
            $this->getSelectedPayments(),
            $this->bankStatementLines->first()
        );
        
        $this->dispatch('reconciliation-completed');
    }
}
```

#### **Integration Benefits:**
- **Consistent Business Logic**: Livewire components use the same Services and Actions as the rest of the application
- **Type Safety**: Components maintain strong typing with Money objects and DTOs
- **Real-time Feedback**: Users get immediate visual feedback for complex financial operations
- **Seamless UI**: Interactive components blend naturally with Filament's administrative interface
- **Audit Trail**: All operations performed through Livewire components are properly logged and audited

### **Architectural Benefits**

This layered architecture provides several key benefits:

1. **Separation of Concerns**: UI, business logic, and data access are cleanly separated
2. **Testability**: Actions and Services can be unit tested independently
3. **Consistency**: Business rules are enforced uniformly across all entry points
4. **Maintainability**: Changes to business logic are localized to specific layers
5. **Type Safety**: DTOs and strong typing prevent runtime errors
6. **Audit Trail**: All operations are tracked and logged for compliance
7. **Immutability**: Financial data integrity is maintained through architectural constraints

### **Design Principles Applied**

- **Single Responsibility Principle**: Each class has one clear purpose
- **Open/Closed Principle**: New functionality added through new Actions/Services
- **Dependency Inversion**: High-level modules depend on abstractions (services)
- **Command Pattern**: Actions encapsulate discrete business operations
- **Event-Driven Architecture**: Loose coupling through domain events
- **Domain-Driven Design**: Code organization reflects business domains
