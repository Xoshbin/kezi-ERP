## The Core Story: The Launch of "Jmeryar ERP"

Soran, an ambitious IT professional in Slemani, decides to start his own consulting firm, "Jmeryar ERP". His company will provide IT setup and support services to local businesses. We will meticulously follow his first few transactions to test the core functionality of your accounting application. The primary currency for Jmeryar ERP will be the Iraqi Dinar (IQD).

> **A Note on Monetary Values and Precision for Junior Developers:** In financial systems, to prevent floating-point arithmetic errors that can lead to tiny, but critical, discrepancies (e.g., `99.9999999999` vs `100.00`), it's standard practice to store monetary values as integers representing the smallest unit of currency (e.g., cents for USD, fils for IQD). For example, 15,000,000 IQD might be stored as `1500000000` if your smallest unit is 100 fils. While the `journal_entries` table schema in the sources uses `Decimal`, internal application logic often converts to integers for calculations and back to decimals for display. For clarity in this scenario, we will use the stated IQD values directly as if they were already scaled for storage, but remember this crucial principle for your implementation.

---

## Step 1: Foundational Setup (Entity, Currency, User)

*Goal: Establish the company as a separate legal entity and set up the necessary base currencies and a primary user. This tests the fundamental configuration of your system, ensuring all subsequent financial data can be properly attributed.*

