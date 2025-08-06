### **🧠 Accounting Rationale: The Core of Inventory Management**

From a PhD perspective, inventory management is crucial for accurate financial reporting and operational efficiency. The goal is to track goods from acquisition to sale, accurately reflecting their value on the Balance Sheet (as an Asset) and their cost when sold on the Profit & Loss Statement (as Cost of Goods Sold or COGS).

Key accounting principles underpinning this system include:

*   **Perpetual Inventory System:** This system continuously updates inventory records for both quantities and values as transactions occur, providing real-time inventory balances. This is in contrast to a periodic system, which relies on physical counts to adjust balances only at specific intervals. Your application will primarily leverage a perpetual system.
*   **Inventory Valuation Methods:** Different methods impact the Cost of Goods Sold (COGS) and the ending inventory value:
    *   **Average Cost (AVCO):** Recalculates the weighted average cost each time new products are received.
    *   **FIFO (First-In, First-Out):** Assumes the first goods purchased are the first ones sold.
    *   **LIFO (Last-In, First-Out):** Assumes the last goods purchased are the first ones sold.
    *   **Standard Price:** A fixed cost for a product.
*   **Immutability for Accounting Impact:** As with all financial records in your system, once inventory valuation entries (Journal Entries) are posted, they are immutable and cannot be directly deleted or modified. Corrections must be made via new, offsetting transactions. Cryptographic hashing will secure these entries.
*   **Double-Entry Bookkeeping:** Every inventory-related transaction will result in balanced debit and credit entries, ensuring your accounting equation remains in balance.

---

### **📊 Current Application Schema Analysis for Inventory**

Your current application has a strong foundation for core accounting, immutability, and a layered architecture. Here's what we have and what's missing concerning inventory:

**Existing Components:**

*   **Models:** You have a `products` table. You also have `accounts` (for Inventory/COGS GL accounts), `journal_entries`, `journal_entry_lines`, `invoices`, `invoice_lines`, `vendor_bills`, `vendor_bill_lines`, `companies`, `users`, etc..
*   **Core Architectural Patterns:** Your system rigorously applies:
    *   **Service Layer:** For encapsulating business logic.
    *   **Actions Layer (Command Pattern):** For specific business operations.
    *   **Data Transfer Objects (DTOs):** For type-safe, immutable data contracts.
    *   **Observers:** For model lifecycle side effects.
    *   **Events & Listeners:** For decoupling system components.
    *   **Queues:** For heavy, asynchronous jobs.
    *   **Money Object Precision:** Using `Brick\Money` and `MoneyCast`.
    *   **Immutability & Hashing:** For `journal_entries`.
    *   **Multi-Company Support:** With company-specific configurations stored in the `Company` model.
    *   **Filament Integration:** UI delegates to Actions/Services.
    *   **Pest Testing:** Business logic-focused TDD.

**Missing/To Be Added for Comprehensive Inventory:**

1.  **Product Model Extension:** The `products` table needs additional fields for inventory specific configurations.
2.  **Physical Stock Tracking Models:** Currently, there are no explicit models to track physical stock quantities or movements (`stock_moves`, `stock_move_lines`, `stock_locations`). While `StockMoveValuation` is proposed in sources for accounting impact, the physical movement records are essential.
3.  **Cost Layering Model:** `inventory_cost_layers` is needed for FIFO/LIFO valuation.
4.  **Accounting Impact for Stock Moves Model:** `stock_move_valuations` for linking physical movements to their accounting impact.
5.  **Dedicated Inventory Services/Actions:** Core logic for processing incoming/outgoing stock, and managing physical adjustments is absent.
6.  **Comprehensive Inventory Reporting:** No specific inventory valuation or quantity reports are currently outlined.

---

### **🛠️ Robust Plan for Inventory Management**

This plan will integrate robust physical inventory tracking with your existing strong accounting engine, addressing Odoo's perpetual inventory features and enhancing auditability and control.

#### **Phase 1: Database Schema Enhancements & New Models**

This phase focuses on defining the necessary data structures for both physical and financial inventory tracking.

