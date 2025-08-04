# System Patterns *Optional*

This file documents recurring patterns and standards used in the project.
It is optional, but recommended to be updated as the project evolves.
2025-07-22 20:21:53 - Log of updates made.

*

## Coding Patterns

*   **Service-Oriented Architecture:** Business logic for financial operations (e.g., creating, updating, confirming, deleting vendor bills) is encapsulated within dedicated service classes (e.g., `VendorBillService`). This pattern is used to ensure separation of concerns, promote code reusability, and maintain a clean and testable codebase. The Filament resource pages (e.g., `CreateVendorBill`, `EditVendorBill`) act as the interface and delegate all business logic to the corresponding service.

[2025-07-23 21:22:00] - Documented the service-oriented pattern used in the VendorBillResource migration.
## Coding Patterns

*   

## Architectural Patterns

*   

## Testing Patterns

*
[2025-07-24 21:55:54] - **Journal to Account Linking:** Core accounting journals (like 'Bank' or 'Cash') are now directly linked to their corresponding default debit and credit accounts in the Chart of Accounts via `default_debit_account_id` and `default_credit_account_id` columns in the `journals` table. This pattern ensures a robust, auditable, and unambiguous link between a financial transaction's source journal and its ledger impact, which is critical for internal controls and scalability.
[2025-07-26 22:00:39] - **Pattern: Dedicated Validation Services**
- **Description:** Shared business logic validations (e.g., checking for locked accounting periods) are encapsulated within their own dedicated service classes (e.g., `AccountingValidationService`).
- **Implementation:** These services are injected via the constructor into other services or resolved from the container in UI components. This promotes the Single Responsibility Principle, enhances testability by allowing for easy mocking, and improves code maintainability by centralizing logic.
[2025-07-27 07:14:02] - **Pattern: Company-Specific Default Accounts**
- **Description:** Critical accounting settings, such as default accounts for payables, receivables, and taxes, and default journals for different transaction types, are stored directly on the `companies` table in the database.
- **Implementation:** This pattern replaces the use of a global `config()` file, which is unsuitable for a multi-tenant application. All services that generate journal entries now retrieve these settings from the `Company` model associated with the transaction. This ensures that each company's financial data is correctly and independently configured, which is a cornerstone of a robust multi-company accounting system.

[2025-08-01 17:27:00] - **Pattern: Actions/DTOs Architecture**
- **Description:** All business operations follow a consistent Actions/DTOs pattern where DTOs provide type-safe data contracts and Actions encapsulate business logic execution.
- **Implementation:**
  - DTOs use readonly properties for immutability and type safety
  - Actions have single `execute()` methods with database transactions
  - Domain-driven organization (Accounting, Sales, Purchases, Payments, Adjustments)
  - Filament resources delegate to Actions instead of direct model manipulation
  - Consistent error handling and validation patterns across all operations

[2025-08-01 17:27:00] - **Pattern: Livewire Integration with Filament**
- **Description:** Complex interactive UI components use Livewire for real-time updates while leveraging Filament's form and action systems for modals and forms.
- **Implementation:**
  - Livewire components handle real-time calculations and state management
  - Filament actions provide modal interfaces for complex operations
  - Computed properties for efficient reactive calculations
  - Service injection for business logic execution
  - Proper Money object handling in reactive contexts

[2025-08-01 17:27:00] - **Pattern: Financial Precision with Money Objects**
- **Description:** All monetary values are handled using Brick\Money objects throughout the application to ensure precise financial calculations.
- **Implementation:**
  - MoneyCast for automatic Money object conversion in Eloquent models
  - Consistent Money object usage in Actions, Services, and UI components
  - Proper handling of different currencies and exchange rates
  - Minor amount storage in database with automatic Money object hydration
  - Specialized financial calculation actions that preserve precision

