Alright, AccounTech Pro here, ready to lay out a robust plan for asset management and depreciation in your **Laravel 12** application, leveraging **Filament** and **Pest**. We'll build upon your existing architecture, translating Odoo's strong asset principles into your stack.

### 🧠 Accounting Rationale for Asset Management

From a PhD perspective, implementing robust asset management is crucial for accurate financial reporting and compliance with accrual accounting principles.

*   **Capitalization:** When you acquire a fixed asset (like IT equipment or a vehicle), its cost is not immediately expensed. Instead, it's "capitalized" and recorded as an Asset on the Balance Sheet. This is because these assets provide economic benefits over multiple accounting periods, exceeding one year.
*   **Depreciation:** Assets, except for land, gradually lose value over their useful life due to wear and tear, obsolescence, or usage. Depreciation is the systematic allocation of this asset's cost over its useful life. It's an expense that impacts your Profit & Loss statement (decreasing profit) and a "contra-asset" account (Accumulated Depreciation) on the Balance Sheet, which reduces the book value of the asset.
*   **Immutability:** Once depreciation entries (which are journal entries) are posted, they, like all other financial transactions, **cannot be directly edited or deleted**. Corrections must be made via new, offsetting entries.

### 🛠️ Technical Plan: Implementing Assets and Depreciation

Your existing `assets` and `depreciation_entries` tables in `all_migrations.md` are a great start! We'll build on those.

#### 1. Database Design (Enhancing Existing Models)

Your `assets` and `depreciation_entries` tables are already defined. We'll ensure these are fully integrated:

*   **`assets` Table:**
    *   `id`: Primary Key.
    *   `company_id`: FK to `companies.id`.
    *   `name`: String, asset name.
    *   `purchase_date`: Date.
    *   `purchase_value`: Decimal.
    *   `salvage_value`: Decimal (or `not_depreciable_value`).
    *   `useful_life_years`: Integer.
    *   `depreciation_method`: String (e.g., 'Straight-line', 'Declining').
    *   `asset_account_id`: FK to `accounts.id` (Balance Sheet asset account, type 'Fixed Assets' or 'Non-current Assets').
    *   `depreciation_expense_account_id`: FK to `accounts.id` (P&L expense account).
    *   `accumulated_depreciation_account_id`: FK to `accounts.id` (Contra-asset account).
    *   `status`: String ('Draft', 'Confirmed', 'Depreciating', 'Fully Depreciated', 'Sold').
    *   `source_type`, `source_id`: Polymorphic relation to originating document (e.g., `VendorBill`) for auditability.
    *   `created_at`, `updated_at`.

*   **`depreciation_entries` Table:**
    *   `id`: Primary Key.
    *   `asset_id`: FK to `assets.id`.
    *   `depreciation_date`: Date.
    *   `amount`: Decimal, the depreciation amount for this period.
    *   `journal_entry_id`: Nullable FK to `journal_entries.id` (links to the actual posted JE).
    *   `status`: String ('Draft', 'Posted').
    *   `created_at`, `updated_at`.

#### 2. Service Layer (Business Logic)

We'll introduce an `AssetService` to encapsulate all asset-related logic, adhering to your service-oriented architecture.

*   **`AssetService` (Core Business Logic):**
    *   **`createAsset(array $data)` Action/Method:**
        *   Creates a new `Asset` record.
        *   Can be called manually or triggered automatically (e.g., upon posting a `VendorBill` where a line item is marked as an asset).
        *   Initializes the `depreciation_board` (schedule of future depreciation entries) in `draft` status, similar to Odoo.
        *   **Initial Journal Entry:** Upon creation/confirmation, if applicable (e.g., from a purchase), it generates a `JournalEntry` to **Debit** the `Asset Account` (e.g., 1500 - IT Equipment) and **Credit** `Accounts Payable` or `Bank`. This entry will be hashed and immutable.
    *   **`computeDepreciation(Asset $asset)`:**
        *   Calculates the depreciation schedule (Depreciation Board) based on `depreciation_method`, `purchase_value`, `salvage_value`, and `useful_life_years`.
        *   Populates the `depreciation_entries` table with *draft* entries.
    *   **`postDepreciation(DepreciationEntry $depreciationEntry)` Action/Method:**
        *   This is the critical step for generating accounting entries periodically.
        *   Creates a new `JournalEntry` for the depreciation period:
            *   **Debit:** `Depreciation Expense` account (P&L).
            *   **Credit:** `Accumulated Depreciation` account (Contra-asset).
        *   Ensure the `JournalEntry` balances (debits = credits).
        *   Sets `is_posted = true` for the `JournalEntry` and `depreciation_entry`.
        *   **Crucially, applies cryptographic hashing (`hash` and `previous_hash`) to the new `JournalEntry` to ensure immutability and link it to the audit chain**.
        *   Links the `JournalEntry` back to the `DepreciationEntry` via `source_type`/`source_id`.
        *   Updates the asset's `recognized_amount` (if you track this on `assets`) and `last_depreciation_date`.
    *   **`modifyAsset(Asset $asset, array $newValues)`:**
        *   Handles changes to an asset's value or depreciation schedule.
        *   This will involve creating *new* journal entries for value increases/decreases, and recalculating *future unposted* depreciation entries.
    *   **`disposeAsset(Asset $asset, float $salePrice = 0)`:**
        *   Records the disposal or sale of an asset.
        *   Generates a `JournalEntry` to remove the asset from the books, accounting for any gain or loss on sale. This typically involves:
            *   **Debit:** Cash/Bank (if sold) and Accumulated Depreciation.
            *   **Credit:** Asset account (original value) and Gain on Sale (if applicable, Income).
            *   **Debit:** Loss on Sale (if applicable, Expense).
        *   Updates the asset `status` to 'Sold'.