*   **Update Migrations:** Generate new migrations to add or modify tables.
    *   **Update `products` table migration:**
        *   Add `inventory_valuation_method` (string, e.g., 'fifo', 'lifo', 'avco', 'standard_price'). Use a backed Enum for this.
        *   Add `default_inventory_account_id` (FK to `accounts.id`).
        *   Add `default_cogs_account_id` (FK to `accounts.id`).
        *   Add `default_stock_input_account_id` (FK to `accounts.id`, for Anglo-Saxon accounting).
        *   Add `default_price_difference_account_id` (FK to `accounts.id`, for AVCO variances).
        *   Add `average_cost` (Decimal, default 0.00, for AVCO method).
        *   Run the new migrations.
    *   **Create `stock_locations` table migration:** To support multiple inventories/warehouses.
        *   `id`: Primary Key.
        *   `company_id`: FK to `companies.id`.
        *   `name`: String (e.g., 'Warehouse A', 'Production Line').
        *   `type`: String (e.g., 'Internal', 'Customer', 'Vendor', 'Inventory Adjustment'). Use a backed Enum.
        *   `is_active`: Boolean, default true.
        *   `parent_id`: Nullable FK to `stock_locations.id` (for hierarchical locations).
        *   `created_at`, `updated_at`: Timestamps.
    *   **Create `stock_moves` table migration:** To record all physical inventory movements.
        *   `id`: Primary Key.
        *   `company_id`: FK to `companies.id`.
        *   `product_id`: FK to `products.id`.
        *   `quantity`: Decimal.
        *   `from_location_id`: FK to `stock_locations.id`.
        *   `to_location_id`: FK to `stock_locations.id`.
        *   `move_type`: String (e.g., 'incoming', 'outgoing', 'internal_transfer', 'adjustment'). Use a backed Enum.
        *   `status`: String (e.g., 'draft', 'confirmed', 'done', 'cancelled'). Use a backed Enum.
        *   `move_date`: Date.
        *   `reference`: String (e.g., PO-001, SO-002, INV-ADJ-001).
        *   `source_type`, `source_id`: Polymorphic relation to originating document (e.g., `VendorBill`, `Invoice`).
        *   `created_at`, `updated_at`: Timestamps.
        *   `created_by_user_id`: FK to `users.id` (for auditability).
    *   **Create `inventory_cost_layers` table migration:** For FIFO/LIFO tracking.
        *   `id`: Primary Key.
        *   `product_id`: FK to `products.id`.
        *   `quantity`: Decimal – quantity received in this layer.
        *   `cost_per_unit`: Decimal – cost of items in this layer (use MoneyCast).
        *   `remaining_quantity`: Decimal – how much of this layer is left.
        *   `purchase_date`: Date – when this layer was acquired.
        *   `source_type`, `source_id`: Polymorphic relation to originating purchase (e.g., `VendorBill`).
        *   `created_at`, `updated_at`: Timestamps.
    *   **Create `stock_move_valuations` table migration:** To record the accounting impact of each physical stock move.
        *   `id`: Primary Key.
        *   `company_id`: FK to `companies.id`.
        *   `product_id`: FK to `products.id`.
        *   `stock_move_id`: FK to `stock_moves.id`.
        *   `quantity`: Decimal – quantity moved.
        *   `cost_impact`: Decimal – total cost impact (e.g., COGS for sale, value increase for purchase) (use MoneyCast).
        *   `valuation_method`: String – the method applied for this specific move (e.g., 'fifo', 'avco').
        *   `move_type`: String ('inbound', 'outbound', 'adjustment').
        *   `journal_entry_id`: Nullable FK to `journal_entries.id`.
        *   `source_type`, `source_id`: Polymorphic relation to the originating stock move/invoice/bill.
        *   `created_at`, `updated_at`: Timestamps.

