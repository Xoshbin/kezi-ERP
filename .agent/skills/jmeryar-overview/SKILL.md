---
name: jmeryar-overview
description: High-level project overview, core design philosophy, and accounting principles. Use to understand the project context.
---

### 🧠 **Purpose**

This document outlines the coding and architectural principles for building a **headless accounting application for Iraq**. It's inspired by Odoo's robust accounting rules but adapted for a market without widespread digital banking.

### 🧱 **Core Design Philosophy**

* **Manual Data Entry First:** The system relies entirely on manual input. Integrations like Stripe or PayPal are not included, ensuring full control and transparency over financial data.
* **Immutability is Law:** Posted financial records (**invoices, bills, journal entries**) can never be edited or deleted. Errors are corrected only through reversals (e.g., credit notes), preserving a perfect audit trail.
* **Headless Laravel Backend:** The architecture is backend-only. The initial focus is on building solid business logic within services and actions, completely separate from any API or web interface.
* **Business Logic-Focused TDD:** We use Test-Driven Development (TDD) with **Pest**. All tests must focus on the core business logic (services, calculations, state changes). At this stage, **we do not write tests for API endpoints**, as no API interfaces exist yet. This ensures the core engine is flawless before any UI is connected.

### 🔐 **Immutable & Auditable System**

To ensure data integrity, the system enforces:

* **Reversals Only:** Errors in posted documents are fixed with new, linked entries (reversals), not by changing the original record.
* **Cryptographic Hashing:** Key financial records are hashed (similar to a blockchain) to make tampering detectable.
* **Lock Dates:** Financial periods can be "locked," preventing any new or back-dated entries.
* **Detailed Audit Logs:** Every action is logged, including the user, IP address, timestamp, and what was changed.
* **Strict Document Numbering:** Official numbers are assigned only when a document is posted, ensuring no gaps in sequences.

### 🧩 **Essential Database Design**

The database schema includes tables for `companies`, `users`, `accounts`, `journals`, `journal_entries`, `invoices`, `payments`, `products`, `partners`, and more. Key principles include using foreign key constraints for data integrity and adding audit fields to every table.

### 🛠️ **Laravel Best Practices**

* **Service Layer:** All business logic is kept in dedicated service classes (e.g., `InvoiceService`, `PaymentService`) to keep controllers thin and logic reusable.
* **Strong Validation:** All incoming data is strictly validated at the entry point to the business logic.
* **Queues for Heavy Jobs:** Long-running tasks like posting a complex invoice are handled by background jobs to ensure the application remains fast.
* **Events & Listeners:** System components are decoupled using events (e.g., `InvoicePosted`) to trigger actions like creating journal entries.
* **Security:** Standard Laravel security features, including strong password hashing (Bcrypt/Argon2) and protection against common web vulnerabilities, are used.

### 🏗️ **Architectural Patterns**

The system implements a sophisticated layered architecture:

* **Actions Layer (Command Pattern):** Domain-organized business operations with single `execute()` methods, atomic database transactions, and proper dependency injection. Actions handle specific business workflows like invoice creation, payment processing, and journal entry generation.

* **Data Transfer Objects (DTOs):** Type-safe, immutable data contracts with readonly properties that define clear boundaries between layers. DTOs mirror the Actions structure and provide composition for complex operations (e.g., Invoice containing InvoiceLine DTOs).

* **Service Orchestration:** Core services (`JournalEntryService`, `InvoiceService`, `PaymentService`, etc.) implement business rules and coordinate complex workflows. Services enforce immutability, handle event dispatching, and manage audit trail creation.

* **Filament Integration:** Clean separation between UI and business logic where Filament resources delegate all operations to Actions and Services. Form data is transformed into DTOs before business logic execution.

* **Livewire Components:** Interactive UI components for complex real-time functionality (e.g., bank reconciliation matching) that integrate seamlessly with the service layer while maintaining proper Money object handling and audit trails.

* **Money Object Precision:** All monetary values use `Brick\Money` objects throughout the application with a custom `MoneyCast` for automatic conversion, ensuring precise financial calculations and multi-currency support.

* **Multi-Company Architecture:** Company-specific configurations stored in the `Company` model rather than global config files, enabling proper multi-tenant accounting with isolated financial data per company.

### 📊 **Accounting Workflows**

The system is designed to handle core accounting workflows, including:

* **Sales & Invoicing**
* **Purchases & Vendor Bills**
* **Payments & Reconciliation**
* **Asset Management & Depreciation**
* **Budgeting & Analytics**

### ✅ **Final Goal**

The goal is to build a trustworthy, immutable, and fully auditable accounting backend for the Iraqi market. It combines the strictness of enterprise systems like Odoo with the flexibility and power of a modern Laravel application.
