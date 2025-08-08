Immutable Accounting Database Architecture and Principles
To lay a perfect, detailed database structure as the foundation for your comprehensive, headless accounting application, drawing on the principles of immutability, auditability, and double-entry bookkeeping that underpin systems like Odoo, here is a breakdown of the essential tables and their key columns, excluding default Laravel migrations already in your project and specifically avoiding external banking integrations [1-7].
The core philosophy of accounting mandates that financial transactions, once officially posted, are immutable [1-3, 8, 9]. This means direct deletion or alteration of these records is prohibited [2, 9]. Corrections must always be handled by creating new, offsetting entries (contra-entries), maintaining a complete and chronological audit trail [3, 8-10]. Your application's reliance on manual data entry further necessitates robust integrity controls [10, 11].
Core Accounting Tables (Emphasis on Immutability & Auditability)
1. companies Table
    ◦ id: Primary Key (PK), auto-increment.
    ◦ name: String, the legal name of the company.
    ◦ address: Text, the company's physical address.
    ◦ tax_id: String, e.g., VAT number or Iraqi tax identification.
    ◦ currency_id: Foreign Key (FK) to currencies.id, the default operating currency for the company [12].
    ◦ fiscal_country: String, (e.g., 'IQ' for Iraq), crucial for localization and tax compliance [13].
    ◦ parent_company_id: Nullable FK to companies.id, to support multi-branch/multi-company structures [14].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
2. users Table (Your existing Laravel users table, ensuring relevant fields for auditing)
    ◦ id: PK, auto-increment.
    ◦ name: String, user's full name.
    ◦ email: String, unique.
    ◦ password: String, hashed securely using Laravel's Hash facade [15, 16].
    ◦ company_id: FK to companies.id, for users within a specific company in a multi-company setup.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
3. audit_logs Table
    ◦ id: PK, auto-increment.
    ◦ user_id: FK to users.id, the user who initiated the action [3, 8, 10].
    ◦ event_type: String (e.g., 'record_created', 'record_updated', 'status_changed', 'login', 'setting_changed').
    ◦ auditable_type: String, polymorphic relation for the model affected (e.g., 'App\Models\Invoice', 'App\Models\Account').
    ◦ auditable_id: Integer, polymorphic relation for the ID of the affected model.
    ◦ old_values: JSON/Text, stores the previous state of relevant attributes [3, 8].
    ◦ new_values: JSON/Text, stores the new state of relevant attributes [3, 8].
    ◦ description: Text, a human-readable summary of the action.
    ◦ ip_address: String, IP address of the user (for security).
    ◦ user_agent: Text, user's browser/client information.
    ◦ created_at: Timestamp, records when the audit event occurred [3, 8].
4. lock_dates Table
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ lock_type: String (e.g., 'tax_return_date', 'everything_date') [3, 8, 10].
    ◦ locked_until: Date/Timestamp, the date up to which records are locked [3, 8, 10].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ Unique constraint on company_id and lock_type to prevent duplicate lock entries.
5. currencies Table
    ◦ id: PK, auto-increment.
    ◦ code: String (e.g., 'IQD', 'USD', 'EUR'), unique.
    ◦ name: String (e.g., 'Iraqi Dinar', 'United States Dollar').
    ◦ symbol: String (e.g., 'د.ع', '$').
    ◦ exchange_rate: Decimal, the rate relative to a chosen base currency (e.g., USD = 1.0).
    ◦ is_active: Boolean, default true.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
6. accounts Table (Chart of Accounts)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ code: String, unique within a company's chart of accounts [17].
    ◦ name: String, the name of the account.
    ◦ type: String (e.g., 'Asset', 'Liability', 'Equity', 'Income', 'Expense', 'Bank', 'Cash', 'Receivable', 'Payable') [17, 18]. This categorizes accounts.
    ◦ is_deprecated: Boolean, default false. Accounts with transactions cannot be deleted; they can only be marked as deprecated to prevent new use [8, 9, 19].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ Unique constraint on company_id and code.
7. journals Table
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ name: String (e.g., 'Sales Journal', 'Bank Account ABC').
    ◦ type: String (e.g., 'Sale', 'Purchase', 'Bank', 'Cash', 'Miscellaneous') [20-22].
    ◦ short_code: String (e.g., 'INV', 'BILL', 'BNK'), unique per company, used for prefixes [23, 24].
    ◦ currency_id: Nullable FK to currencies.id, if a journal operates in a specific currency [12].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ Unique constraint on company_id and short_code.
