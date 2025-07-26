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