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