8. journal_entries Table
    ◦ id: PK, auto-increment. This should be the primary, sequentially numbered identifier for all posted financial transactions within a company.
    ◦ company_id: FK to companies.id.
    ◦ journal_id: FK to journals.id, indicating which journal the entry belongs to.
    ◦ entry_date: Date, the accounting date of the transaction [21, 25].
    ◦ reference: String, a unique reference number (e.g., invoice number, bill reference, or internal reference for miscellaneous entries) [21, 25].
    ◦ description: Text, a summary of the entire transaction.
    ◦ total_debit: Decimal, calculated sum of all debit lines, must equal total_credit for balance [2, 4].
    ◦ total_credit: Decimal, calculated sum of all credit lines, must equal total_debit [2, 4].
    ◦ is_posted: Boolean, default false. Once set to true, this record is considered immutable.
    ◦ hash: String (VARCHAR 64 for SHA-256), a cryptographic fingerprint of the entry's essential data. Crucial for data inalterability verification [3, 8, 10, 26].
    ◦ previous_hash: String (VARCHAR 64), the hash of the immediately preceding journal entry, forming a blockchain-like audit chain [3, 8, 10].
    ◦ created_at: Timestamp, the actual system creation date/time (immutable once set), vital for audit trails [3, 8, 10].
    ◦ created_by_user_id: FK to users.id, the user who created this entry.
    ◦ source_type: String (e.g., 'Invoice', 'Bill', 'Payment', 'Asset', 'CreditNote', 'Manual'), polymorphic relation to the originating document.
    ◦ source_id: Integer, ID of the originating document.
    ◦ Unique constraint on company_id, journal_id, and reference (or just company_id, reference if reference is globally unique per company).
9. journal_entry_lines Table
    ◦ id: PK, auto-increment.
    ◦ journal_entry_id: FK to journal_entries.id, links to the header.
    ◦ account_id: FK to accounts.id, the specific account affected by this line.
    ◦ debit: Decimal, amount debited to the account (default 0.00).
    ◦ credit: Decimal, amount credited from the account (default 0.00).
    ◦ description: Text, specific line item description.
    ◦ partner_id: Nullable FK to partners.id, if the line involves a specific customer/vendor.
    ◦ analytic_account_id: Nullable FK to analytic_accounts.id, for tracking costs/revenue against projects/departments [27].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ Constraint: Enforce that either debit or credit is greater than 0, but not both.
