# Product Context

This file provides a high-level overview of the project and the expected product that will be created. Initially it is based upon projectBrief.md (if provided) and all other available project-related information in the working directory. This file is intended to be updated as the project evolves, and should be used to inform all other modes of the project's goals and context.
2025-07-22 20:21:10 - Log of updates made will be appended as footnotes to the end of this file.

*

## Project Goal

*   
*   **Project Goal:** To create a comprehensive, double-entry accounting system using the Laravel framework. The system is designed to be robust, auditable, and support multi-company and multi-currency operations.

*   **Key Features:**
    *   Multi-Company Support
    *   Double-Entry Bookkeeping (Journals, Journal Entries)
    *   Invoicing &amp; Vendor Bills (with Draft/Posted states and immutability)
    *   Payments (Inbound/Outbound) &amp; Bank Reconciliation
    *   Adjustment Documents (Credit/Debit Notes)
    *   Fixed Asset Management with automated depreciation
    *   Analytic &amp; Cost Accounting
    *   Budgeting capabilities
    *   Fiscal Positions for automated tax and account mapping
    *   Comprehensive Audit Trails and logging

*   **Overall Architecture:**
    *   **Framework:** Laravel
    *   **Design:** Service-Oriented Architecture (`app/Services`) to encapsulate business logic.
    *   **ORM:** Eloquent ORM with extensive use of Observers (`app/Observers`) to enforce business rules and maintain data integrity.
    *   **Data Integrity:**
        *   Custom Exceptions for business rule violations (e.g., `UpdateNotAllowedException`, `PeriodIsLockedException`).
        *   Custom `MoneyCast` for precise handling of monetary values.
        *   Immutability of posted financial records is a core principle.
    *   **Security &amp; Audit:**
        *   Authorization enforced via Laravel Gates (`app/Policies`).
        *   Blockchain-like hashing of journal entries to ensure an inalterable audit chain.
    *   **Database:** Managed via Laravel Migrations.
    *   **Testing:** Feature tests using PHPUnit (`tests/Feature`).

## Key Features

*   

## Overall Architecture

*   