*   **Update Models:**
    *   **`Product.php`:** Add fillable/guarded properties for new columns, define relationships to accounts, and implement `MoneyCast` for `average_cost`.
    *   **`StockLocation.php`:** Define relationships to `Company` and `parent` (self-referencing).
    *   **`StockMove.php`:** Define relationships to `Company`, `Product`, `fromLocation`, `toLocation`, `Source` (polymorphic), and `StockMoveValuation`.
    *   **`InventoryCostLayer.php`:** Define relationships to `Product` and `Source` (polymorphic), implement `MoneyCast` for `cost_per_unit`.
    *   **`StockMoveValuation.php`:** Define relationships to `Company`, `Product`, `StockMove`, `JournalEntry`, and `Source` (polymorphic), implement `MoneyCast` for `cost_impact`.

#### **Phase 2: Core Business Logic (Service & Actions Layer)**

This phase implements the complex logic for managing physical stock and its accounting valuation. Adhere strictly to the Actions/DTOs pattern.

*   **Data Transfer Objects (DTOs):**
    *   `App/DataTransferObjects/Inventory/CreateStockMoveDTO.php`: For creating new stock movements (e.g., from purchase receipt or sales delivery).
    *   `App/DataTransferObjects/Inventory/UpdateStockMoveDTO.php`: For modifying draft stock moves.
    *   `App/DataTransferObjects/Inventory/ConfirmStockMoveDTO.php`: For confirming stock moves, triggering physical and accounting updates.
    *   `App/DataTransferObjects/Inventory/AdjustInventoryDTO.php`: For manual inventory adjustments (e.g., discrepancies, damages).

*   **Services:**
    *   `App/Services/Inventory/StockMoveService.php`:
        *   **Purpose:** Manages the lifecycle of physical stock movements. It orchestrates the creation, modification, and confirmation of `StockMove` records.
        *   **Methods:**
            *   `createMove(CreateStockMoveDTO $dto): StockMove`: Creates a new `StockMove` in `draft` status.
            *   `updateMove(StockMove $move, UpdateStockMoveDTO $dto): StockMove`: Updates a `draft` stock move.
            *   `confirmMove(ConfirmStockMoveDTO $dto): StockMove`: Changes `StockMove` status to `confirmed` or `done`. This method will *dispatch events* for quantity and accounting updates.
            *   `cancelMove(StockMove $move): StockMove`: Changes `StockMove` status to `cancelled`, possibly reversing physical quantity changes if not already `done`.

    *   `App/Services/Inventory/InventoryValuationService.php`:
        *   **Purpose:** Manages the accounting impact of stock movements. It calculates costs based on valuation methods and generates corresponding Journal Entries.
        *   **Methods:**
            *   `processIncomingStock(Product $product, float $quantity, Money $costPerUnit, Carbon $date, $sourceDocument)`:
                *   **For AVCO:** Recalculates `Product`'s `average_cost` using weighted average.
                *   **For FIFO/LIFO:** Creates new `InventoryCostLayer` records.
                *   Generates `JournalEntry` (Debit: Inventory, Credit: Stock Input/Accounts Payable).
                *   Applies cryptographic hashing to the `JournalEntry`.
                *   Creates `StockMoveValuation` record.
            *   `processOutgoingStock(Product $product, float $quantity, Carbon $date, $sourceDocument)`:
                *   **For AVCO:** Calculates COGS as `quantity * product->average_cost`.
                *   **For FIFO:** Consumes oldest `InventoryCostLayer` records to determine COGS.
                *   **For LIFO:** Consumes newest `InventoryCostLayer` records to determine COGS.
                *   Generates `JournalEntry` (Debit: COGS, Credit: Inventory).
                *   Applies cryptographic hashing to the `JournalEntry`.
                *   Creates `StockMoveValuation` record.
            *   `adjustInventoryValue(AdjustInventoryDTO $dto)`: For accounting adjustments due to write-offs, revaluations, etc., generating `JournalEntry` and `StockMoveValuation` records.