#### 3. Automation (Laravel Scheduler & Queues)

Automating periodic depreciation is key for an Odoo-inspired system.

*   **Scheduled Artisan Command:**
    *   Create `app/Console/Commands/ProcessDepreciations.php`.
    *   This command will run daily (or monthly) using Laravel's scheduler.
    *   It queries for `Asset` records with `status = 'Depreciating'` and `depreciation_entries` in `draft` status that are due for posting.
    *   **Dispatches a Queued Job:** For each eligible `DepreciationEntry`, it dispatches a `ProcessDepreciationJob`.
*   **`ProcessDepreciationJob` (Queued Job):**
    *   This job calls `AssetService::postDepreciation()` for the specific `DepreciationEntry`.
    *   **Database Transaction Awareness:** Implement `ShouldQueue` and `AfterCommit` to ensure the job only runs *after* any preceding database transactions (e.g., initial asset purchase) are fully committed, preventing race conditions.

#### 4. Filament Integration (UI Layer)

Filament will provide the administrative interface for managing assets, delegating all operations to your Services and Actions.

*   **`app/Filament/Resources/AssetResource.php`:**
    *   Allows CRUD (Create, Read, Update, Dispose) for assets.
    *   Forms will capture `name`, `purchase_date`, `purchase_value`, `salvage_value`, `useful_life_years`, `depreciation_method`, and link to appropriate `Accounts` (using select fields populated from your `accounts` table).
    *   When creating/updating an asset, form data is transformed into a DTO and passed to `AssetService::createAsset()` or `modifyAsset()`.
    *   **Action Button:** A "Compute Depreciation" action on the asset view page can trigger `AssetService::computeDepreciation()` to generate the `depreciation_entries` (draft board).
    *   **Relation Manager:** A `DepreciationEntryRelationManager` to display the depreciation schedule for an asset. Each entry will have a "Post" button (for manual triggering) or simply reflect the `status` as 'Posted' when automated.
*   **Automation Configuration:** A Filament settings page or resource to configure the depreciation schedule frequency (e.g., via a `Company` setting for `depreciation_frequency`).

#### 5. Testing Strategy (Pest)

Rigorous testing with Pest is paramount to ensure the integrity of your asset management system.

*   **Feature Tests (`tests/Feature/`):**
    *   **`test('asset can be created manually and confirmed')`:** Verify creation of `Asset` records and initial status.
    *   **`test('purchasing product with fixed asset account creates draft asset entry')` (Automatic):** Simulate a `VendorBill` for an asset and assert that a corresponding `Asset` record is created (e.g., in 'Draft' status, waiting for confirmation).
    *   **`test('confirming asset generates initial journal entry')`:** When an asset is "Confirmed" (if distinct from creation), verify the correct `JournalEntry` is created, linking `IT Equipment` (Debit) and `Accounts Payable/Bank` (Credit). Ensure it's `is_posted = true` and `hashed`.
    *   **`test('depreciation calculation generates correct draft entries')`:** Test `AssetService::computeDepreciation()` for different methods (Straight-line, Declining) and assert that `depreciation_entries` are populated correctly in `draft` status.
    *   **`test('automated depreciation job posts correct journal entries periodically')`:**
        *   Set up an asset due for depreciation.
        *   Simulate running the scheduled command/job.
        *   Assert a new `JournalEntry` is created with the correct `Debit` to `Depreciation Expense` and `Credit` to `Accumulated Depreciation`.
        *   Verify the `JournalEntry` is `is_posted = true`, `hashed`, and linked to the `depreciation_entry`.
        *   Assert `DepreciationEntry` status updates to 'Posted'.
    *   **`test('posted depreciation entries are immutable and hashed')`:** After a `depreciation_entry` (and its underlying `JournalEntry`) is posted, attempt to modify/delete it directly and assert `UpdateNotAllowedException`/`DeletionNotAllowedException` is thrown. Verify `hash` and `previous_hash` fields are populated.
    *   **`test('asset modification recomputes future depreciation schedule')`:** Test `AssetService::modifyAsset()` to ensure changes correctly update future *unposted* depreciation entries and log the modification.
    *   **`test('asset disposal correctly generates final journal entries')`:** Test `AssetService::disposeAsset()` to ensure the asset is removed from the balance sheet, and any gain/loss on sale is correctly recorded via a new `JournalEntry`.
    *   **`test('lock dates prevent depreciation entries in locked periods')`:** Attempt to post depreciation for a period locked by `LockDate` and assert `PeriodIsLockedException`.

By following this plan, you will have a robust, auditable, and Odoo-inspired asset management system within your Laravel application.
