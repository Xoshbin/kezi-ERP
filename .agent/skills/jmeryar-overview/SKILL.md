---
name: kezi-overview
description: High-level project overview, core design philosophy, and accounting principles. Use to understand the project context.
---

### 🧠 **Purpose**

Kezi is an **ERP accounting system** built on Laravel 12 and Filament 4. It implements strict double-entry bookkeeping principles inspired by Odoo, adapted for a market without widespread digital banking.

### 🧱 **Core Design Philosophy**

<philosophy>
* **Manual Data Entry First:** The system relies on manual input. Integrations like Stripe or PayPal are not included, ensuring full control and transparency over financial data.
* **Immutability is Law:** Posted financial records (**invoices, bills, journal entries**) can never be edited or deleted. Errors are corrected only through reversals (e.g., credit notes), preserving a perfect audit trail.
* **Modular Package Architecture:** The system is organized into domain-specific local PHP packages in `packages/kezi/`, ensuring strict separation of concerns and maintainability via Composer path repositories.
* **Business Logic-Focused TDD:** We use Test-Driven Development (TDD) with **Pest**. All tests focus on core business logic (services, calculations, state changes) and Filament UI workflows.
</philosophy>

### 📦 **Module Structure**

<modules>
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
</modules>

### 🔐 **Immutable & Auditable System**

<integrity>
* **Reversals Only:** Errors in posted documents are fixed with new, linked entries (reversals), not by changing the original record.
* **Cryptographic Hashing:** Key financial records are hashed to make tampering detectable.
* **Lock Dates:** Financial periods can be "locked," preventing any new or back-dated entries.
* **Detailed Audit Logs:** Every action is logged, including the user, IP address, timestamp, and what was changed.
* **Strict Document Numbering:** Official numbers are assigned only when a document is posted, ensuring no gaps in sequences.
</integrity>

### ✅ **Project Goal**

<objective>
The ultimate goal is to build a trustworthy, immutable, and fully auditable accounting ERP system that competes with enterprise systems like Odoo and SAP by leveraging the flexibility of the Laravel/Filament ecosystem.
</objective>