*   **Actions (following Command Pattern):**
    *   `App/Actions/Inventory/CreateStockMoveAction.php`: `execute(CreateStockMoveDTO $dto)` -> uses `StockMoveService::createMove`.
    *   `App/Actions/Inventory/UpdateStockMoveAction.php`: `execute(UpdateStockMoveDTO $dto)` -> uses `StockMoveService::updateMove`.
    *   `App/Actions/Inventory/ConfirmStockMoveAction.php`: `execute(ConfirmStockMoveDTO $dto)` -> uses `StockMoveService::confirmMove`.
    *   `App/Actions/Inventory/AdjustInventoryAction.php`: `execute(AdjustInventoryDTO $dto)` -> uses `InventoryValuationService::adjustInventoryValue`.
    *   `App/Actions/Inventory/ProcessIncomingStockAction.php`: `execute(StockMove $stockMove)` -> triggered by `StockMoveConfirmed` event, calls `InventoryValuationService::processIncomingStock`.
    *   `App/Actions/Inventory/ProcessOutgoingStockAction.php`: `execute(StockMove $stockMove)` -> triggered by `StockMoveConfirmed` event, calls `InventoryValuationService::processOutgoingStock`.

#### **Phase 3: Integration & Automation**

Seamless integration with existing workflows and background processing.

*   **Events & Listeners:**
    *   `App/Events/Inventory/StockMoveConfirmed.php`: Dispatched when a `StockMove` transitions to `confirmed` or `done` status. Contains `StockMove` instance.
    *   `App/Listeners/Inventory/HandleStockMoveConfirmation.php`: Subscribes to `StockMoveConfirmed`.
        *   Its `handle` method dispatches a queued job for `ProcessIncomingStockAction` or `ProcessOutgoingStockAction` based on `move_type`.
        *   **Crucial:** Ensure database transaction awareness for queued jobs (`ShouldQueue` and `AfterCommit` interface) to prevent race conditions. This is a core strength of your current application.

*   **Queued Jobs:**
    *   `App/Jobs/Inventory/ProcessIncomingStockJob.php`: Calls `ProcessIncomingStockAction::execute()`.
    *   `App/Jobs/Inventory/ProcessOutgoingStockJob.php`: Calls `ProcessOutgoingStockAction::execute()`.

*   **Integration with Existing Workflows:**
    *   **Purchases (`VendorBill`):** When a `VendorBill` for a "Storable Product" is confirmed:
        *   Modify `VendorBillService::post()` (or a new action `PostVendorBillAction`) to trigger `CreateStockMoveAction` for an `incoming` stock move.
        *   The DTO for this `CreateStockMoveAction` would link to the `VendorBill` via polymorphic fields (`source_type`, `source_id`).
        *   This `StockMove` would initially be in a `draft` or `pending` status. A separate process (e.g., warehouse user confirming receipt in Filament) or an automated step based on the bill confirmation could then `confirmMove`, which triggers the valuation.
    *   **Sales (`Invoice`):** When a `Invoice` for a "Storable Product" is confirmed:
        *   Modify `InvoiceService::post()` (or a new action `PostInvoiceAction`) to trigger `CreateStockMoveAction` for an `outgoing` stock move.
        *   The DTO for this `CreateStockMoveAction` would link to the `Invoice`.
        *   Similarly, this `StockMove` would be `confirmed` later (e.g., warehouse user confirming delivery in Filament), triggering the COGS valuation.

#### **Phase 4: Enhancements & Gap Fixes (Better than Odoo)**

This phase focuses on improving aspects based on Odoo's patterns and adding a more granular level of control and auditability tailored to your existing architecture.

*   **Explicit Physical vs. Accounting:** Your architecture explicitly separates `StockMove` (physical) from `StockMoveValuation` (accounting impact). This provides a clearer audit trail and more flexible control over physical logistics versus financial adjustments, which can sometimes be entangled in monolithic systems.
*   **Comprehensive Stock Movement Audit Trail:**
    *   Every `StockMove` will have `created_by_user_id` and timestamps, along with explicit `from_location_id` and `to_location_id`.
    *   Implement an `AuditLogObserver` for `StockMove` to track status changes and modifications, extending your existing audit capabilities.
*   **Flexible Inventory Adjustments (Manual & Automated):**
    *   Beyond purchase/sale, provide dedicated `AdjustInventoryAction` for manual quantity corrections (e.g., spoilage, theft, physical count discrepancies). These actions should generate corresponding `JournalEntry` via `InventoryValuationService` to debit/credit a `Inventory Adjustment Expense/Income` account.
    *   The system should log *all* adjustments with a clear reason.
