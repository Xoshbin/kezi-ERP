# Decision Log

This file records architectural and implementation decisions using a list format.
2025-07-22 20:21:43 - Log of updates made.

*

## Decision

*   Migrate the `VendorBillResource` to use the `VendorBillService` for all business operations.

## Rationale 

*   To centralize business logic, improve code maintainability, and ensure consistent handling of vendor bill operations across the application. This aligns with the project's service-oriented architecture and supports test-driven development.

## Implementation Details

*   The `CreateVendorBill`, `EditVendorBill`, and `ListVendorBills` pages in the `VendorBillResource` now delegate creation, update, deletion, and confirmation operations to the `VendorBillService` class. This change was implemented by modifying the `handleRecordCreation`, `handleRecordUpdate`, and custom action methods in the resource pages.

[2025-07-23 21:22:10] - Recorded the decision to migrate VendorBillResource to use VendorBillService.
## Decision

*

## Rationale 

*

## Implementation Details

*
[2025-07-24 19:36:10] - Refactored `VendorBillService::createJournalEntryForBill` to handle credit notes as true reversal transactions. Instead of applying the same debit/credit logic as a standard bill, the service now correctly inverts the entries for credit notes (debiting Accounts Payable, crediting expenses/taxes). This decision was made to align the implementation with the core accounting principle of immutability, where corrections are made via new, offsetting transactions rather than edits.
[2025-07-24 20:28:00] - **Decision:** Implemented an event-driven approach for posting journal entries related to vendor bills.
**Rationale:** The previous implementation created a journal entry in an un-posted state but failed to trigger the posting mechanism, causing feature tests to fail. By dispatching a `VendorBillConfirmed` event and creating a dedicated `PostJournalEntry` listener, the system now correctly decouples the creation of the journal entry from the posting process. This aligns with modern Laravel architecture, improves modularity, and ensures that the accounting workflow is completed reliably.
**Implementation Details:**
1.  Created a new `PostJournalEntry` listener in `app/Listeners`.
2.  The listener's `handle` method calls `JournalEntryService->post()` to finalize the journal entry.
3.  Registered the event and listener in `AppServiceProvider` to ensure they are correctly wired up by the framework.
[2025-07-24 21:08:00] - **Decision:** Refactored the `InvoiceService` and `PostJournalEntry` listener to resolve a critical bug preventing invoice confirmation.
**Rationale:** The original implementation created a race condition and violated the system's immutability principle. The `InvoiceService` was creating an already-posted journal entry, which an event listener then tried to post again. The fix was to align the invoice workflow with the working vendor bill workflow.
**Implementation Details:**
1.  Modified `InvoiceService::create()` to explicitly instantiate the `Invoice` model, ensuring a consistent state.
2.  Modified `InvoiceService::confirm()` to create the `JournalEntry` in a **draft** state, removing the `postImmediately` flag.
3.  The existing `PostJournalEntry` event subscriber now correctly and exclusively handles the final posting of the journal entry for both invoices and vendor bills, ensuring a clean, decoupled, and auditable two-step process.
[2025-07-24 21:55:15] - **Decision:** Added `default_debit_account_id` and `default_credit_account_id` columns to the `journals` table.
**Rationale:** A critical bug in `PaymentService` revealed an architectural flaw: there was no reliable way to determine which bank/cash account a payment journal represented. This violated the core principles of traceability and internal controls. Adding a direct, non-nullable link from a journal to its default accounts is the only robust, scalable, and auditable solution. This change supports multi-company and multi-currency setups by design.
**Implementation Details:**
1.  add the foreign key columns to the `journals` table.
2.  Updated the `Journal` Eloquent model with the new `fillable` attributes and `defaultDebitAccount()` / `defaultCreditAccount()` relationships.
3.  Refactored `PaymentService` to use the new `journal->default_debit_account_id` relationship instead of a fragile global config.
4.  Updated the `AccountingWorkflowTest` to correctly configure the test journal with its default account.
[2025-07-24 23:14:34] - **Decision:** Corrected multiple test suite flaws to resolve a critical bug and subsequent regressions.
**Rationale:** The initial bug, a silent save failure, was traced to a case-sensitivity typo in the test data for `AccountingWorkflowTest`. Fixing this revealed that other tests in `AccountingTest.php` were brittle and improperly configured, causing regressions. The decisions were made to harden the test suite to make it a more accurate reflection of the application's accounting principles.
**Implementation Details:**
1.  **Corrected `MoneyCast` Return Type:** The `MoneyCast::get` method was modified to return a `float` instead of a formatted `string`. This enforces data integrity by ensuring that internal representations of monetary values are numeric, preventing type-related errors in calculations and audit logs.
2.  **Hardened `JournalFactory`:** The `JournalFactory` was updated to prevent the creation of duplicate `Currency` and `Company` records. More importantly, it was modified to require the explicit setting of `default_debit_account_id` and `default_credit_account_id`, ensuring that all factory-created journals are valid for use in payment transactions.
3.  **Fixed Test Setups:** The failing payment-related tests in `AccountingTest.php` were refactored to create `Journal` instances with the specific default accounts required by the business logic they were intended to validate. This makes the tests more explicit and reliable.
[2025-07-25 00:33:13] - **Decision:** Resolved a multi-step bug in `AccountingWorkflowTest` related to `AdjustmentDocument` posting.
**Rationale:** The initial `BadMethodCallException` revealed deeper issues, including incorrect service logic, missing test configuration, and improper handling of the application's `MoneyCast`. The fixes were made sequentially to align the `AdjustmentDocument` workflow with the project's core accounting and architectural principles.
**Implementation Details:**
1.  Refactored the test to use `AdjustmentDocumentService`, respecting the service-oriented architecture.
2.  Corrected the service to use the appropriate contra-revenue account (`Sales Discounts & Returns`) for credit notes.
3.  Updated the test to be self-contained by providing the necessary `default_sales_discount_account_id` via `config()`.
4.  Modified the service to ensure the resulting `JournalEntry` was posted immediately, satisfying test assertions.
5.  Corrected the test's database assertions to use the correct integer-based values expected by the `MoneyCast`, ensuring data integrity.
[2025-07-25 00:43:11] - **Decision:** Refactored `AdjustmentDocumentService` to use an event-driven architecture for posting journal entries.
**Rationale:** This change aligns the adjustment document workflow with the existing patterns for invoices and vendor bills, creating a consistent and decoupled architecture. It enhances modularity and maintainability by centralizing the journal posting logic in the `PostJournalEntry` listener.
**Implementation Details:**
1.  Created the `AdjustmentDocumentPosted` event.
2.  Modified `AdjustmentDocumentService::post()` to create the journal entry in a draft state and dispatch the `AdjustmentDocumentPosted` event.
3.  Updated the `PostJournalEntry` listener to subscribe to and handle the new event.
[2025-07-25 15:43:41] - **Decision:** Resolved a multi-step bug in `AccountingTest` related to `AdjustmentDocument` posting.
**Rationale:** The initial `ValidationException` revealed deeper issues, including missing test configuration and incorrect test assertions. The fixes were made sequentially to align the `AdjustmentDocument` test with the project's core accounting and architectural principles.
**Implementation Details:**
1.  Updated the test to be self-contained by providing the necessary `default_sales_discount_account_id` via `config()`.
2.  Corrected the test's database assertions to use the correct integer-based values expected by the `MoneyCast`, ensuring data integrity.
3.  Corrected a copy-paste error in the test assertions to check for the correct account ID.
[2025-07-26 22:00:21] - **Decision:** Refactored the `checkIfPeriodIsLocked` method from `JournalEntryService` into a new, dedicated `AccountingValidationService`.
- **Rationale:** To centralize shared business logic, eliminate code duplication, improve maintainability, and adhere to the Single Responsibility Principle. This makes the system more scalable and easier to test.
- **Implications:** All services requiring this validation (`JournalEntryService`, `VendorBillService`, etc.) must now inject `AccountingValidationService`. All service instantiations throughout the app (including tests and UI components like Filament pages) must be updated to use the service container (`app(...)`) to handle dependency injection correctly.
[2025-07-27 07:13:52] - **Decision:** Migrated default accounting settings from a static configuration file to the `companies` table in the database.
- **Rationale:** The original approach of using a global `config()` file was a significant architectural flaw in a multi-company application. Storing these settings on a per-company basis in the database is essential for scalability, flexibility, and proper multi-tenancy. This change resolves the immediate `RuntimeException` and aligns the application with its core design principles.
- **Implications:** All services that create journal entries (`VendorBillService`, `InvoiceService`, `PaymentService`, etc.) have been refactored to read these settings from the relevant `Company` model. The `CompanyResource` UI, database seeders, and the entire test suite have also been updated to support this new, more robust architecture.
[2025-07-27 07:33:28] - **Decision:** Resolved multiple cascading test failures after a major architectural refactoring.
- **Rationale:** The migration of default accounting settings from `config()` to the `companies` table broke the entire test suite. The fixes were applied sequentially to address the root causes.
- **Implementation Details:**
    1.  **Service Instantiation:** Replaced all direct `new Service()` calls in `tests/Feature/AccountingTest.php` with `app(Service::class)` to ensure correct dependency injection.
    2.  **Test Trait Configuration:** Corrected the `createConfiguredCompany()` method in `tests/Traits/CreatesApplication.php` to provide all required default accounts and journals, resolving multiple `RuntimeException`s.
    3.  **Factory Correction:** Modified the `CompanyFactory` and `JournalFactory` to use `Currency::firstOrCreate()` instead of `Currency::factory()`, eliminating the creation of duplicate currencies.
    4.  **Test Isolation:** Refactored the `creating a currency with an existing code is prevented` test to create its own unique currency, preventing conflicts with the shared 'IQD' currency.
[2025-07-29 16:01:19] - **Decision:** Corrected a critical bug in the tax calculation logic that caused incorrect journal entries for vendor bills.
- **Rationale:** The `Tax` model was incorrectly casting its `rate` attribute to an `integer` instead of a `float`. This caused the tax rate to be truncated to zero during calculations, leading to incorrect `total_amount` on vendor bills and failed test assertions. The fix ensures that tax rates are handled as numeric values, preserving precision and aligning with accounting principles.
- **Implementation Details:**
    1.  Modified the `rate` attribute's cast in `app/Models/Tax.php` from `integer` to `float`.
    2.  Updated the `CreateJournalEntryForVendorBillActionTest` to create the tax `rate` as a `float` (e.g., `0.10`) instead of a `Money` object, which was causing the incorrect type coercion.
    3.  Ensured the `VendorBillLineObserver` correctly uses the `float` value for tax calculations.