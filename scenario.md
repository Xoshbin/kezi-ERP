Accounting System Test Case Scenario: "Jmeryar ERP" Launch
The Scenario: The Launch of "Jmeryar ERP"
Soran, an ambitious IT professional in Slemani, decides to start his own consulting firm, "Jmeryar ERP". His company will provide IT setup and support services to local businesses. We will follow his first few transactions to test the core functionality of your accounting application. The primary currency will be the Iraqi Dinar (IQD).
Step-by-Step Testing Guide
Step 1: Foundational Setup (Entity & Currency) Goal: Establish the company as a separate legal entity and set up the necessary currencies and users. This tests the basic configuration.
• Create the Currency:
    ◦ Resource: Navigate to CurrencyResource.
    ◦ Action: Click "New Currency".
    ◦ Enter Data:
        ▪ code: IQD
        ▪ name: Iraqi Dinar
        ▪ symbol: ع.د
        ▪ exchange_rate: 1.0 (as it's our base currency)
        ▪ is_active: true
    ◦ Expected Outcome: The IQD currency is now available for all transactions.
• Create the Company:
    ◦ Resource: Navigate to CompanyResource.
    ◦ Action: Click "New Company".
    ◦ Enter Data:
        ▪ name: Jmeryar ERP
        ▪ address: Slemani, Kurdistan Region, Iraq
        ▪ tax_id: (leave blank for now)
        ▪ currency_id: Select "Iraqi Dinar".
        ▪ fiscal_country: IQ
    ◦ Expected Outcome: The company "Jmeryar ERP" is created. All subsequent data will be linked to this entity, demonstrating the Entity Concept.
• Create a User:
    ◦ Resource: Navigate to UserResource.
    ◦ Action: Click "New User".
    ◦ Enter Data:
        ▪ company_id: Select "Jmeryar ERP".
        ▪ name: Soran
        ▪ email: soran@jmeryarerp.com
        ▪ password: (a secure password)
    ◦ Expected Outcome: Soran is created as a user who can perform transactions for the company.
Step 2: Building the Chart of Accounts Goal: Create the essential accounts for our transactions. This is the backbone of the general ledger.
• Resource: Navigate to AccountResource for all items in this step.
• Action: Click "New Account" for each account below.
    ◦ Code:1010, Name:Bank, Type:Asset
    ◦ Code:1200, Name:Accounts Receivable, Type:Asset
    ◦ Code:1500, Name:IT Equipment, Type:Asset (Fixed)
    ◦ Code:1501, Name:Accumulated Depreciation, Type:Asset (Contra)
    ◦ Code:2100, Name:Accounts Payable, Type:Liability
    ◦ Code:3000, Name:Owner's Equity, Type:Equity
    ◦ Code:4000, Name:Consulting Revenue, Type:Revenue
    ◦ Code:5000, Name:Sales Discounts & Returns, Type:Revenue (Contra)
    ◦ Code:6100, Name:Depreciation Expense, Type:Expense
Step 4: Capital Injection (Owner's Investment) Story: Soran invests 15,000,000 IQD of his personal savings into the company's bank account. Goal: Record the initial capital. This demonstrates a manual journal entry affecting Assets and Equity.
• Resource: Navigate to JournalEntryResource.
• Action: Click "New Journal Entry".
• Enter Data (Header):
    ◦ journal_id: Select "Bank".
    ◦ entry_date: (Today's Date)
    ◦ reference: Initial Capital Investment
• Action (Lines): Add two lines:
    ◦ Debit:
        ▪ account_id: 1010 - Bank
        ▪ debit: 15000000
        ▪ credit: 0
    ◦ Credit:
        ▪ account_id: 3000 - Owner's Equity
        ▪ debit: 0
        ▪ credit: 15000000
• Action: Post the Journal Entry.
• Expected Outcome:
    ◦ The Bank account (Asset) increases by 15,000,000 IQD.
    ◦ The Owner's Equity account increases by 15,000,000 IQD.
    ◦ The accounting equation Assets = Liabilities + Equity is in balance. This tests the core Double-Entry Bookkeeping principle.
Step 5: Purchasing a Fixed Asset Story: Soran buys a high-end laptop for 3,000,000 IQD on credit from "Paykar Tech Supplies".
• Create the Vendor:
    ◦ Resource: Navigate to PartnerResource.
    ◦ Action: Click "New Partner".
    ◦ Enter Data:
        ▪ name: Paykar Tech Supplies
        ▪ type: Vendor
• Record the Vendor Bill:
    ◦ Resource: Navigate to VendorBillResource.
    ◦ Action: Click "New Vendor Bill".
    ◦ Enter Data (Header):
        ▪ vendor_id: Paykar Tech Supplies
        ▪ bill_date: (Today's Date)
        ▪ due_date: (e.g., 30 days from today)
        ▪ bill_reference: KE-LAPTOP-001
    ◦ Action (Lines): Add one line:
        ▪ description: High-End Laptop for Business Use
        ▪ quantity: 1
        ▪ unit_price: 3000000
        ▪ expense_account_id: 1500 - IT Equipment (Note: We debit the asset account directly, not an expense account, because this is a capital expenditure).
• Action: Post the Vendor Bill.
• Expected Outcome:
    ◦ A journal entry is automatically created.
    ◦ Debit: 1500 - IT Equipment for 3,000,000 IQD.
    ◦ Credit: 2100 - Accounts Payable for 3,000,000 IQD.
    ◦ This tests the Accrual Basis of accounting (recognizing the liability when incurred, not when paid) and Capitalization of an asset.
Step 6: Providing a Service & Invoicing Story: Soran provides IT setup services to his first client, "Hawre Trading Group," and invoices them for 5,000,000 IQD.
• Create the Customer:
    ◦ Resource: Navigate to PartnerResource.
    ◦ Action: Click "New Partner".
    ◦ Enter Data:
        ▪ name: Hawre Trading Group
        ▪ type: Customer
• Create the Invoice:
    ◦ Resource: Navigate to InvoiceResource.
    ◦ Action: Click "New Invoice".
    ◦ Enter Data (Header):
        ▪ customer_id: Hawre Trading Group
        ▪ invoice_date: (Today's Date)
        ▪ due_date: (e.g., 15 days from today)
    ◦ Action (Lines): Add one line:
        ▪ description: On-site IT Infrastructure Setup
        ▪ quantity: 1
        ▪ unit_price: 5000000
        ▪ income_account_id: 4000 - Consulting Revenue
• Action: Post the Invoice.
• Expected Outcome:
    ◦ A journal entry is automatically created.
    ◦ Debit: 1200 - Accounts Receivable for 5,000,000 IQD.
    ◦ Credit: 4000 - Consulting Revenue for 5,000,000 IQD.
    ◦ This tests the Revenue Recognition Principle. Revenue is earned and recorded when the service is provided, not when cash is received.
Step 7: Receiving Payment from Customer Story: "Hawre Trading Group" pays their invoice in full. Goal: Record the cash receipt and clear the receivable.
• Resource: Navigate to PaymentResource.
• Action: Click "New Payment".
• Enter Data:
    ◦ payment_type: Inbound
    ◦ paid_to_from_partner_id: Hawre Trading Group
    ◦ amount: 5000000
    ◦ payment_date: (Today's Date)
    ◦ journal_id: Bank
• Action: In the payment record, link it to the invoice from Step 6 and apply the full amount.
• Action: Post the Payment.
• Expected Outcome:
    ◦ A journal entry is automatically created.
    ◦ Debit: 1010 - Bank for 5,000,000 IQD.
    ◦ Credit: 1200 - Accounts Receivable for 5,000,000 IQD.
    ◦ The invoice status should now be "Paid". This tests cash management and reconciliation.
Step 8: Paying a Vendor Story: Soran pays "Paykar Tech Supplies" for the laptop. Goal: Record the cash disbursement and clear the payable.
• Resource: Navigate to PaymentResource.
• Action: Click "New Payment".
• Enter Data:
    ◦ payment_type: Outbound
    ◦ paid_to_from_partner_id: Paykar Tech Supplies
    ◦ amount: 3000000
    ◦ payment_date: (Today's Date)
    ◦ journal_id: Bank
• Action: Link the payment to the vendor bill from Step 5.
• Action: Post the Payment.
• Expected Outcome:
    ◦ A journal entry is automatically created.
    ◦ Debit: 2100 - Accounts Payable for 3,000,000 IQD.
    ◦ Credit: 1010 - Bank for 3,000,000 IQD.
    ◦ The vendor bill status should now be "Paid".
Step 9: Handling a Correction (Credit Note) Story: As a goodwill gesture, Soran gives "Hawre Trading Group" a 500,000 IQD refund on their paid invoice. Goal: Correctly reduce revenue and issue a refund, demonstrating the immutability principle (we don't edit the original invoice).
• Resource: Navigate to AdjustmentDocumentResource.
• Action: Click "New Adjustment Document".
• Enter Data:
    ◦ type: Credit Note
    ◦ original_invoice_id: Select the invoice from Step 6.
    ◦ date: (Today's Date)
    ◦ reason: Goodwill discount for new client
    ◦ total_amount: 500000 (This should be a positive number).
• (You would add a line item here pointing to the 5000 - Sales Discounts & Returns account).
• Action: Post the Credit Note.
• Expected Outcome:
    ◦ A journal entry is automatically created.
    ◦ Debit: 5000 - Sales Discounts & Returns for 500,000 IQD.
    ◦ Credit: 1200 - Accounts Receivable for 500,000 IQD.
    ◦ The customer now has a credit of 500,000 IQD, which can be refunded via an outbound payment or applied to a future invoice. This tests the proper handling of corrections via contra-entries.
By completing these steps, you will have tested the entire lifecycle of core accounting transactions, validating that your application correctly implements fundamental principles in a real-world context. You can then check the JournalEntryResource to see the immutable, chained list of all financial events. This approach aligns perfectly with best practices for accounting data integrity, where financial transactions, once "posted" or "finalized," cannot be deleted or directly altered, and all corrections are mandated via contra-entries, forming a blockchain-like audit chain using cryptographic hashing. Your system’s design, especially its reliance on manual data entry, necessitates robust real-time validation and intuitive workflows for these compliant corrections