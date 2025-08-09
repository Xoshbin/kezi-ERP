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

## Step 10: Bank Statement Processing, Write-Off, and Reconciliation

*Goal: Process a bank statement, handle minor discrepancies via a write-off, and reconcile existing payments. This tests your system's ability to align internal cash records with actual bank movements, ensuring financial accuracy and maintaining a perfect audit trail.*

* **Accounting Rationale:** Bank reconciliation is the process of matching transactions recorded in your company's internal books with those appearing on your bank statements. Its primary purpose is to ensure that your cash and bank accounts in the General Ledger accurately reflect the actual cash held by the business.

### • 10.1. Create a Bank Statement Record:
* **Resource:** `BankStatementResource`.
* **Action:** Click "New Bank Statement".
* **Enter Data (Header):**
    * `company_id`: Select "Jmeryar ERP" (from Step 1.2).
    * `starting_balance`: Calculate this based on your system's bank balance before the transactions on this statement. For example, after Step 8's payment, your bank balance would be 15,000,000 (initial) + 5,000,000 (Hawre payment) - 3,000,000 (Paykar payment) = 17,000,000 IQD. So, `starting_balance`: 17000000 IQD.
    * `ending_balance`: (e.g., 17000000 (start) + 5000000 (Hawre) - 3000000 (Paykar) - 500 (fee) = 18999500 IQD).
