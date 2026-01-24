# UI Replacement & Infrastructure Analysis Report

## 1. Executive Summary

**Verdict: The core business logic is highly reusable, but the API infrastructure is non-existent.**

The codebase is **Architecturally Ready** for a UI replacement in terms of business logic. The strict use of the **Action Pattern** and **Data Transfer Objects (DTOs)** means that complex operations (like "Create Journal Entry" or "Validate Stock Picking") are fully decoupled from the Filament UI.

However, the **Infrastructure Layer** (the "pipes" that connect a UI to that logic) is **Missing** or **Tightly Coupled to Filament**. You do not currently have a functioning REST or GraphQL API that a React/Vue app could consume.

Replacing Filament today would require building a complete API layer (Controllers, Routes, Resources, Middleware) to expose the existing Actions.

---

## 2. Architecture Readiness (The Good)

The application follows a robust Domain-Driven Design (DDD) style structure which greatly facilitates switching UIs.

*   **Decoupled Logic:** Critical business operations are encapsulated in `Action` classes, not inside Controllers or Livewire components.
    *   *Example:* `Modules/Accounting/Actions/Accounting/CreateJournalEntryAction.php` contains all the rules for balancing ledgers, locking dates, and currency conversion. It does not know "Filament" exists.
*   **Strong Typing:** The use of DTOs (e.g., `CreateJournalEntryDTO`) defines a clear contract. Any new UI simply needs to construct this object and pass it to the Action.
*   **Database Isolation:** Database transactions are handled within Actions, ensuring data integrity regardless of the entry point (API vs UI).

---

## 3. Infrastructure Gaps (The Missing Pieces)

To connect a React/Vue interface, you need to fill the following gaps:

### 3.1. Authentication & Tenancy (Critical)
Currently, the application relies on `Filament::getTenant()` to identify the active company. This is a "Hard Dependency".
*   **Current State:** Middleware like `App\Http\Middleware\SetPermissionsTeamId` explicitly calls `Filament::getTenant()`.
*   **The Gap:** A separate API cannot use Filament's session-based tenant resolver.
*   **Required Work:** You must implement a new Tenant Middleware (e.g., resolving from an `X-Company-ID` header) that works with Laravel Sanctum (which is already installed but unused).

### 3.2. The API Layer
The application has `routes/api.php` files, but they are mostly empty shells.
*   **Controllers:** Most controllers (e.g., `SalesController`) are stubs or return Views. They do not return JSON.
*   **Transformers (Resources):** There are **zero** `Http/Resources` (API Resources). This means if you request a `SalesOrder`, you get the raw Database Model. This is bad practice for a separate UI because:
    *   It exposes internal database columns.
    *   It doesn't handle relationship nesting cleanly.
    *   It breaks if you rename a database column.
*   **Input Validation:** Validation is currently split between **Actions** (strict business rules) and **Filament Forms** (UI feedback). You lack **FormRequest** classes to validate API input before it hits the domain logic.

### 3.3. Field Level Security
As noted in your internal research (`RBAC_FIELD_VISIBILITY_RESEARCH.md`), visibility logic is currently ad-hoc inside Filament Resources (`->visible()`).
*   **The Gap:** An external UI (React/Vue) has no way of knowing "Can this user see the Cost Price field?".
*   **Required Work:** You need a centralized "Field Policy" API endpoint so the frontend knows which fields to hide/disable.

---

## 4. Module Deep Dive

### 4.1. Accounting Module
*   **Readiness:** High.
*   **Strengths:** Actions like `CreateJournalEntryAction` are very robust and handle complex currency math internally.
*   **Challenge:** The "Preparation Logic" is currently in the Filament Page.
    *   *Example:* In `CreateJournalEntry.php`, the system calculates "Base Currency Amount" from "Foreign Amount" *before* creating the DTO. A new API Controller would need to replicate this math to ensure the DTO receives the correct data structure.

### 4.2. Inventory Module
*   **Readiness:** Medium.
*   **Strengths:** `ValidateStockPickingAction` handles the heavy lifting (Quality Control, Backorders, Transactions).
*   **Challenge:** "Wizard" Data Hydration.
    *   *Example:* The `ValidateStockPicking` page has a complex `mount()` method that gathers Moves, Product Lines, and Lot/Serial Numbers into a flat structure for the user to edit.
    *   **The Gap:** Your new API will need a dedicated "Get Validation Data" endpoint that mimics this logic, sending a structured JSON object to the React/Vue app so it can render the "Scanner/Validation" screen.

---

## 5. Roadmap & Recommendations

If you plan to introduce a React/Vue interface, I recommend the following phases:

**Phase 1: Foundation (Infrastructure)**
1.  **Tenant Middleware:** Build a middleware that reads `X-Company-ID` header and sets the active Tenant context (replacing `Filament::getTenant()`).
2.  **API Standards:** Define a base `ApiController` and a standard JSON response format (Success/Error/Pagination).

**Phase 2: The "Glue" Layer**
For each module you want to expose:
1.  **Create API Resources:** Map your Models (SalesOrder, Product) to clean JSON structures.
2.  **Create FormRequests:** Replicate the validation rules found in Filament Forms into reusable Request classes.
3.  **Create Controllers:** Write simple controllers that:
    *   Accept JSON input.
    *   Validate it (FormRequest).
    *   Convert it to a DTO.
    *   Call the existing **Action**.
    *   Return the Result (API Resource).

**Phase 3: Field Security**
*   Implement the "Strategy B" from your RBAC research (Centralized Registry) and expose it via an endpoint like `/api/v1/user/permissions` so the UI knows what to render.

**Effort Estimate:**
*   **Backend Plumbing:** ~2 weeks (Auth, Base Classes, Middleware).
*   **Per Module Exposure:** ~1 week per module (writing Resources/Requests for all major actions).

**Conclusion:** Your backend **Logic** is ready. Your backend **Plumbing** is not.