### • 1.1. Create the Primary Currency:
* **Accounting Rationale:** Every financial transaction occurs in a specific currency. Defining the base currency (IQD) is the first step in enabling accurate monetary calculations and reporting.
* **Resource:** `CurrencyResource`
* **Action:** Click "New Currency".
* **Enter Data:**
    * `code`: IQD
    * `name`: Iraqi Dinar
    * `symbol`: ع.د
    * `exchange_rate`: 1.0 (as it's our base currency)
    * `is_active`: true
* **Expected Outcome:** The IQD currency is successfully created and available for all transactions. Technically, verify that the `currencies` table contains this record.

### • 1.2. Create the Company "Jmeryar ERP":
* **Accounting Rationale:** The Entity Concept dictates that a business is separate from its owner. All financial activities belong to "Jmeryar ERP," not Soran personally. This is crucial for accurate financial statements.
* **Resource:** `CompanyResource`.
* **Action:** Click "New Company".
* **Enter Data:**
    * `name`: Jmeryar ERP
    * `address`: Slemani, Kurdistan Region, Iraq
    * `tax_id`: (Leave blank for now, as tax registration might come later)
    * `currency_id`: Select "Iraqi Dinar" (the id of the IQD currency created above).
    * `fiscal_country`: IQ (Important for future localization and tax compliance).
* **Expected Outcome:** The company "Jmeryar ERP" is successfully created. All subsequent data should be linked to this entity. Technically, verify its existence in the `companies` table.

### • 1.3. Create the Primary User "Soran":
* **Accounting Rationale:** Every action within an accounting system must be traceable to an individual for auditability and accountability. Soran is the initial actor.
* **Resource:** `UserResource`.
* **Action:** Click "New User".
* **Enter Data:**
    * `company_id`: Select "Jmeryar ERP" (the id of the company created above).
    * `name`: Soran
    * `email`: soran@jmeryarerp.com
    * `password`: (A secure password. Ensure Laravel's `Hash::make()` is used for secure storage.)
* **Expected Outcome:** Soran is created as a user who can perform transactions for the company. Technically, verify user creation and secure password hashing.

---

## Step 2: Building the Chart of Accounts

*Goal: Create the essential accounts for our financial transactions. This is the fundamental backbone of the general ledger, defining the "buckets" for all financial data.*

* **Accounting Rationale:** The Chart of Accounts (COA) is a complete, structured list of all financial accounts. Each account has a unique code and belongs to a specific type (Asset, Liability, Equity, Income, Expense). Correct categorization is vital for generating accurate financial reports like the Balance Sheet and Profit & Loss Statement. Accounts can be deprecated, not deleted, if they have associated transactions.
* **Resource:** `AccountResource` for all items in this step.
* **Action:** Click "New Account" for each account below.
    * Code: `1010`, Name: `Bank`, Type: `Asset`
    * Code: `1200`, Name: `Accounts Receivable`, Type: `Asset`
    * Code: `1500`, Name: `IT Equipment`, Type: `Asset` (Fixed Asset - a long-term asset)
    * Code: `1501`, Name: `Accumulated Depreciation`, Type: `Asset` (Contra-Asset - reduces the value of a related asset account)
    * Code: `2100`, Name: `Accounts Payable`, Type: `Liability`
    * Code: `3000`, Name: `Owner's Equity`, Type: `Equity`
    * Code: `4000`, Name: `Consulting Revenue`, Type: `Revenue`
    * Code: `5000`, Name: `Sales Discounts & Returns`, Type: `Revenue` (Contra-Revenue - reduces total revenue)
    * Code: `6100`, Name: `Depreciation Expense`, Type: `Expense`
* **Expected Outcome:** All specified accounts are created and correctly categorized within the Chart of Accounts. Technically, verify each account's creation and that the code is unique per company.

---

## Step 3: Establishing Essential Journals and System Defaults (Crucial New Step!)

*Goal: Configure the specialized journals and system-wide default account mappings necessary for automated transaction processing. This directly addresses the "no journal created" issue for manual entries and prepares the system for automated ones.*

* **Accounting Rationale:** While the Chart of Accounts defines what is tracked, Journals define where transactions are initially recorded and how they are categorized for audit and automation. Modern systems like Odoo rely on designated journals (e.g., a 'Bank' journal for all bank movements) and pre-set default accounts to streamline transaction processing. Without these, manual journal entries fail (as you observed), and automated processes (like posting invoices) lack the necessary "pointers" to create their underlying journal entries.

### • 3.1. Create Essential Journals:
* **Resource:** `JournalResource`
* **Action:** Click "New Journal" for each type.
* **Enter Data** (Link `company_id` to Jmeryar ERP):
    * `name`: Bank, `type`: Bank, `short_code`: BNK (or similar, ensuring unique `short_code` per company).
        > **Guidance for Junior Developers:** This "Bank" journal is specifically designed to handle all money coming into and out of Soran's bank account. When you make a manual journal entry that affects the actual bank cash, you'll select this journal.
    * `name`: Sales, `type`: Sale, `short_code`: INV (for customer invoices).
    * `name`: Purchases, `type`: Purchase, `short_code`: BILL (for vendor bills).
    * `name`: Miscellaneous, `type`: Miscellaneous, `short_code`: MISC (for general adjustments and opening entries).
* **Expected Outcome:** The core journals are created and ready for use. Technically, verify creation and `short_code` uniqueness.

### • 3.2. Configure System Default Accounts and Journals (Crucial for Automation):
> **Guidance for Junior Developers:** In Laravel, you'd typically have a configuration file (e.g., `config/accounting.php`) or a database settings table where you map general ledger accounts and journals to their specific roles for automated processes. This tells the system, "When an invoice is posted, use THIS Accounts Receivable account and THIS Sales Journal."
* **Action:** Ensure your application's configuration or settings database is updated with the IDs of the accounts and journals created in Step 2 and 3.1.
* **Example Laravel Test Setup (demonstrates the concept):**
* **Expected Outcome:** The system is now fully aware of which accounts and journals to use for various automated and manual financial operations.

---

## Step 4: Capital Injection (Owner's Investment)

*Story: Soran invests 15,000,000 IQD of his personal savings into the company's bank account to get Jmeryar ERP started.*

*Goal: Record the initial capital injection as a manual journal entry. This demonstrates the core Double-Entry Bookkeeping principle and its impact on the accounting equation (Assets = Liabilities + Equity).*

* **Accounting Rationale & Pre-computation:**
    * The company's bank account (an **Asset**) is increasing. Assets increase with a **Debit**.
    * The owner's investment (**Owner's Equity**) is increasing. Equity increases with a **Credit**.
    * Therefore: **Debit Bank**, **Credit Owner's Equity**.
    * The equation remains balanced: **Assets (+) = Equity (+)**.
* **Resource:** `JournalEntryResource`.
* **Action:** Click "New Journal Entry".
* **Enter Data (Header):**
    * `journal_id`: Select "Bank" (This is why Step 3 was critical!).
    * `entry_date`: (Today's Date).
    * `reference`: Initial Capital Investment.
    * `description`: Soran's personal funds transferred to the Jmeryar ERP bank account (A more descriptive header).
    * `created_by_user_id`: Select Soran's user ID (Crucial for auditability).
    * `status`: Initially Draft (editable, no accounting impact).
* **Action (Lines):** Add two `JournalEntryLine` items:
    * **Line 1 (Debit):**
        * `account_id`: 1010 - Bank
        * `debit`: 15000000
        * `credit`: 0
        * `description`: Capital injection into company bank account
    * **Line 2 (Credit):**
        * `account_id`: 3000 - Owner's Equity
        * `debit`: 0
        * `credit`: 15000000
        * `description`: Owner's personal investment
* **Action:** Post the Journal Entry.
    > **Guidance for Junior Developers:** The act of "posting" a journal entry is critical. Before posting, it might be in a "Draft" state where it can be modified. Once posted, it becomes immutable.
* **Expected Outcome:**
    * The **Bank** account (Asset) increases by 15,000,000 IQD.
    * The **Owner's Equity** account increases by 15,000,000 IQD.
    * The accounting equation **Assets = Liabilities + Equity** is in balance, confirming the core Double-Entry principle.
    * **Technically, verify:**
        * The `journal_entry` record has `is_posted = true`, `total_debit = 15000000`, `total_credit = 15000000`.
        * A `hash` and `previous_hash` are generated, forming the immutable audit chain.
        * The `created_by_user_id` and `created_at` are correctly recorded and immutable.
        * Attempts to directly `UPDATE` or `DELETE` this posted `JournalEntry` are prevented at the application level (e.g., throwing `UpdateNotAllowedException` or `DeletionNotAllowedException`).

---

## Step 5: Purchasing a Fixed Asset

*Story: Soran needs equipment. He buys a high-end laptop for 3,000,000 IQD on credit from "Paykar Tech Supplies".*

*Goal: Record the acquisition of a fixed asset and the corresponding liability. This tests the Accrual Basis of accounting (recognizing expenses/assets when incurred, not when paid) and Capitalization.*

### • 5.1. Create the Vendor ("Paykar Tech Supplies"):
* **Accounting Rationale:** Accurate tracking of who owes whom is vital. This establishes "Paykar Tech Supplies" as a Vendor.
* **Resource:** `PartnerResource`.
* **Action:** Click "New Partner".
* **Enter Data:**
    * `name`: Paykar Tech Supplies
    * `type`: Vendor
* **Expected Outcome:** The vendor is created. Technically, verify its creation and that it is soft-deletable (not physically deleted) if it becomes inactive later, preserving historical links.

### • 5.2. Record the Vendor Bill:
* **Accounting Rationale & Pre-computation:**
    * A laptop is a **Fixed Asset** (provides long-term benefit), not a regular expense. Assets increase with a **Debit**.
    * Buying on credit creates a **Liability** (Accounts Payable). Liabilities increase with a **Credit**.
    * Therefore: **Debit IT Equipment**, **Credit Accounts Payable**.
    * The equation remains balanced: **Assets (+) = Liabilities (+)**.
* **Resource:** `VendorBillResource`.
* **Action:** Click "New Vendor Bill".
* **Enter Data (Header):**
    * `vendor_id`: Select Paykar Tech Supplies.
    * `bill_date`: (Today's Date).
    * `accounting_date`: (Today's Date - often same as bill date for immediate recognition).
    * `due_date`: (e.g., 30 days from today).
    * `bill_reference`: KE-LAPTOP-001 (The vendor's reference number).
    * `status`: Initially `Draft` (editable, no accounting impact).
* **Action (Lines):** Add one `VendorBillLine`:
    * `description`: High-End Laptop for Business Use.
    * `quantity`: 1.
    * `unit_price`: 3000000.
    * `expense_account_id`: 1500 - IT Equipment (Crucially, we debit the asset account directly because this is a capital expenditure, not an operating expense).
* **Action:** Post the Vendor Bill.
    > **Guidance for Junior Developers:** Posting this bill automatically generates its corresponding journal entry. The `vendor_bill` itself, once posted, should become immutable.
* **Expected Outcome:**
    * A journal entry is automatically created and linked to the Vendor Bill.
    * **Debit:** `1500` - IT Equipment for 3,000,000 IQD.
    * **Credit:** `2100` - Accounts Payable for 3,000,000 IQD.
    * This tests the Accrual Basis of accounting (recognizing the liability when incurred) and Capitalization of an asset.
    * **Technically, verify:**
        * The `vendor_bill` status changes to `Posted`, and `journal_entry_id` is populated.
        * A new `journal_entry` is created with balanced debits/credits, `is_posted = true`, and correct `hash`/`previous_hash`.
        * The `journal_entry` has `source_type = 'App\Models\VendorBill'` and `source_id` pointing to the bill's ID.
        * Attempts to modify/delete the posted `VendorBill` are prevented, or any "reset to draft" is logged.

---

## Step 6: Providing a Service & Invoicing

*Story: Soran provides IT setup services to his first client, "Hawre Trading Group," and invoices them for 5,000,000 IQD.*

*Goal: Record revenue from services provided. This tests the Revenue Recognition Principle, where revenue is recognized when earned, not when cash is received.*

### • 6.1. Create the Customer ("Hawre Trading Group"):
* **Accounting Rationale:** Similar to vendors, customers need to be tracked.
* **Resource:** `PartnerResource`.
* **Action:** Click "New Partner".
* **Enter Data:**
    * `name`: Hawre Trading Group
    * `type`: Customer
* **Expected Outcome:** The customer is created and is soft-deletable.

### • 6.2. Create the Customer Invoice:
* **Accounting Rationale & Pre-computation:**
    * Services provided but not yet paid for create a **Receivable** (an Asset). Assets increase with a **Debit**.
    * Services rendered generate **Revenue** (an Income account, increasing Equity). Revenue increases with a **Credit**.
    * Therefore: **Debit Accounts Receivable**, **Credit Consulting Revenue**.
    * The equation remains balanced: **Assets (+) = Equity (+)**.
* **Resource:** `InvoiceResource`.
* **Action:** Click "New Invoice".
* **Enter Data (Header):**
    * `customer_id`: Hawre Trading Group.
    * `invoice_date`: (Today's Date).
    * `due_date`: (e.g., 15 days from today).
    * `status`: Initially `Draft` (editable, no accounting impact).
    * `invoice_number`: This field should be empty at the draft stage. It is assigned automatically upon posting.
* **Action (Lines):** Add one `InvoiceLine`:
    * `description`: On-site IT Infrastructure Setup.
    * `quantity`: 1.
    * `unit_price`: 5000000.
    * `income_account_id`: 4000 - Consulting Revenue.
* **Action:** Post the Invoice.
    > **Guidance for Junior Developers:** Posting the invoice triggers critical actions: a unique sequential invoice number is assigned, and a journal entry is automatically generated. The invoice itself, now "Posted," becomes immutable.
* **Expected Outcome:**
    * A journal entry is automatically created and linked to the Invoice.
    * **Debit:** `1200` - Accounts Receivable for 5,000,000 IQD.
    * **Credit:** `4000` - Consulting Revenue for 5,000,000 IQD.
    * This tests the Revenue Recognition Principle (revenue is earned and recorded when the service is provided).
    * **Technically, verify:**
        * The invoice status changes to `Posted`, `invoice_number` is assigned, and `journal_entry_id` is populated.
        * A new `journal_entry` is created with balanced debits/credits, `is_posted = true`, and correct `hash`/`previous_hash`.
        * The `journal_entry` has `source_type = 'App\Models\Invoice'` and `source_id` pointing to the invoice's ID.
        * Attempts to modify/delete the posted `Invoice` are prevented, or any "reset to draft" is logged.

---

## Step 7: Receiving Payment from Customer

*Story: "Hawre Trading Group" pays their invoice in full.*

*Goal: Record the cash receipt and clear the accounts receivable. This tests cash management and reconciliation.*

* **Accounting Rationale & Pre-computation:**
    * The company's bank account (an **Asset**) is increasing. Assets increase with a **Debit**.
    * The amount owed by the customer (**Accounts Receivable**, an Asset) is decreasing. Assets decrease with a **Credit**.
    * Therefore: **Debit Bank**, **Credit Accounts Receivable**.
    * The equation remains balanced: **Assets (+) = Assets (-)**.
* **Resource:** `PaymentResource`.
* **Action:** Click "New Payment".
* **Enter Data:**
    * `payment_type`: Inbound (Cash receipt).
    * `paid_to_from_partner_id`: Hawre Trading Group.
    * `amount`: 5000000.
    * `payment_date`: (Today's Date).
    * `journal_id`: Bank (Link to the Bank Journal for this transaction).
* **Action:** In the payment record, link it to the invoice from Step 6 and apply the full amount.
* **Action:** Post the Payment.
    > **Guidance for Junior Developers:** Payments, once confirmed, are financial transactions and, therefore, immutable.
* **Expected Outcome:**
    * A journal entry is automatically created and linked to the Payment.
    * **Debit:** `1010` - Bank for 5,000,000 IQD.
    * **Credit:** `1200` - Accounts Receivable for 5,000,000 IQD.
    * The invoice status should now be "Paid".
    * This tests cash management and the reduction of Accounts Receivable.
    * **Technically, verify:**
        * The payment status changes to `Confirmed` and `journal_entry_id` is populated.
        * A new `journal_entry` is created with balanced debits/credits, `is_posted = true`, and correct `hash`/`previous_hash`.
        * The payment is linked to the invoice via `payment_document_links`.
        * Attempts to modify/delete the confirmed `Payment` are prevented.

---

## Step 8: Paying a Vendor

*Story: Soran pays "Paykar Tech Supplies" the 3,000,000 IQD owed for the laptop.*

*Goal: Record the cash disbursement and clear the accounts payable.*

* **Accounting Rationale & Pre-computation:**
    * The company's bank account (an **Asset**) is decreasing. Assets decrease with a **Credit**.
    * The amount owed to the vendor (**Accounts Payable**, a Liability) is decreasing. Liabilities decrease with a **Debit**.
    * Therefore: **Debit Accounts Payable**, **Credit Bank**.
    * The equation remains balanced: **Liabilities (-) = Assets (-)**.
* **Resource:** `PaymentResource`.
* **Action:** Click "New Payment".
* **Enter Data:**
    * `payment_type`: Outbound (Cash disbursement).
    * `paid_to_from_partner_id`: Paykar Tech Supplies.
    * `amount`: 3000000.
    * `payment_date`: (Today's Date).
    * `journal_id`: Bank.
* **Action:** Link the payment to the vendor bill from Step 5.
* **Action:** Post the Payment.
* **Expected Outcome:**
    * A journal entry is automatically created and linked to the Payment.
    * **Debit:** `2100` - Accounts Payable for 3,000,000 IQD.
    * **Credit:** `1010` - Bank for 3,000,000 IQD.
    * The vendor bill status should now be "Paid".
    * **Technically, verify:** Similar immutability and linking as Step 7.

---

## Step 9: Handling a Correction (Credit Note)

*Story: As a goodwill gesture, Soran gives "Hawre Trading Group" a 500,000 IQD refund on their paid invoice from Step 6.*

*Goal: Correctly reduce revenue and issue a refund. This is a crucial test of the immutability principle: we do NOT edit or delete the original invoice; instead, we create a contra-entry (a Credit Note).*

* **Accounting Rationale & Pre-computation:**
    * Since revenue is being reduced, and the customer will receive cash (or a future credit), the net impact is a reduction in income and a reduction in what the customer "owes" us (or an increase in what we owe them).
    * **Sales Discounts & Returns** (a Contra-Revenue account) is increasing (acting like an Expense, reducing Net Income). Expenses increase with a **Debit**.
    * **Accounts Receivable** (an Asset, representing what the customer owes) is decreasing (or creating a credit balance that we owe them). Assets decrease with a **Credit**.
    * Therefore: **Debit Sales Discounts & Returns**, **Credit Accounts Receivable**.
    * The equation remains balanced: **Equity (-) = Assets (-)**.
* **Resource:** `AdjustmentDocumentResource` (or `CreditNoteResource` if separate).
* **Action:** Click "New Adjustment Document".
* **Enter Data (Header):**
    * `type`: Credit Note.
    * `original_invoice_id`: Select the invoice from Step 6 (Crucial link for audit trail).
    * `date`: (Today's Date).
    * `reference_number`: (System-generated sequential number for Credit Notes, after posting).
    * `reason`: Goodwill discount for new client.
    * `total_amount`: 500000 (This represents the positive amount of the credit note).
    * `status`: Initially `Draft`.
* **Action (Lines):** Add one line item (e.g., `AdjustmentDocumentLine` or similar):
    * `description`: Refund for IT Setup Services
    * `quantity`: 1
    * `unit_price`: 500000
    * `income_account_id`: 5000 - Sales Discounts & Returns (Points to the contra-revenue account).
* **Action:** Post the Credit Note.
* **Expected Outcome:**
    * A journal entry is automatically created and linked to the Credit Note.
    * **Debit:** `5000` - Sales Discounts & Returns for 500,000 IQD.
    * **Credit:** `1200` - Accounts Receivable for 500,000 IQD.
    * The customer now has a credit of 500,000 IQD, which can be refunded via an outbound payment or applied to a future invoice. This tests the proper handling of corrections via contra-entries, preserving the integrity of the original transaction.
    * **Technically, verify:**
        * The `adjustment_document` status changes to `Posted` and `journal_entry_id` is populated.
        * A new `journal_entry` is created with balanced debits/credits, `is_posted = true`, and correct `hash`/`previous_hash`.
        * The `journal_entry` has `source_type` pointing to the `AdjustmentDocument`.
        * The `original_invoice_id` is correctly linked.
        * Attempts to modify/delete the posted `AdjustmentDocument` are prevented.

---

## Additional Post-Scenario Tests & Considerations for Robustness

Beyond these core transactions, a truly robust system, aiming to compete with Odoo, should also rigorously test the following crucial principles:

### Lock Date Enforcement:
* **Test:** Attempt to create or modify any financial transaction (`Journal Entries`, `Invoices`, `Vendor Bills`) with an `entry_date` falling on or before a defined `locked_until` date.
* **Expected Outcome:** The system should strictly prevent these actions, throwing a `PeriodIsLockedException` or similar custom exception. This ensures financial periods, once closed, cannot be altered, which is vital for compliance and audits.

### Comprehensive Audit Logging:
* **Test:** Verify that all significant data changes, especially transitions for financial data (e.g., `Draft` to `Posted`, `Reset to Draft`), are logged in the `audit_logs` table. Ensure the `user_id`, `event_type`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, and `created_at` fields are accurately captured.
* **Expected Outcome:** A clear, unalterable log of who did what, when, and to which financial record.

### Preventing Direct Deletion/Modification of Posted Entries (Explicit Test):
* **Test:** After any invoice, vendor bill, payment, or adjustment document is `Posted`/`Confirmed`, programmatically attempt to directly `DELETE` or `UPDATE` its underlying `journal_entry` record or the document itself (if it's in a `Posted` state).
* **Expected Outcome:** These attempts should be blocked by your application's business logic, throwing `DeletionNotAllowedException` or `UpdateNotAllowedException`. This reinforces the non-negotiable principle of immutability.

### Cryptographic Hashing Verification:
* **Test:** For every `JournalEntry` that reaches a `is_posted = true` state, verify that its `hash` field is populated with a valid SHA-256 hash (64 characters long) and that `previous_hash` correctly links to the preceding entry's hash, forming the chain.
* **Expected Outcome:** An unbroken and verifiable cryptographic chain of financial transactions.

By systematically addressing each of these areas, from the most basic data entities to complex transactional and system-level concerns, your "Jmeryar ERP" application will achieve the highest level of data integrity, compliance, and reliability, effectively competing with established platforms like Odoo in the Iraqi market. This rigorous testing approach will ensure your application's financial records are as immutable and auditable as if they were etched in stone.