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