10. invoices Table (Customer Invoices)
• id: PK, auto-increment.
• company_id: FK to companies.id.
• customer_id: FK to partners.id.
• invoice_date: Date [12].
• due_date: Date [12].
• invoice_number: String, assigned ONLY upon "confirmation" or "posting" to ensure a clean, unbroken sequence [12, 28].
• status: String (e.g., 'Draft', 'Posted', 'Paid', 'Cancelled'). A 'Draft' invoice can be modified/deleted, but 'Posted' cannot [9, 28].
• currency_id: FK to currencies.id [12].
• total_amount: Decimal, total amount of the invoice.
• total_tax: Decimal.
• journal_entry_id: Nullable FK to journal_entries.id, links to the posted financial transaction.
• created_at: Timestamp.
• updated_at: Timestamp.
• posted_at: Nullable Timestamp, records when the invoice was posted.
• reset_to_draft_log: JSON/Text, logs instances where a 'Posted' invoice was reset to 'Draft' for modification, crucial for audit trail maintenance [8-10].
• Unique constraint on company_id and invoice_number where status is 'Posted'.
11. invoice_lines Table
• id: PK, auto-increment.
• invoice_id: FK to invoices.id.
• product_id: Nullable FK to products.id.
• description: String.
• quantity: Decimal.
• unit_price: Decimal.
• tax_id: Nullable FK to taxes.id, the tax applied to this line.
• subtotal: Decimal (quantity * unit_price).
• total_line_tax: Decimal.
• income_account_id: FK to accounts.id, the specific income account for this line.
• created_at: Timestamp.
• updated_at: Timestamp.
12. vendor_bills Table (Similar structure and immutability rules as invoices)
• id: PK, auto-increment.
• company_id: FK to companies.id.
• vendor_id: FK to partners.id.
• bill_date: Date [21].
• accounting_date: Date [21].
• due_date: Date [21].
• bill_reference: String, the vendor's reference number, not necessarily system-generated or unique across all companies [21].
• status: String (e.g., 'Draft', 'Posted', 'Paid').
• currency_id: FK to currencies.id.
• total_amount: Decimal.
• total_tax: Decimal.
• journal_entry_id: Nullable FK to journal_entries.id.
• created_at: Timestamp.
• updated_at: Timestamp.
• posted_at: Nullable Timestamp.
• reset_to_draft_log: JSON/Text, for audit trail.
13. vendor_bill_lines Table
• id: PK, auto-increment.
• vendor_bill_id: FK to vendor_bills.id.
• product_id: Nullable FK to products.id.
• description: String.
• quantity: Decimal.
• unit_price: Decimal.
• tax_id: Nullable FK to taxes.id.
• subtotal: Decimal.
• total_line_tax: Decimal.
• expense_account_id: FK to accounts.id, the specific expense account for this line.
• created_at: Timestamp.
• updated_at: Timestamp.
14. payments Table (Handles manual cash/bank payments, linked to invoices/bills)
• id: PK, auto-increment.
• company_id: FK to companies.id.
• journal_id: FK to journals.id (must be a 'Bank' or 'Cash' type journal) [29].
• payment_date: Date.
• amount: Decimal.
• currency_id: FK to currencies.id.
• payment_type: String (e.g., 'Inbound' for receipts, 'Outbound' for disbursements).
• reference: String (e.g., check number, manual transaction ID) [21].
• status: String (e.g., 'Draft', 'Confirmed', 'Reconciled'). Once confirmed, payments should be part of the immutable trail.
• paid_to_from_partner_id: FK to partners.id, the customer/vendor associated with the payment.
• journal_entry_id: Nullable FK to journal_entries.id, once confirmed.
• created_at: Timestamp.
• updated_at: Timestamp.
15. payment_document_links Table (Pivot for many-to-many relationship between payments and invoices/bills)
• payment_id: FK to payments.id.
• invoice_id: Nullable FK to invoices.id.
• vendor_bill_id: Nullable FK to vendor_bills.id.
• amount_applied: Decimal, the specific amount of the payment applied to this document.
• created_at: Timestamp.
• updated_at: Timestamp.
• Constraint: Either invoice_id or vendor_bill_id must be present.
16. adjustment_documents Table (For credit notes, debit notes, etc.)
• id: PK, auto-increment.
• company_id: FK to companies.id.
• original_invoice_id: Nullable FK to invoices.id, if it's a credit note for a customer invoice.
• original_vendor_bill_id: Nullable FK to vendor_bills.id, if it's a debit note for a vendor bill.
• type: String (e.g., 'Credit Note', 'Debit Note', 'Miscellaneous Adjustment').
• date: Date.
• reference_number: String, unique for the type/company.
• total_amount: Decimal.
• total_tax: Decimal.
• reason: Text, explains the reason for the adjustment.
• status: String (e.g., 'Draft', 'Posted'). Once posted, this becomes immutable.
• journal_entry_id: Nullable FK to journal_entries.id.
• created_at: Timestamp.
• updated_at: Timestamp.
Supporting Tables (Can utilize Soft Deletes if appropriate)
1. partners Table (Customers and Vendors)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id (if partners are defined per internal company, or can be null if shared across all companies).
    ◦ name: String, the partner's name.
    ◦ type: String (e.g., 'Customer', 'Vendor', 'Both').
    ◦ contact_person: Nullable string.
    ◦ email: Nullable string.
    ◦ phone: Nullable string.
    ◦ address_line_1: Nullable string.
    ◦ address_line_2: Nullable string.
    ◦ city: Nullable string.
    ◦ state: Nullable string.
    ◦ zip_code: Nullable string.
    ◦ country: Nullable string.
    ◦ tax_id: Nullable string [12, 30].
    ◦ is_active: Boolean, default true.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ deleted_at: Nullable Timestamp, for soft deletion of partner records if they are no longer active, but their historical transactions must remain [31].