*   **Multi-Location/Warehouse Support:** The `stock_locations` table allows for granular tracking of inventory across multiple physical locations, aligning with Odoo's capability. `StockMove` records will explicitly link these locations.
*   **Lock Date Enforcement:** Ensure that `InventoryValuationService` methods, particularly those generating `JournalEntry` records, respect your existing `LockDateService` and `AccountingValidationService` to prevent posting in locked accounting periods.
*   **Enhanced Reporting (Leveraging existing data):**
    *   **Inventory Valuation Report:** Sum `InventoryCostLayer` or apply `Product.average_cost` across all `Product` records, filtered by `StockLocation` if desired, for a real-time Balance Sheet valuation.
    *   **Cost of Goods Sold (COGS) Report:** Aggregate `StockMoveValuation` records for `outgoing` moves, grouped by `Product` and period.
    *   **Stock On Hand Report:** Sum `quantity` from `StockMove` records, grouped by `Product` and `StockLocation`, to derive current physical quantities. This requires efficient calculation (e.g., materialized views or cached aggregates for large datasets).

#### **Phase 5: Testing Strategy (Pest)**

Rigorous TDD is paramount for an accounting system.

*   **Unit Tests (`tests/Unit/`):**
    *   Test `StockMoveService` methods in isolation for their core logic (e.g., `createMove`, `confirmMove` state transitions).
    *   Test `InventoryValuationService` methods for correct cost calculations (AVCO, FIFO, LIFO) given specific inputs.
    *   Test `StockLocation` hierarchy and relationships.
*   **Feature Tests (`tests/Feature/`):**
    *   **Model CRUD & Relationships:** `test('product can be configured with different inventory valuation methods')`, `test('stock locations can be created and have a hierarchy')`, `test('inventory cost layers are created for FIFO/LIFO products')`, `test('stock move valuations are recorded for all movements')`.
    *   **Core Workflows:**
        *   `test('purchasing storable product creates incoming stock move and correct inventory value JE')`: Simulate `VendorBill` confirmation, assert `StockMove` creation (in `draft`), and then confirming the `StockMove` triggers `InventoryValuationService` to debit `Inventory` and credit `Stock Input/Accounts Payable`, creating a hashed `JournalEntry` and `StockMoveValuation`.
        *   `test('selling storable product creates outgoing stock move and correct COGS JE')`: Simulate `Invoice` confirmation, assert `StockMove` creation, and then confirming `StockMove` triggers `InventoryValuationService` to debit `COGS` and credit `Inventory`, creating a hashed `JournalEntry` and `StockMoveValuation`.
        *   `test('manual inventory adjustment correctly updates stock and generates JE')`: Test `AdjustInventoryAction` for adding/removing stock, asserting the correct `StockMove` and `JournalEntry` for adjustment expense/income are created.
    *   **Valuation Method Specifics:**
        *   `test('AVCO correctly updates product average cost on incoming stock')`.
        *   `test('FIFO consumes oldest layers and calculates COGS correctly on outgoing stock')`.
        *   `test('LIFO consumes newest layers and calculates COGS correctly on outgoing stock')`.
    *   **Immutability:**
        *   `test('posted inventory valuation journal entries are immutable and hashed')`: Attempt to directly modify/delete `JournalEntry` or `StockMoveValuation` records linked to posted inventory transactions and assert `UpdateNotAllowedException`/`DeletionNotAllowedException`.
        *   Verify `hash` and `previous_hash` fields are populated for all generated `JournalEntry` records related to inventory.
    *   **Edge Cases:**
        *   `test('lock dates prevent inventory entries in locked periods')`.
        *   `test('negative stock handling (if allowed by business rule)')`.

By meticulously implementing and testing this plan, leveraging your established Laravel patterns, you will build an inventory management system that not only rivals Odoo's robust perpetual inventory and valuation capabilities but also enhances the auditability and flexibility crucial for your specific headless, manual data entry environment in Iraq.