[2025-08-01 17:27:00] - **Pattern: Interactive Bank Reconciliation**
- **Description:** Bank reconciliation uses a dedicated Livewire component for real-time matching between bank statement lines and system payments.
- **Implementation:**
  - Real-time calculation of totals and differences using computed properties
  - Interactive selection of items to reconcile
  - Write-off functionality for unmatched items with proper journal entry creation
  - Integration with the established Actions/Services architecture
  - Comprehensive validation before allowing reconciliation operations
  
  [2025-08-02 04:40:00] - **Pattern: State Management with Backed Enums**
  - **Description:** All state management (e.g., `status`, `state`, `type`) is implemented using PHP 8.1+ Backed Enums. This provides absolute type safety and self-documenting, discoverable states.
  - **Implementation:**
    - Enums are defined in the `app/Enums/` directory, organized by domain.
    - Models cast their state attributes directly to the corresponding Enum class.
    - This pattern is now the standard and replaces the older class constant approach.

[2025-08-03 19:22:37] - **Definitive Solution Pattern:** For models requiring pre-save calculations, the business logic resides exclusively within a dedicated Action that accepts a DTO. Observers are reserved for side effects (e.g., updating a parent model's totals after a line item is saved). This pattern ensures that complex calculations are handled within a transactional, testable, and dedicated class, while Observers are kept clean and focused on reactive side effects rather than primary business logic.

[2025-08-04 07:15:51] - **Pattern: Child Model Currency Resolution**
- **Description:** Child models that have monetary values but do not have their own `currency_id` column must resolve their currency from their parent model. This is achieved by implementing a `getCurrencyIdAttribute` accessor on the child model.
- **Implementation:** The accessor must be robust enough to handle cases where the parent relationship is not yet loaded, especially during model creation. The recommended approach is to use the null coalescing operator to lazy-load the parent relationship if it's not already available.
- **Example:**
  ```php
  // In a child model like InvoiceLine or JournalEntryLine
  public function getCurrencyIdAttribute(): int
  {
      // If the relationship is loaded, use it. If not, lazy-load it.
      return $this->parentModel->currency_id ?? $this->parentModel()->first()->currency_id;
  }
  ```- **Usage:** To prevent N+1 query issues in performance-critical code, it is the developer's responsibility to eager-load the parent relationship (e.g., `->with('parentModel')`) when retrieving multiple child models.

[2025-08-04 07:18:00] - **Pattern: Handling Money Objects in Actions and Tests**
- **Description:** When working with `Brick\Money` objects that are hydrated by the `MoneyCast`, it's crucial to handle them correctly in Actions and tests to avoid `NumberFormatException` and assertion errors.
- **Implementation:**
  - **In Actions:** When an Action receives a model with a `Money` object attribute (e.g., `$asset->purchase_value`), that attribute is already a `Money` object. Do not attempt to create a new `Money` object from it (e.g., `Money::of($asset->purchase_value, ...)`). Simply use the object directly. If you need to pass the value to a DTO that expects a `Money` object, you can pass the object as is.
  - **In DTOs:** When a DTO receives a raw numeric value (e.g., from a form input), use `Money::of()` to create the `Money` object, specifying the currency.
  - **In Tests:** When making database assertions (`assertDatabaseHas`), always use the minor currency unit (e.g., `10000000` for 10,000 IQD). The `MoneyCast` stores values in their minor form, and tests must reflect this.
  - **Docblocks:** Ensure that model docblocks correctly type-hint monetary properties as `\Brick\Money\Money` (e.g., `@property \Brick\Money\Money $purchase_value`) to provide accurate static analysis and prevent linter confusion.

[2025-08-04 07:18:00] - **Pattern: Polymorphic Relationships and `source_type`**
- **Description:** When creating records that use polymorphic relationships (e.g., `JournalEntry` with a `source` relation), the `source_type` column must be set to the fully qualified class name of the source model (e.g., `App\Models\Asset::class`).
- **Implementation:**
  - **In Actions:** When creating a DTO for a polymorphic model, pass the `::class` constant of the source model to the `source_type` property.
  - **In Tests:** When querying for a polymorphic relationship in a test, use the `::class` constant in your `assertDatabaseHas` call. Avoid using hardcoded strings, as this can lead to errors if the model namespace changes. When querying the relationship directly in a test (e.g., `$asset->journalEntries()`), do not add an additional `where('source_type', ...)` clause, as the relationship definition already handles this.