2. products Table (Items bought/sold)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id (if products are company-specific).
    ◦ name: String.
    ◦ sku: String, unique per company.
    ◦ description: Nullable text.
    ◦ unit_price: Decimal, default sales/purchase price.
    ◦ type: String (e.g., 'Service', 'Storable Product') [32].
    ◦ income_account_id: FK to accounts.id, default income account for sales of this product.
    ◦ expense_account_id: FK to accounts.id, default expense account for purchases of this product [33].
    ◦ is_active: Boolean, default true.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
    ◦ deleted_at: Nullable Timestamp, for soft deletion.
3. taxes Table
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ name: String (e.g., 'VAT 15%', 'Service Tax 5%').
    ◦ rate: Decimal (e.g., 0.15 for 15%).
    ◦ type: String ('Sales', 'Purchase', 'Both').
    ◦ is_active: Boolean, default true.
    ◦ tax_account_id: FK to accounts.id, the account where tax amounts are posted (e.g., VAT Payable/Receivable) [34].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
4. fiscal_positions Table (For tax and account mapping based on partner location/type)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ name: String (e.g., 'Domestic Customer', 'Export Customer') [35].
    ◦ country: Nullable string, if the fiscal position applies to a specific country.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
5. fiscal_position_tax_mappings Table (Pivot table for mapping taxes under fiscal positions)
    ◦ fiscal_position_id: FK to fiscal_positions.id.
    ◦ original_tax_id: FK to taxes.id, the tax applied before mapping.
    ◦ mapped_tax_id: FK to taxes.id, the tax applied after mapping.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
6. fiscal_position_account_mappings Table (Pivot table for mapping accounts under fiscal positions)
    ◦ fiscal_position_id: FK to fiscal_positions.id.
    ◦ original_account_id: FK to accounts.id, the account before mapping.
    ◦ mapped_account_id: FK to accounts.id, the account after mapping.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
7. assets Table (For fixed assets and their depreciation)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ name: String, asset name.
    ◦ purchase_date: Date.
    ◦ purchase_value: Decimal.
    ◦ salvage_value: Decimal (or not_depreciable_value) [36].
    ◦ useful_life_years: Integer, estimated useful life in years [36].
    ◦ depreciation_method: String (e.g., 'Straight-line') [36].
    ◦ asset_account_id: FK to accounts.id, the balance sheet asset account.
    ◦ depreciation_expense_account_id: FK to accounts.id, the P&L expense account for depreciation [36].
    ◦ accumulated_depreciation_account_id: FK to accounts.id, the contra-asset account [36].
    ◦ status: String (e.g., 'Draft', 'Confirmed', 'Depreciating', 'Fully Depreciated', 'Sold').
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
8. depreciation_entries Table (For system-generated depreciation journal entries)
    ◦ id: PK, auto-increment.
    ◦ asset_id: FK to assets.id.
    ◦ depreciation_date: Date, the date for this depreciation entry.
    ◦ amount: Decimal, the depreciation amount for this period.
    ◦ journal_entry_id: Nullable FK to journal_entries.id, links to the actual posted journal entry.
    ◦ status: String ('Draft', 'Posted') [36, 37].
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
9. analytic_accounts Table (For management/cost accounting, separate from general ledger accounts)
    ◦ id: PK, auto-increment.
    ◦ company_id: FK to companies.id.
    ◦ name: String (e.g., 'Marketing Campaign Q1', 'Project A', 'Sales Department') [27].
    ◦ reference: Nullable string, internal reference [27].
    ◦ currency_id: Nullable FK to currencies.id, if specific to a currency [27].
    ◦ is_active: Boolean, default true.
    ◦ created_at: Timestamp.
    ◦ updated_at: Timestamp.
