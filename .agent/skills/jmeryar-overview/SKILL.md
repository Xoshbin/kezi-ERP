---
name: jmeryar-overview
description: High-level project overview, core design philosophy, and accounting principles. Use to understand the project context.
---

### 🧠 **Purpose**

JMeryar is an **ERP accounting system** built on Laravel 12 and Filament 4. It implements strict double-entry bookkeeping principles inspired by Odoo, adapted for a market without widespread digital banking.

### 🧱 **Core Design Philosophy**

* **Manual Data Entry First:** The system relies on manual input. Integrations like Stripe or PayPal are not included, ensuring full control and transparency over financial data.
* **Immutability is Law:** Posted financial records (**invoices, bills, journal entries**) can never be edited or deleted. Errors are corrected only through reversals (e.g., credit notes), preserving a perfect audit trail.
* **Modular Architecture:** The system is organized into domain-specific modules using `nwidart/laravel-modules`, ensuring separation of concerns and maintainability.
* **Business Logic-Focused TDD:** We use Test-Driven Development (TDD) with **Pest**. All tests focus on core business logic (services, calculations, state changes) and Filament UI workflows.

### 📦 **Module Structure**

The system consists of 11 interconnected modules:

| Module | Purpose |
|--------|---------|
| **Accounting** | Core double-entry engine: GL, Journals, AP/AR, Assets, Bank Reconciliation, Tax |
| **Foundation** | Shared infrastructure: Partners, Companies, Currencies, Settings |
| **Sales** | Customer invoices, quotes, dunning (debt collection) |
| **Purchase** | Vendor bills, purchase orders |
| **Inventory** | Stock management, valuation (FIFO/AVCO), landed costs |
| **HR** | Employees, payroll, attendance, leaves, cash advances |
| **Payment** | Payment processing and allocation |
| **Product** | Product catalog and variants |
| **ProjectManagement** | Projects, tasks, timesheets, budgets |
| **Manufacturing** | BOMs, manufacturing orders |
| **QualityControl** | Quality checks and alerts |

### 🔐 **Immutable & Auditable System**

To ensure data integrity, the system enforces:

* **Reversals Only:** Errors in posted documents are fixed with new, linked entries (reversals), not by changing the original record.
* **Cryptographic Hashing:** Key financial records are hashed to make tampering detectable.
* **Lock Dates:** Financial periods can be "locked," preventing any new or back-dated entries.
* **Detailed Audit Logs:** Every action is logged, including the user, IP address, timestamp, and what was changed.
* **Strict Document Numbering:** Official numbers are assigned only when a document is posted, ensuring no gaps in sequences.

### 🛠️ **Technical Stack**

* **Framework:** Laravel 12 with Filament 4 admin panel
* **Modules:** nwidart/laravel-modules for modular architecture
* **RBAC:** Filament Shield for role-based access control
* **Monetary Precision:** Brick\Money for all financial calculations
* **Testing:** Pest PHP with Livewire and Browser testing plugins

### 📊 **Accounting Capabilities**

The Accounting module provides:

* **General Ledger:** Full double-entry bookkeeping with journals and chart of accounts
* **Accounts Payable/Receivable:** Invoices, vendor bills, payments, credit notes
* **Asset Management:** Assets with depreciation schedules (straight-line, declining balance)
* **Bank Management:** Reconciliation, statements, cheques
* **Multi-Currency:** Currency conversion, revaluation, gain/loss handling
* **Tax Management:** Tax groups/bundles, withholding tax, Iraq VAT returns
* **Reporting:** Trial Balance, P&L, Balance Sheet, Cash Flow, Aged Receivables/Payables

### ✅ **Final Goal**

The goal is to build a trustworthy, immutable, and fully auditable accounting ERP system. It combines the strictness of enterprise systems like Odoo with the flexibility and power of a modern Laravel application.
