# Headless Accounting ERP System

## Overview

This project is a robust, headless accounting and ERP system built on the Laravel framework. It is designed from the ground up with the core principles of **immutability**, **auditability**, and strict adherence to **double-entry bookkeeping standards**. Inspired by the reliability of enterprise-grade systems like Odoo, it is tailored for environments that demand strong manual controls, data integrity, and a transparent, unalterable audit trail.

The system features a comprehensive suite of accounting modules managed through a clean, service-oriented architecture. All business logic is encapsulated within dedicated service classes, ensuring that financial rules are consistently enforced across the application, whether actions are initiated via an API, the administrative panel, or console commands. The administrative interface is powered by **Filament**.

## Core Principles

The architecture is built upon a foundation of strict accounting principles:

-   **Immutability**: Once a financial transaction is posted (e.g., an invoice is confirmed), it cannot be altered or deleted. All corrections are handled through new, offsetting transactions (like Credit Notes or reversing journal entries), preserving a complete and tamper-proof financial history.

-   **Auditability & Traceability**:

    -   **Comprehensive Audit Logs**: An `AuditLogObserver` automatically records all significant events, such as the creation or modification of records, tracking which user performed the action and what was changed.
    -   **Cryptographic Hashing**: Journal entries are cryptographically linked in a blockchain-style chain. Each posted entry contains the hash of the preceding entry, making it computationally infeasible to alter historical records without detection.
    -   **Source Linking**: Every journal entry is linked back to its originating document (e.g., an Invoice, Vendor Bill, or Payment), providing a clear and traceable path from the business document to the general ledger.

-   **Data Integrity**:

    -   The system enforces business rules at multiple levels, including a `JournalEntryService` that validates that all entries are balanced (debits equal credits) before posting.
    -   It prevents the use of deprecated accounts in new transactions.
    -   **Period Locking**: The application allows for the locking of accounting periods, preventing any transactions from being created or modified within a closed period.

-   **Service-Oriented Architecture**: Business logic is cleanly separated from the presentation layer. Services like `InvoiceService`, `VendorBillService`, and `JournalEntryService` contain the core workflows, making the system modular, scalable, and easy to maintain.

-   **Test-Driven Development (TDD)**: The system's integrity is guaranteed by a comprehensive feature test suite written with **Pest**. These tests validate the core accounting logic, ensuring that principles like immutability and transaction balance are never violated.

## Key Features

The application includes a wide range of accounting and ERP features:

-   **Core Accounting Engine**:

    -   Manages the Chart of Accounts, including rules for deprecating accounts instead of deleting them.
    -   Handles financial Journals (e.g., Sales, Purchases, Bank, Cash).
    -   Creates immutable, hash-chained Journal Entries as the ultimate source of financial truth.

-   **Sales & Invoicing**:

    -   Full lifecycle management for customer invoices (Draft -> Posted -> Paid).
    -   Automatic generation of corresponding journal entries upon invoice confirmation.
    -   Support for "reset to draft" functionality for posted invoices, which reverses the original journal entry and logs the action for audit purposes.

-   **Purchases & Vendor Bills**:

    -   Manages the complete lifecycle of vendor bills, from creation to posting.
    -   Ensures accurate tracking of expenses and accounts payable.
    -   Automatically generates journal entries to reflect liabilities and expenses.

-   **Payments & Reconciliation**:

    -   Handles both inbound (customer) and outbound (vendor) payments.
    -   Links payments to one or more invoices/bills.
    -   Features a two-step reconciliation process for bank transactions, moving funds from an "outstanding" account to the main bank account upon confirmation.

-   **Asset Management & Depreciation**:

    -   Tracks fixed assets from acquisition to disposal.
    -   Automates the periodic generation of depreciation entries and their corresponding journal entries.

-   **Multi-Currency Support**:

    -   Handles transactions in foreign currencies.
    -   Automatically converts amounts to the company's base currency for general ledger posting while preserving the original transaction amounts for reconciliation and reporting.

-   **Analytic & Budgetary Accounting**:
    -   Provides a flexible layer for management accounting by allowing journal entry lines to be tagged with **Analytic Accounts**.
    -   Enables cost and revenue tracking by project, department, or any other defined dimension.

## Technology Stack

-   **Backend**: Laravel 11
-   **Admin Panel**: Filament 3
-   **Testing**: Pest

## Architectural Highlights

-   **Service Layer**: Centralizes all business logic for consistency and maintainability.
-   **Observers**: Uses Eloquent Observers (`JournalEntryObserver`, `AuditLogObserver`) to automatically trigger actions like hashing, validation, and logging.
-   **Custom Exceptions**: Employs custom exceptions (e.g., `PeriodIsLockedException`, `DeletionNotAllowedException`) for clear and predictable error handling.
-   **Policies**: Leverages Laravel Policies for fine-grained authorization control over sensitive actions like resetting a posted document to draft.
-   **Custom Casts**: Uses a custom `MoneyCast` to ensure financial data is handled with precision, storing amounts as integers to avoid floating-point inaccuracies.