* **Action (Lines):** Add three `BankStatementLine` items:
    * **Line 1 (Hawre Payment):**
        * `date`: (Today's Date, matching payment date from Step 7).
        * `description`: "Hawre Trading Group Payment for Invoice INV-001".
        * `amount`: 5000000 (representing the incoming payment from Step 7).
        * `partner_id`: Select "Hawre Trading Group" (from Step 6.1).
    * **Line 2 (Paykar Payment):**
        * `date`: (Today's Date, matching payment date from Step 8).
        * `description`: "Payment to Paykar Tech Supplies for Laptop Bill BILL-001".
        * `amount`: -3000000 (representing the outgoing payment to Paykar from Step 8).
        * `partner_id`: Select "Paykar Tech Supplies" (from Step 5.1).
    * **Line 3 (Bank Fee - for Write-Off):**
        * `date`: (Today's Date).
        * `description`: "Monthly Bank Service Fee".
        * `amount`: -500 (representing a 500 IQD withdrawal/fee, not yet recorded internally).
        * `partner_id`: (Leave blank, or select the Bank as a partner if applicable).
* **Expected Outcome:** The bank statement, including both recorded payments and the unrecorded bank fee, is successfully entered into the system.

### • 10.2. Initiate Bank Reconciliation:
* **Resource:** `BankStatementResource`.
* **Action:** From the list of bank statements, click the "Reconcile" action button (icon 'heroicon-o-scale') next to the newly created bank statement.
* **Filament Integration:** This action will lead to a Livewire component designed for interactive reconciliation, displaying the `BankStatementLine` records and suggesting matches with existing `JournalEntry` records (such as those from Payments in Step 7 and 8).

### • 10.3. Perform a Write-Off for the Unmatched Bank Fee:
* **Context:** On the reconciliation interface, identify the "Monthly Bank Service Fee" line (amount -500 IQD) as an unmatched record.
* **Action:** Click the "Write Off" or "Adjust Difference" button associated with this specific `BankStatementLine`. This should trigger a Filament modal.
* **Enter Data (Modal):**
    * The amount of -500 IQD is pre-filled.
    * Account: Select 6100 - Depreciation Expense (or 'Bank Charges Expense' if a more specific account was created).
    * Description: "Write-off for monthly bank service charge".
* **Backend Logic (Developer Detail):**
    * Upon confirmation, your service layer (e.g., `App\Services\Accounting\ReconciliationService` or `CreateJournalEntryForStatementLineAction`) executes the write-off logic.
    * It creates a new `JournalEntry` and associated `JournalEntryLine` records as a contra-entry for the -500 IQD difference.
        * **Debit:** 6100 - Depreciation Expense (or Bank Charges Expense) account for 500 IQD.
        * **Credit:** 1010 - Bank account for 500 IQD.
    * The `total_debit` and `total_credit` for this new `JournalEntry` must be equal.
    * The `JournalEntry`'s `is_posted` flag is set to true, and it is immediately cryptographically hashed (`hash` and `previous_hash` generated) to ensure immutability and link it to the audit chain.
    * The `JournalEntry` is explicitly linked back to the `BankStatementLine` via `source_type` and `source_id`.
    * All monetary values are handled using Brick\Money objects and your custom MoneyCast, ensuring precise financial calculations, especially considering IQD's specific decimal places.
* **Expected Outcome:** The "Monthly Bank Service Fee" `BankStatementLine`'s status is updated to reconciled. The bank account balance in your General Ledger now accurately reflects the deduction for the bank fee.

### • 10.4. Reconcile Previous Payments:
* **Context:** On the reconciliation interface, the incoming payment from "Hawre Trading Group" (from Step 7, amount 5,000,000 IQD) and the outgoing payment to "Paykar Tech Supplies" (from Step 8, amount 3,000,000 IQD) should appear as `BankStatementLine` records (created in Step 10.1) that can be matched with their corresponding `JournalEntry` records (which were generated by the Payment models in Steps 7 and 8).
* **Action:** Match these `BankStatementLine` records with their corresponding `JournalEntry` records. This can be manual selection or automated suggestions from the system.
* **Expected Outcome:** The respective `BankStatementLine` records for these payments are marked as reconciled. The Payment records themselves might also update their status (e.g., from 'Confirmed' to 'Reconciled' if your system tracks this granularly).

---
## Step 11: Inventory Management – Purchasing and Selling Physical Products

*Goal: Implement and test perpetual inventory valuation, including cost layer management and automatic generation of inventory-related accounting entries (Inventory Asset, Cost of Goods Sold, Stock Input).*

* **Accounting Rationale:** Perpetual inventory valuation continuously updates inventory records with quantities and values as transactions occur. Methods like Average Cost (AVCO), FIFO, and LIFO determine the cost of goods sold and the remaining inventory value, directly impacting financial statements. Anglo-Saxon accounting often uses a "Stock Input" interim account when goods are received before the vendor bill is processed, ensuring proper accrual.
* **Technical Implementation:** Extend the `Product` model, introduce cost layer and stock move valuation models, and encapsulate logic in a dedicated service layer that interacts with the immutable Journal Entry system.

### • 11.1. Create Inventory Accounts
* **Resource:** `AccountResource`
* **Action:** Click "New Account" for each:
    * Code: `1100`, Name: `Inventory Asset`, Type: `Asset`
    * Code: `6000`, Name: `Cost of Goods Sold`, Type: `Expense`
    * Code: `2150`, Name: `Stock Interim (Received)`, Type: `Liability`
    * Code: `6200`, Name: `Inventory Adjustment Expense`, Type: `Expense`
* **Expected Outcome:** Accounts are available in the Chart of Accounts.

### • 11.2. Create a Storable Product with Inventory Settings
* **Story:** Soran adds "IT Workstation" as a product for sale.
* **Resource:** `ProductResource`
* **Action:** Click "New Product".
* **Enter Data:**
    * `name`: IT Workstation
    * `sku`: ITWS001
    * `unit_price`: 10,000,000 (selling price)
    * `type`: storable_product
    * `inventory_valuation_method`: avco
    * `default_inventory_account_id`: 1100 - Inventory Asset
    * `default_cogs_account_id`: 6000 - Cost of Goods Sold
    * `default_stock_input_account_id`: 2150 - Stock Interim (Received)
* **Expected Outcome:** Product is configured for perpetual inventory tracking.

### • 11.3. Purchase Inventory (Vendor Bill & Incoming Stock)
* **Story:** Buy 5 "IT Workstations" from "Global Tech Distributors" at 7,000,000 IQD each.
* **Resource:** `VendorBillResource`
* **Action:**
    1. Create Partner: "Global Tech Distributors" (Vendor)
    2. Create Vendor Bill:
        * `vendor_id`: Global Tech Distributors
        * `bill_date`: (Today's Date)
        * `bill_reference`: GBL-WS-001
    3. Add VendorBillLine:
        * `description`: IT Workstation
        * `quantity`: 5
        * `unit_price`: 7,000,000
        * `expense_account_id`: 1100 - Inventory Asset
    4. Post the Vendor Bill.
* **Expected Outcome:**
    * Inventory increases by 5 units.
    * Average cost updates to 7,000,000 IQD/unit.
    * Journal Entry: Debit 1100 - Inventory Asset (35,000,000 IQD), Credit 2150 - Stock Interim (Received) (35,000,000 IQD).
    * Entry is hashed and linked to the Vendor Bill.

### • 11.4. Sell Inventory (Customer Invoice & Cost of Goods Sold)
* **Story:** Sell 2 "IT Workstations" to "Zanyari Solutions" at 10,000,000 IQD each.
* **Resource:** `InvoiceResource`
* **Action:**
    1. Create Partner: "Zanyari Solutions" (Customer)
    2. Create Invoice:
        * `customer_id`: Zanyari Solutions
        * `invoice_date`: (Today's Date)
    3. Add InvoiceLine:
        * `description`: IT Workstation
        * `quantity`: 2
        * `unit_price`: 10,000,000
        * `income_account_id`: 4000 - Consulting Revenue
    4. Post the Invoice.
* **Expected Outcome:**
    * Inventory decreases by 2 units.
    * COGS calculated at 7,000,000 IQD/unit (AVCO).
    * Journal Entries:
        * Sales: Debit 1200 - Accounts Receivable (20,000,000 IQD), Credit 4000 - Consulting Revenue (20,000,000 IQD).
        * COGS: Debit 6000 - Cost of Goods Sold (14,000,000 IQD), Credit 1100 - Inventory Asset (14,000,000 IQD).
    * Entries are hashed and linked to the Invoice.

### • 11.5. Inventory Adjustment (Write-Off Damaged Goods)
* **Story:** Write off 1 damaged "IT Workstation".
* **Resource:** `JournalEntryResource`
* **Action:** Create Journal Entry:
    * `journal_id`: Miscellaneous
    * `entry_date`: (Today's Date)
    * `description`: Write-off for 1 damaged IT Workstation
* **JournalEntryLines:**
    * Debit: 6200 - Inventory Adjustment Expense (7,000,000 IQD)
    * Credit: 1100 - Inventory Asset (7,000,000 IQD)
* **Action:** Post the Journal Entry.
* **Expected Outcome:** Inventory decreases by 1 unit; entry is hashed and immutable.

---

As AccounTech Pro, I will update your `scenario.md` with a detailed scenario for `Lock Date Enforcement`, seamlessly integrating it into the existing structure and aligning with your Laravel, Filament, and Pest stack. This is fundamental for maintaining the integrity and auditability of your financial records, a cornerstone of robust accounting inspired by Odoo's principles.

Here is the appended scenario for `Lock Date Enforcement`, placed chronologically after the `Inventory Management` step.
---

## Step 12: Lock Date Enforcement

*Goal: Ensure the system strictly enforces financial period lock dates, preventing creation or modification of transactions dated on or before a defined locked period. This is essential for compliance, auditability, and data integrity.*

* **Accounting Rationale:** Once a financial period is closed, it must remain immutable to comply with accounting standards (IFRS/GAAP) and legal regulations. Lock dates prevent retroactive changes that could compromise financial statements or tax compliance. Systems like Odoo implement multiple lock levels (e.g., "Lock Tax Return Date", "Lock Everything Date") for granular control.

* **Technical Implementation:** The application should use a dedicated validation service (e.g., `AccountingValidationService` or `LockDateService`) to check against the `lock_dates` table. Any attempt to bypass this validation should trigger a custom exception such as `PeriodIsLockedException`. Both UI actions (via Filament) and direct service calls must respect the lock.

### • 12.1. Set a Lock Date

* **Story:** Soran, as administrator, closes all records up to the end of the previous month for audit preparation.
* **Resource:** `LockDateResource` (or admin panel section).
* **Action:** Create a new Lock Date entry:
    * `company_id`: Jmeryar ERP
    * `lock_type`: everything_date
    * `locked_until`: e.g., '2025-07-31'
* **Expected Outcome:** The lock date is recorded in the `lock_dates` table for Jmeryar ERP.

### • 12.2. Attempt to Create a Journal Entry in a Locked Period

* **Story:** An accountant tries to back-date a miscellaneous expense within the locked period.
* **Resource:** `JournalEntryResource`
* **Action:** Attempt to create a new journal entry with `entry_date` set to a locked date (e.g., '2025-07-15').
* **Expected Outcome:** The system blocks the action and throws `PeriodIsLockedException`.

### • 12.3. Attempt to Modify a Posted Invoice in a Locked Period

* **Story:** Soran attempts to edit an invoice from the locked period to fix a typo.
* **Resource:** `InvoiceResource`
* **Action:** Attempt to update or reset to draft an invoice dated within the locked period.
* **Expected Outcome:** The system blocks the modification, throwing `PeriodIsLockedException` or `UpdateNotAllowedException`.

### • 12.4. Attempt to Modify a Posted Vendor Bill in a Locked Period

* **Story:** A user tries to adjust a posted vendor bill from the locked period.
* **Resource:** `VendorBillResource`
* **Action:** Attempt to modify a vendor bill dated within the locked period.
* **Expected Outcome:** The system blocks the modification, throwing `PeriodIsLockedException` or `UpdateNotAllowedException`.

---

## Additional Post-Scenario Tests & Considerations for Robustness

Beyond these core transactions, ensure your system rigorously tests the following principles:

### Comprehensive Audit Logging

* **Test:** Confirm all significant financial data changes (e.g., Draft to Posted, Reset to Draft) are logged in the `audit_logs` table, capturing `user_id`, `event_type`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, and `created_at`.
* **Expected Outcome:** An unalterable log of all critical actions.

### Preventing Direct Deletion/Modification of Posted Entries

* **Test:** Attempt to directly DELETE or UPDATE any posted invoice, vendor bill, payment, or adjustment document.
* **Expected Outcome:** The system blocks these actions, throwing `DeletionNotAllowedException` or `UpdateNotAllowedException`, enforcing immutability.

### Cryptographic Hashing Verification

* **Test:** For every posted `JournalEntry`, verify the `hash` field contains a valid SHA-256 hash and `previous_hash` links to the prior entry, forming a cryptographic chain.
* **Expected Outcome:** An unbroken, verifiable chain of financial transactions.

By addressing these areas, your "Jmeryar ERP" application will achieve robust data integrity, compliance, and reliability, matching the standards of leading platforms like Odoo.


This scenario covers inventory management from setup to purchasing, selling, and adjustments, maintaining strict immutability and auditability. Test these flows thoroughly, focusing on correct Journal Entry generation and hashing.

* **Expected Outcome (Overall Scenario):**
    * The bank statement is successfully processed, including manual input of the two payments and a minor bank fee.
    * The immaterial bank fee is correctly handled via a write-off, creating a new, offsetting journal entry that preserves the immutable audit trail.
    * Previously recorded customer and vendor payments are matched and reconciled with their corresponding bank statement lines.
    * The company's internal bank balance in the General Ledger now precisely matches the actual bank statement balance, demonstrating effective cash management and adherence to double-entry bookkeeping principles.

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
