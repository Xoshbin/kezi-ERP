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