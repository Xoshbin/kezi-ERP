---
type: "always_apply"
---

# Roo Code General Instructions for Accounting Project

## Coding Philosophy

-   Always prioritize **immutability** and **auditability** in financial data.
-   Do **not** allow deletion or editing of posted transactions.
-   All corrections must be handled using **contra-entries** (credit/debit notes).

## Coding Style

-   Use **Laravel best practices** (Service classes, Eloquent, Queues, Events, Validation).
-   Follow **strict TDD**: All features must be covered by **Pest tests**.
-   Stick to **modular, headless API design** (no frontend logic).

## Data Integrity Rules

-   Enforce **sequential numbering** for posted financial docs (invoices, bills).
-   Assign numbers **only on confirmation**, not during draft.
-   Each posted transaction must include a **SHA-256 hash** and `previous_hash`.

## Architectural Principles

-   Use **manual data entry** only — no Stripe, PayPal, or external gateways.
-   No Laravel Cashier or subscription systems.
-   Support **multi-currency**, **multi-company**, and **fiscal lock dates**.

## Laravel-Specific Guidelines

-   Wrap all financial DB writes in **database transactions**.
-   Never queue jobs until the **DB transaction commits**.
-   Use `.env` and Laravel config files for all environment-specific logic.

## Developer Experience

-   Prioritize **clarity and audit trails** over brevity.
-   Log all actions with user, IP, timestamp, and before/after values.
-   Never allow UI-based "delete" or "edit" actions on posted documents.

## Example Behaviors

-   When resetting a posted invoice to draft, log it in a `reset_to_draft_log`.
-   When correcting a posted bill, generate a new credit note instead.
