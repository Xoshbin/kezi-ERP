---
trigger: always_on
---

This document outlines the official coding styles, architectural patterns, and best practices for this application. [cite: 2312] Adherence to these standards is mandatory to ensure consistency, maintainability, and robustness. [cite: 2313]

## 1\. Core Principles

  - **Immutability is Law:** Posted financial records (invoices, bills, journal entries) can NEVER be edited or deleted. [cite: 2314] Corrections are made only through new, reversing transactions. [cite: 2315]
  - **Manual Data Entry First:** The system relies on manual input. [cite: 2315] No third-party payment integrations. [cite: 2316]
  - **Business Logic-Focused TDD:** All tests must focus on the core business logic (services, calculations, state changes) using Pest. [cite: 2316]
  - **No Temporary Hacks:** All development, for both features and tests, must strictly adhere to the established architectural patterns (Actions, DTOs, Services), prioritize code reusability, and align with the overall system design. [cite: 2317] Solutions must be robust and scalable, without violating core accounting principles. Short-term workarounds are forbidden. [cite: 2318]
  - **Single Responsibility Principle (SRP):** Each class, method, or function should have only one reason to change. [cite: 2319] Logic should be narrowly focused and encapsulated. For example, a service should orchestrate a workflow, an action should execute a single business operation, and an observer should react to a model event. [cite: 2320] This keeps the codebase modular, maintainable, and easier to test. [cite: 2321]
  - **Definitive Solution Pattern:** We have established a key pattern for creating models that require pre-save calculations. The business logic for these calculations resides exclusively within a dedicated Action that accepts a DTO. [cite: 2322, 2323] Observers are reserved for side effects (e.g., updating a parent model's totals after a line item is saved). [cite: 2324]
  - **Explicit Context Pattern:** This pattern provides the definitive solution for any situation where a model's attribute depends on context from a parent that isn't available during its creation. [cite: 2326] The responsibility for providing context to a new model instance must be shifted from the model itself to the calling code (the Action or Service). [cite: 2327] The calling code must explicitly create a context-aware object (like `Brick\Money\Money`) and pass that complete object to the creation method. [cite: 2328]
  - **Lowercase Enum & Option Values:** All enum values and any option stored in the database (e.g., status, type, state) **MUST** be lowercase, `snake_case`. [cite: 2329] CamelCase, PascalCase, or uppercase values are strictly prohibited for database storage. [cite: 2330] This ensures consistency, simplifies querying, and avoids case-sensitivity issues. [cite: 2331]
  - **Architectural Consistency:** Ensure any feature, bug fix, or change is consistent with the other parts of the app. [cite: 2332] Analyze the codebase carefully and follow existing patterns. [cite: 2333]
  - **Targeted Changes:** Modify only the code that needs to be changed. [cite: 2333] Avoid unnecessary refactoring to keep commit messages clean and easy to review. [cite: 2334]
  - **Respect Accounting Principles:** Do not violate core accounting principles. [cite: 2335]
  - **Preserve Comments:** Do not remove comments unless they are no longer relevant. Commenting is important for code maintainability. [cite: 2336]

## 2\. The Journal Entry as the Single Source of Truth

  * **(PhD in Accounting) Rationale:** The General Ledger (GL) is the definitive record of a company's financial transactions. To ensure its integrity, all financial events **MUST** be recorded as immutable journal entries. Commercial documents like invoices or payments are important business records, but their financial impact is only official once it is reflected in the GL through a posted journal entry. [cite: 403, 1474] This creates a clear, auditable link from every operational action to its financial consequence and is the foundation of the double-entry system. [cite: 2289, 2291]
  * **(Expert Laravel Architect) Rules:**
      * **Financial Impact via Journal Entry:** Any model that has a financial impact (e.g., `Invoice`, `VendorBill`, `Payment`, `AssetDepreciationLine`) **MUST** have a polymorphic relationship to the `JournalEntry` model. The creation and posting of this `JournalEntry` is the sole mechanism by which a business event is recorded in the accounting system.
      * **Decoupled Creation and Posting:** The creation of a `JournalEntry` (in a `draft` state) **SHALL** be decoupled from its posting. Posting is a final, irreversible action. This is handled by an event listener (e.g., `PostJournalEntry`) after a business document is confirmed, ensuring a clean separation of concerns. [cite: 2378, 2379, 2600, 2602]
      * **Source of Truth for Reports:** All financial reports (Trial Balance, P\&L, Balance Sheet) **MUST** be generated exclusively from the `journal_entry_lines` table. They **MUST NOT** be calculated from `invoices`, `bills`, or other commercial documents directly.
      * **Data Consistency:** The `JournalEntry` **MUST** store key redundant data at the time of posting (e.g., the partner name) to ensure that reports from past periods remain consistent, even if the source partner record is later updated.

## 3\. State Management: PHP 8.1+ Backed Enums

**Rule:** All state management (e.g., `status`, `state`, `type`) **MUST** be implemented using PHP 8.1+ Backed Enums. [cite: 2337] The use of class constants is deprecated. [cite: 2338] Enum cases **MUST** be `PascalCase`, while their corresponding string values **MUST** be `snake_case`. [cite: 2339]

**Rationale:**

  **Type Safety:** Enums provide absolute type safety. [cite: 2340]
  **Code Clarity & Discoverability:** Enums are self-documenting. [cite: 2340] `PascalCase` for cases follows official PHP examples. [cite: 2341]
  **Reduced Boilerplate:** Enums can contain their own related logic. [cite: 2342]
  **Database Consistency:** Using `snake_case` for backed string values aligns with our `Lowercase Enum & Option Values` core principle. [cite: 2343]

**Example:**

```php
// app/Enums/Inventory/StockLocationType.php
enum StockLocationType: string
{
    case Internal = 'internal';
    case Customer = 'customer';
    case Vendor = 'vendor';
    case InventoryAdjustment = 'inventory_adjustment';
}

// Example usage in a model:
class StockLocation extends Model
{
    protected $casts = [
        'type' => StockLocationType::class,
    ];
}
```

## 4\. Layered Architecture

The application follows a strict layered architecture to separate concerns. [cite: 2346, 4784] The primary layers are: Actions, Data Transfer Objects (DTOs), Services, Observers, and Policies. [cite: 2347]

### 4.1. Actions Layer (`app/Actions/`)

**Purpose:** To encapsulate a single, specific business operation (Command Pattern). [cite: 2348, 9355] Actions are the only place where data is written or modified. [cite: 2349]

**Rules:**

  Each Action **MUST** have a single public `execute()` method. [cite: 2350]
  The `execute()` method **MUST** be wrapped in a `DB::transaction()` to ensure atomicity. [cite: 2351]
  Actions **SHOULD** accept a DTO for input to enforce a strict data contract. [cite: 2352, 2608]
  Actions are organized by domain (e.g., `Accounting/`, `Sales/`). [cite: 2353]
  Actions are responsible for their own validation or should call a dedicated validation service. [cite: 2354]

### 4.2. Data Transfer Objects (`app/DataTransferObjects/`)

**Purpose:** To provide type-safe, immutable data contracts for transferring data between layers, especially into Actions. [cite: 2355]

**Rules:**

  All DTOs **MUST** be `readonly` classes. [cite: 2356]
  All properties **MUST** be `public readonly` and have strict type hints. [cite: 2356]
  Data is passed exclusively through the constructor. [cite: 2357]
  * DTOs contain **NO** business logic. They are pure data structures. [cite: 2357]
  DTOs **SHOULD** be organized by domain, mirroring the `Actions` structure. [cite: 2358]

### 4.3. Service Layer (`app/Services/`)

**Purpose:** To orchestrate complex business workflows that may involve multiple Actions, validation steps, or dispatching events. [cite: 2359] Services do **NOT** directly modify data; they delegate that responsibility to Actions. [cite: 2360]

**Rules:**

  Services contain high-level business process logic. [cite: 2360]
  Services call Actions to perform data modifications. [cite: 2361]
  Services are responsible for dispatching domain events (e.g., `InvoiceConfirmed`). [cite: 2361]
  Services can be injected into Filament Resources, Livewire Components, and other Services. [cite: 2362]

### 4.4. Observers (`app/Observers/`)

**Purpose:** To react to Eloquent model lifecycle events (`creating`, `updating`, `deleting`) to enforce system-level data integrity rules and trigger automatic side effects. [cite: 2363, 4481]

**Rules:**

  Observers are used for **System Reactions**, NOT for business rule authorization. [cite: 2364]
  * **Use Cases:**
      Enforcing non-negotiable data integrity (e.g., preventing deletion of a posted record). [cite: 2365]
      Automatically calculating fields (e.g., line totals). [cite: 2366]
      Maintaining the cryptographic hash chain on `JournalEntry`. [cite: 2366]
      Creating `AuditLog` entries. [cite: 2366]

#### 4.4.1. Observer Registration

**Rule:** Observers **MUST** be registered using the `#[ObservedBy]` attribute directly on the corresponding Eloquent model. [cite: 2367] Manual registration in `EventServiceProvider` is prohibited. [cite: 2368]

**Rationale:**

  **Colocation of Logic:** Keeps the connection between a model and its observer in one place. [cite: 2368]
  **Clarity & Explicitness:** Makes it immediately clear which observers are acting on a model. [cite: 2369]
  **Modern Syntax:** Aligns with modern Laravel practices. [cite: 2370]

### 4.5. Policies (`app/Policies/`)

**Purpose:** To handle all user **Authorization**. [cite: 2371] Policies answer the question, "Can the current user perform this action on this model?" [cite: 2372]

**Rules:**

  All authorization checks **MUST** be handled by a Policy. [cite: 2373]
  Do **NOT** place authorization logic in Observers, Services, or Actions. [cite: 2375]

### 4.6. Events & Listeners (`app/Events/`, `app/Listeners/`)

**Purpose:** To decouple different parts of the system. [cite: 2376] An action in one domain can trigger a reaction in another without them being tightly coupled. [cite: 2377]

**Rules:**

  Events are dispatched from Services after a core business process is completed. [cite: 2378]
  Listeners subscribe to these events to perform follow-up actions. [cite: 2379]

## 5\. Financial Calculations

**Rule:** All monetary values **MUST** be handled using `Brick\Money` objects. Never use floats for financial calculations. [cite: 2380]

**Rationale:** To avoid floating-point inaccuracies and ensure precision. [cite: 2381]
**Implementation:** Use the custom `MoneyCast` on all model properties that represent money. All calculations must be performed using the methods provided by the `Brick\Money` library. [cite: 2382]

### 5.1. MoneyCast and Currency Precision

**Rule:** The `app/Casts/MoneyCast.php` class automatically handles the conversion between major and minor currency units based on the currency's defined decimal places. [cite: 2383] All numeric input is treated as major units. [cite: 2384]

**Rationale:** To ensure monetary values are stored with the correct precision, preventing rounding errors. [cite: 2385]

### 5.2. Safe Aggregation of Money Objects

**Rule:** When calculating totals, you **MUST** use the arithmetic methods provided by `Brick\Money\Money` objects (e.g., `plus()`). Direct casting of the `Money` object or its amount to a `float` for summation is strictly forbidden and w