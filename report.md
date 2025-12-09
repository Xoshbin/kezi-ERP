Issue 1: Sales Module Navigation 500 Error

Name of Issue: Sales Module 500 Internal Server Error

Copy-pasteable prompt:

markdown
I am getting a 500 Internal Server Error when navigating to the "Sales" module/panel in this Laravel 12 + Filament 4 application.
**Context:**
- The system is split into modules (Accounting, Sales, etc.).
- Other modules like Accounting load fine.
- The error occurs immediately upon clicking the top-level "Sales" navigation item or accessing the Sales dashboard.
**Task:**
1. Analyze the `laravel.log` (use `read-log-entries`) to find the exact stack trace for the 500 error.
2. It is likely a missing class, a Service Provider registration issue in `SalesServiceProvider`, or a misconfiguration in the Filament `SalesPanelProvider` (e.g., a missing widget, resource, or invalid default route).
3. Fix the issue so the Sales dashboard/list loads correctly.


Issue 2: Invoice Status UI/Backend Mismatch

Name of Issue: Invoice UI Reverts to Draft Despite Backend Posting

Copy-pasteable prompt:

markdown
There is a critical state persistence bug in the Customer Invoice workflow (and potentially Vendor Bills).
**Symptoms:**
1. I create an Invoice, Click "Confirm", and then "Register Payment".
2. The UI shows success messages, and the backend **correctly creates** the persistent `JournalEntry` and `Payment` records (verified in DB).
3. HOWEVER, when the Invoice page reloads, the status in the UI reverts to `not_posted` (Draft), and the "Confirm" / "Register Payment" buttons disappear or reset.
4. The UI is out of sync with the database reality (which is Paid/Posted).
**Architecture Context:**
- **Filament 4** Resources using Livewire.
- State is likely managed via a `status` Enum on the `Invoice` model.
- We use the `Service-Action-DTO` pattern. The `ConfirmInvoiceAction` is likely successfully executing the transaction but the Filament Component isn't refreshing its view of the record correctly, or the `Invoice` model's `status` column isn't being updated/persisted despite the Journal Entry being created.
**Task:**
1. Check `ConfirmInvoiceAction` to ensure it explicitly updates the `Invoice` model's `status` to `posted` inside the transaction.
2. Check the Filament `InvoiceResource` (Edit Page) to ensure it acts on the fresh model instance and not a cached/stale one.
3. Verify that the `InvoiceObserver` or Service responsible for syncing status is actually firing.



Issue 3: Vendor Bill Confirmation Failure

Name of Issue: Vendor Bill Confirm Modal & Preview Error

Copy-pasteable prompt:

markdown
I cannot confirm Vendor Bills in the Accounting module.
**Symptoms:**
1. The "Preview Posting" action throws a **500 Internal Server Error**.
2. The "Confirm" button opens a modal that is flaky/unresponsive, or clicking "Confirm" inside it does nothing effectively (the bill remains in `not_posted` status).
3. Unlike Sales Invoices, no Journal Entry is created in the backend, meaning the action is failing entirely.
**Task:**
1. **Fix Preview Posting**: Investigate the 500 error. It is likely a missing Blade view for the preview modal or a crash in the calculation logic in `PreviewJournalEntryAction`.
2. **Fix Confirmation**: Debug the `ConfirmVendorBillAction`. Ensure it properly validates the bill (e.g., checks for Expense Account presence) and returns a clear error if validation fails, rather than failing silently.
3. Ensure the `EditVendorBill` Filament page properly handles the confirmation response and refreshes the page state.