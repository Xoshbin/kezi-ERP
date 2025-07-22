🧠 Purpose
The file sets global coding and architectural principles for building a headless accounting app for Iraq, inspired by Odoo but tailored to Iraq’s lack of digital banking.

🧱 Core Design Philosophy
Manual data entry only: No Stripe, PayPal, or Laravel Cashier. Ensures full control and transparency.

No deletion or editing of posted financial records: Only reversals (credit notes, debit notes) are allowed.

Headless Laravel API: Backend-only architecture for flexibility (web, mobile, etc.).

Test-Driven Development (TDD) using Pest to guarantee code quality.

🔐 Immutable & Auditable Accounting System
Inspired by Odoo, it enforces:

Immutability: Posted records can’t be altered.

Reversals only: Fix errors via contra-entries, not edits.

Cryptographic Hashing: Like blockchain, to prevent tampering.

Lock Dates: Prevent changes to closed financial periods.

Audit Logs: Track every change, user, IP, timestamp, etc.

Strict Numbering: Sequential numbers only upon posting (no gaps).

🧩 Essential Database Design
Comprehensive schema includes:

companies, users, currencies, accounts

journals, journal_entries, journal_entry_lines

invoices, vendor_bills, payments, adjustments

products, partners, taxes, assets, budgets, analytics

Key ideas: foreign keys, soft deletes for non-financial data, and audit fields on every table.

🛠️ Laravel Best Practices
Validation: Strong real-time validation at entry point.

Migrations: Full version control for DB schema.

Eloquent ORM: Used for clean and secure DB interaction.

Queues: Background job processing with proper DB transactions.

Events/Listeners: Decoupled design for tasks like posting invoices.

Service Layer: Business logic via dedicated services (InvoiceService, etc.).

Logging: Laravel logs + full audit trails.

Security: Strong password hashing (Bcrypt/Argon2).

Environment Configs: Use .env for environment-specific settings.

📊 Accounting Workflows
Handles:

Sales & Invoices: Drafts → Post → Journal entries.

Purchases & Vendor Bills: Manual entry, journalized at confirmation.

Payments: Manual, reconciled against invoices/bills.

Assets & Depreciation: Tracked and auto-depreciated.

Deferred Revenues/Expenses: Accrual-based support.

Budgets & Analytics: Per project/department for performance tracking.

📋 Analogy
Think of it like stone carving: drafts are like sketches, but once confirmed (posted), the record is etched in stone. Corrections are new carvings, never edits on the original.

✅ Final Goal
Build a trustworthy, immutable, fully auditable accounting backend tailored for Iraq, with the robustness and modularity of Odoo, but Laravel-native and banking-independent.