10. analytic_plans Table (To group analytic accounts or define budget structures)
• id: PK, auto-increment.
• company_id: FK to companies.id.
• name: String (e.g., 'Project Budget', 'Departmental Costs').
• created_at: Timestamp.
• updated_at: Timestamp.
11. analytic_account_plan_pivot Table (Many-to-many relationship between analytic accounts and plans)
• analytic_account_id: FK to analytic_accounts.id.
• analytic_plan_id: FK to analytic_plans.id.
• created_at: Timestamp.
• updated_at: Timestamp.
12. budgets Table
• id: PK, auto-increment.
• company_id: FK to companies.id.
• name: String [38].
• period_start_date: Date [38].
• period_end_date: Date [38].
• budget_type: String (e.g., 'Analytic', 'Financial') [38, 39].
• status: String (e.g., 'Draft', 'Open', 'Revised', 'Closed') [38].
• created_at: Timestamp.
• updated_at: Timestamp.
13. budget_lines Table
• id: PK, auto-increment.
• budget_id: FK to budgets.id.
• analytic_account_id: Nullable FK to analytic_accounts.id (if analytic budget) [38].
• account_id: Nullable FK to accounts.id (if financial budget).
• budgeted_amount: Decimal, the planned amount [38].
• achieved_amount: Decimal, calculated from confirmed journal entries (can be cached) [40].
• committed_amount: Decimal, calculated from confirmed POs (can be cached) [40].
• created_at: Timestamp.
• updated_at: Timestamp.
Key Implementation Details & Principles
• Sequential Numbering: For documents like invoices and credit_notes that require sequential numbering, the application layer must ensure numbers are generated and assigned only upon "posting" or "confirmation". This avoids gaps from discarded drafts, which is a key good practice in accounting systems [24, 28].
• Hashing for Immutability: The hash and previous_hash fields in the journal_entries table are critical. When a journal_entry is posted, generate its SHA-256 hash using all essential data fields (e.g., entry_date, journal_id, total_debit, total_credit, account_ids, partner_ids, and the hash of the previous entry). This creates an unbreakable, verifiable chain [3, 8, 10, 26]. Any attempt to tamper with a past entry would invalidate subsequent hashes, making tampering immediately detectable.
• Correction Mechanism: For any errors in posted invoices, vendor_bills, or journal_entries, the system must force the creation of new adjustment_documents (credit/debit notes or miscellaneous journal entries). These new documents should explicitly reference the original transaction they are correcting, ensuring a clear audit trail [8-10].
• Audit Logging Granularity: Beyond the audit_logs table, consider adding specific "chatter" or activity feed logging on individual financial documents (like invoices or vendor_bills), tracking status changes (e.g., 'Draft' to 'Posted', or 'Reset to Draft') and key field modifications, including the user_id and timestamp for each change [3, 8, 10].
• Lock Date Enforcement: The application logic must strictly prevent any new financial journal_entries or modifications to existing ones with an entry_date falling on or before a defined locked_until date for a given company_id and lock_type [3, 8, 10]. This ensures financial periods are closed and secure.
• Manual Data Entry Validation: Given your project's reliance on manual data entry, implement robust real-time validation at the point of entry for all fields, especially numerical and date fields, to minimize errors before records are finalized and posted [10, 11]. Leverage Laravel's validation rules extensively [41, 42].
• Database Transactions: For all operations involving the creation or modification of financial records, wrap the entire process within a database transaction. This ensures that all related database changes are either fully committed or entirely rolled back, preventing partial or inconsistent data states [43, 44]. For queued jobs, ensure the database transaction is fully committed before dispatching dependent jobs to avoid race conditions [2, 11].
• Soft Deletion Application: The deleted_at column (for soft deletes) should only be applied to non-financial records like partners or products. For financial accounts (accounts table), use the is_deprecated flag instead of deletion, as accounts with transactions cannot be truly removed [8, 9, 19].
• Indexing: Ensure proper database indexing on frequently queried columns (e.g., FKs, reference numbers, dates, is_posted flags) to optimize performance.
This detailed database structure, coupled with the described logic and principles, forms a solid, auditable, and compliant foundation for your accounting application, enabling it to compete effectively with established systems by ensuring unwavering data integrity.
--------------------------------------------------------------------------------
Analogy: Think of your accounting database as a meticulously compiled historical archive, not just a dynamic spreadsheet. Every financial transaction, once recorded and "sealed" (posted), becomes an unalterable document etched in stone, complete with its unique, verifiable fingerprint (hash). If a mistake is discovered, you don't erase or deface the original document. Instead, you create a new, corrective document (contra-entry) that explicitly references the original, explaining the change. This new document also gets its own unique fingerprint and is added to the historical chain. This ensures that the entire financial history is transparent, verifiable, and perpetually accountable, much like an unbroken chain of signed and witnessed legal decrees. Your application's architecture is the expert archivist, ensuring every entry is properly documented, linked, and secured against future tampering.
