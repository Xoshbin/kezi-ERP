# JMeryar ERP - Manual Test Scenario

> [!NOTE]
> **Pre-requisites**: Run `php artisan migrate:fresh --seed` to load the base data.

## 📋 Scenario Overview
| Field | Value |
|-------|-------|
| **Company** | Jmeryar Solutions (Seeded) |
| **Base Currency** | IQD (Iraqi Dinar) |
| **Period** | January 2024 |

---

## Phase 1: Capital Injection (Day 1)
**Goal**: Inject initial funding into the company.

*   **Navigate**: Accounting > Journal Entries > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Journal | `Opening Balance` (OPEN) |
    | Date | 2024-01-01 |
    | Reference | `CAPITAL-01` |
    | **Line 1** | Dr `110202 - Cash (IQD)` — 50,000,000 |
    | **Line 2** | Cr `310101 - Share Capital` — 50,000,000 |
*   **Action**: Post.
*   **Validation**: Balance Sheet shows Cash = 50M, Equity = 50M.

---

## Phase 2: Procurement (Days 2-3)
**Goal**: Purchase inventory from a vendor.

### 2.1. Create Vendor Bill (Day 2)
*   **Navigate**: Accounting > Vendor Bills > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Vendor | `Paykar Tech Supplies` (Seeded Vendor) |
    | Date | 2024-01-02 |
    | **Line Item** | Product: `DDR5 32GB Memory Module`, Qty: 50, Price: 400,000 |
    | **Total** | 20,000,000 IQD |
*   **Action**: Post.
*   **Validation**: JE: Dr `130102 - Inventory Asset (IQD)` 20M, Cr `Payable` 20M.

### 2.2. Pay Vendor (Day 3)
*   **Navigate**: Open the Bill > Register Payment.
*   **Data**: Journal: `Cash (IQD)` (CSH-IQD), Amount: 20,000,000.
*   **Validation**: Cash = 30M (50M - 20M). Bill status = Paid.

---

## Phase 3: Sales (Days 10-12)
**Goal**: Sell inventory for profit.

### 3.1. Create Invoice (Day 10)
*   **Navigate**: Accounting > Invoices > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Customer | `Hawre Trading Group` (Seeded Customer) |
    | Date | 2024-01-10 |
    | **Line Item** | Product: `DDR5 32GB Memory Module`, Qty: 25, Price: 600,000 |
    | **Total** | 15,000,000 IQD |
*   **Action**: Post.
*   **Validation**: JE: Dr `Receivable` 15M, Cr `410102 - Sales Revenue (IQD)` 15M.

### 3.2. Receive Payment (Day 12)
*   **Navigate**: Open Invoice > Register Payment.
*   **Data**: Journal: `Cash (IQD)`, Amount: 15,000,000.
*   **Validation**: Cash = 45M (30M + 15M). Invoice status = Paid.

---

## Phase 4: Operating Expenses (Day 15)
**Goal**: Record monthly rent.

*   **Navigate**: Accounting > Journal Entries > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Journal | `Miscellaneous Operations` (MISC) |
    | Date | 2024-01-15 |
    | **Line 1** | Dr `530201 - Rent Expense` — 2,000,000 |
    | **Line 2** | Cr `110202 - Cash (IQD)` — 2,000,000 |
*   **Action**: Post.
*   **Validation**: Cash = 43M. P&L: Rent Expense = 2M.

---

## Phase 5: Fixed Assets & Depreciation (Days 20-31)
**Goal**: Acquire an asset and run depreciation.

### 5.1. Purchase Asset (Day 20)
*   **Navigate**: Accounting > Vendor Bills > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Vendor | `Hiwa Computer Center` (Seeded Vendor) |
    | Account | `150101 - Office Equipment` |
    | Amount | 2,400,000 IQD |
*   **Action**: Post & Pay with `Cash (IQD)`.
*   **Validation**: Cash = 40.6M. Fixed Assets = 2.4M.

### 5.2. Register Asset
*   **Navigate**: Accounting > Assets > **New**
*   **Data**: Name: "Dell Laptop", Value: 2,400,000, Method: Linear / 2 Years.
*   **Action**: Confirm.

### 5.3. Run Depreciation (Month End)
*   **Navigate**: Accounting > Assets > **Generate Entries**
*   **Target Date**: 2024-01-31.
*   **Validation**: JE: Dr `530301 - Depreciation Expense` 100k, Cr `150199 - Acc. Depreciation` 100k.

---

## Phase 6: Multi-Currency (Days 25-28)
**Goal**: Test FX gain/loss.

### 6.1. USD Invoice (Day 25)
*   **Navigate**: Accounting > Invoices > **New**
*   **Data**: Customer: `Zryan Tech Store`, Currency: **USD**, Amount: $1,000.
*   **Exchange Rate**: 1 USD = 1,500 IQD.
*   **Action**: Post.
*   **Validation**: Receivable records 1,500,000 IQD.

### 6.2. USD Payment (Day 28)
*   **Action**: Register Payment.
*   **New Rate**: 1 USD = 1,520 IQD.
*   **Validation**: FX Gain of 20,000 IQD recorded.

---

## Phase 7: Reporting & Period Lock

### 7.1. Trial Balance
*   **Navigate**: Reports > Trial Balance.
*   **Check**: Total Debits = Total Credits.

### 7.2. Profit & Loss
*   **Expected Structure**:
    | Item | Amount (IQD) |
    |------|--------------|
    | Sales Revenue | 15,000,000 |
    | (-) COGS | (10,000,000) |
    | **Gross Profit** | 5,000,000 |
    | (-) Rent | (2,000,000) |
    | (-) Depreciation | (100,000) |
    | (+) FX Gain | 20,000 |
    | **Net Profit** | ~2,920,000 |

### 7.3. Balance Sheet
*   **Check**: Assets = Liabilities + Equity.
*   Equity includes `Current Year Earnings` ≈ Net Profit.

### 7.4. Lock Period
*   **Navigate**: Accounting > Lock Dates.
*   **Action**: Set Lock Date to 2024-01-31.
*   **Test**: Attempt to edit Day 10 Invoice. **Expected**: Error "Period is Locked".

---

## ✅ Success Criteria
All 7 phases execute without error and reports match expected values.
