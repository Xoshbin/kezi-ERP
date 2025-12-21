# JMeryar ERP - Purchase Order, Sales Order & Inventory Management Scenario

> [!NOTE]
> **Pre-requisites**: Run `php artisan migrate:fresh --seed` to load the base data.

## 📋 Scenario Overview
| Field | Value |
|-------|-------|
| **Company** | Jmeryar Solutions (Seeded) |
| **Base Currency** | IQD (Iraqi Dinar) |
| **Period** | February 2024 |
| **Focus** | End-to-end Purchase → Inventory → Sales flow with cost tracking |

---

## Phase 1: Capital Injection (Day 1)
**Goal**: Inject initial funding into the company to enable operations.

*   **Navigate**: Accounting > Journal Entries > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Journal | `Opening Balance` (OPEN) |
    | Date | 2024-02-01 |
    | Reference | `CAPITAL-FEB-01` |
    | **Line 1** | Dr `110202 - Cash (IQD)` — 100,000,000 |
    | **Line 2** | Cr `310101 - Share Capital` — 100,000,000 |
*   **Action**: Post.
*   **📊 Accountant's Validation**:
    - Trial Balance: Cash = 100M (Debit), Share Capital = 100M (Credit)
    - Balance Sheet: Total Assets = 100M, Total Equity = 100M
    - Verify: Debits = Credits

---

## Phase 2: Purchase Order Creation (Day 2)
**Goal**: Create a Purchase Order for inventory procurement.

### 2.1. Create Purchase Order
*   **Navigate**: Purchases > Purchase Orders > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Vendor | `Paykar Tech Supplies` (Seeded Vendor) |
    | PO Date | 2024-02-02 |
    | Expected Delivery | 2024-02-05 |
    | Reference | `PO-GPU-FEB` |
    | **Line 1** | Product: `NVIDIA RTX 4090 Graphics Card`, Qty: 10, Price: 2,000,000 IQD |
    | **Line 2** | Product: `DDR5 32GB Memory Module`, Qty: 50, Price: 350,000 IQD |
    | **Line 3** | Product: `2TB NVMe SSD Drive`, Qty: 30, Price: 250,000 IQD |
*   **Calculated Totals**:
    | Line | Qty | Unit Price | Subtotal |
    |------|-----|------------|----------|
    | RTX 4090 | 10 | 2,000,000 | 20,000,000 IQD |
    | DDR5 32GB | 50 | 350,000 | 17,500,000 IQD |
    | NVMe SSD | 30 | 250,000 | 7,500,000 IQD |
    | **Total** | | | **45,000,000 IQD** |
*   **Action**: Save (Status: `Draft`)
*   **📊 Accountant's Validation**:
    - No journal entries yet (PO is commitment, not accrued)
    - Inventory unchanged
    - Cash unchanged at 100M

### 2.2. Confirm Purchase Order
*   **Navigate**: Open PO > **Confirm** action
*   **Expected Status Change**: `Draft` → `Confirmed` → `To Receive`
*   **📊 Accountant's Validation**:
    - Still no journal entries (confirmation is operational, not financial)
    - PO status = `To Receive`
    - This is a *commitment* only, not a liability

---

## Phase 3: Goods Receipt / Stock Move (Day 5)
**Goal**: Receive goods from vendor and update inventory.

### 3.1. Receive Goods (Full Receipt)
*   **Navigate**: Inventory > Stock Moves > **New** OR from PO > **Receive Goods**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Purchase Order | Select the PO from Phase 2 |
    | Receipt Date | 2024-02-05 |
    | Source Location | `Vendor` |
    | Destination Location | `Warehouse` |
*   **Line Data** (all quantities received):
    | Product | Qty Received | Unit Cost |
    |---------|--------------|-----------|
    | RTX 4090 | 10 | 2,000,000 |
    | DDR5 32GB | 50 | 350,000 |
    | NVMe SSD | 30 | 250,000 |
*   **Action**: Confirm Stock Move
*   **Expected PO Status Change**: `To Receive` → `Fully Received` → `To Bill`
*   **📊 Accountant's Validation**:
    - **Journal Entry Created** (Accrual):
      - Dr `130102 - Inventory Asset (IQD)` — 45,000,000 IQD
      - Cr `210202 - Stock Input Account (IQD)` — 45,000,000 IQD
    - **Inventory Valuation**:
      | Product | Qty | Unit Cost | Total Value |
      |---------|-----|-----------|-------------|
      | RTX 4090 (FIFO) | 10 | 2,000,000 | 20,000,000 |
      | DDR5 32GB (AVCO) | 50 | 350,000 | 17,500,000 |
      | NVMe SSD (LIFO) | 30 | 250,000 | 7,500,000 |
      | **Total** | | | **45,000,000** |
    - Cash still at 100M (no payment yet)

---

## Phase 4: Vendor Bill & Payment (Day 6-7)
**Goal**: Record vendor bill and settle payables.

### 4.1. Create Vendor Bill from PO (Day 6)
*   **Navigate**: Open PO > **Create Bill** action OR Accounting > Vendor Bills > New
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Purchase Order | Auto-linked |
    | Vendor | `Paykar Tech Supplies` |
    | Bill Date | 2024-02-06 |
    | Due Date | 2024-02-20 |
    | Reference | `VB-PO-GPU-FEB` |
*   **Lines auto-populated from PO**
*   **Action**: Post / Confirm Bill
*   **Expected PO Status Change**: `To Bill` → `Partially Billed` or `Fully Billed`
*   **📊 Accountant's Validation**:
    - **Journal Entry** (Bill confirmation clears accrual):
      - Dr `210202 - Stock Input Account (IQD)` — 45,000,000 IQD
      - Cr `Accounts Payable - Paykar Tech Supplies` — 45,000,000 IQD
    - Payables increased by 45M
    - Inventory Asset unchanged (already recognized at receipt)

### 4.2. Pay Vendor Bill (Day 7)
*   **Navigate**: Open Vendor Bill > **Register Payment**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Journal | `Cash (IQD)` (CSH-IQD) |
    | Amount | 45,000,000 IQD |
    | Payment Date | 2024-02-07 |
*   **Action**: Confirm Payment
*   **📊 Accountant's Validation**:
    - **Journal Entry**:
      - Dr `Accounts Payable - Paykar Tech Supplies` — 45,000,000 IQD
      - Cr `110202 - Cash (IQD)` — 45,000,000 IQD
    - Bill Status = `Paid`
    - Cash = 55,000,000 IQD (100M - 45M)
    - Payables = 0

---

## Phase 5: Sales Order Creation (Day 10)
**Goal**: Create a Sales Order for customer.

### 5.1. Create Sales Order
*   **Navigate**: Sales > Sales Orders > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Customer | `Hawre Trading Group` (Seeded Customer) |
    | SO Date | 2024-02-10 |
    | Expected Delivery | 2024-02-12 |
    | Reference | `SO-GPU-FEB` |
    | **Line 1** | Product: `NVIDIA RTX 4090 Graphics Card`, Qty: 5, Price: 2,500,000 IQD |
    | **Line 2** | Product: `DDR5 32GB Memory Module`, Qty: 25, Price: 450,000 IQD |
    | **Line 3** | Product: `2TB NVMe SSD Drive`, Qty: 15, Price: 320,000 IQD |
*   **Calculated Totals**:
    | Line | Qty | Unit Price | Subtotal |
    |------|-----|------------|----------|
    | RTX 4090 | 5 | 2,500,000 | 12,500,000 IQD |
    | DDR5 32GB | 25 | 450,000 | 11,250,000 IQD |
    | NVMe SSD | 15 | 320,000 | 4,800,000 IQD |
    | **Total** | | | **28,550,000 IQD** |
*   **Action**: Save (Status: `Draft`)
*   **📊 Accountant's Validation**:
    - No journal entries yet
    - Inventory unchanged
    - Cash at 55M

### 5.2. Confirm Sales Order
*   **Navigate**: Open SO > **Confirm** action
*   **Expected Status Change**: `Draft` → `Confirmed` → `To Deliver`
*   **📊 Accountant's Validation**:
    - Still no journal entries (confirmation reserves goods, no revenue yet)
    - SO status = `To Deliver`

---

## Phase 6: Goods Delivery (Day 12)
**Goal**: Deliver goods to customer and record outgoing inventory.

### 6.1. Deliver Goods (Full Delivery)
*   **Navigate**: Inventory > Stock Moves > **New** OR from SO > **Deliver Goods**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Sales Order | Select the SO from Phase 5 |
    | Delivery Date | 2024-02-12 |
    | Source Location | `Warehouse` |
    | Destination Location | `Customer` |
*   **Line Data** (all quantities delivered):
    | Product | Qty Delivered | Unit Cost (from inventory) |
    |---------|---------------|----------------------------|
    | RTX 4090 (FIFO) | 5 | 2,000,000 |
    | DDR5 32GB (AVCO) | 25 | 350,000 |
    | NVMe SSD (LIFO) | 15 | 250,000 |
*   **COGS Calculation**:
    | Product | Qty | Unit Cost | COGS |
    |---------|-----|-----------|------|
    | RTX 4090 | 5 | 2,000,000 | 10,000,000 |
    | DDR5 32GB | 25 | 350,000 | 8,750,000 |
    | NVMe SSD | 15 | 250,000 | 3,750,000 |
    | **Total COGS** | | | **22,500,000** |
*   **Action**: Confirm Stock Move
*   **Expected SO Status Change**: `To Deliver` → `Fully Delivered` → `To Invoice`
*   **📊 Accountant's Validation**:
    - **Journal Entry** (COGS Recognition):
      - Dr `500100 - Cost of Revenue` — 22,500,000 IQD
      - Cr `130102 - Inventory Asset (IQD)` — 22,500,000 IQD
    - **Remaining Inventory**:
      | Product | Qty | Unit Cost | Total Value |
      |---------|-----|-----------|-------------|
      | RTX 4090 | 5 | 2,000,000 | 10,000,000 |
      | DDR5 32GB | 25 | 350,000 | 8,750,000 |
      | NVMe SSD | 15 | 250,000 | 3,750,000 |
      | **Total** | | | **22,500,000** |

---

## Phase 7: Customer Invoice & Payment (Day 13-15)
**Goal**: Invoice customer and collect receivables.

### 7.1. Create Invoice from SO (Day 13)
*   **Navigate**: Open SO > **Create Invoice** action OR Accounting > Invoices > New
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Sales Order | Auto-linked |
    | Customer | `Hawre Trading Group` |
    | Invoice Date | 2024-02-13 |
    | Due Date | 2024-02-28 |
    | Reference | `INV-SO-GPU-FEB` |
*   **Lines auto-populated from SO**
*   **Action**: Post / Confirm Invoice
*   **Expected SO Status Change**: `To Invoice` → `Fully Invoiced`
*   **📊 Accountant's Validation**:
    - **Journal Entry** (Revenue Recognition):
      - Dr `Accounts Receivable - Hawre Trading Group` — 28,550,000 IQD
      - Cr `410102 - Sales Revenue (IQD)` — 28,550,000 IQD
    - Receivables increased by 28.55M
    - Revenue recognized

### 7.2. Receive Customer Payment (Day 15)
*   **Navigate**: Open Invoice > **Register Payment**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Journal | `Cash (IQD)` (CSH-IQD) |
    | Amount | 28,550,000 IQD |
    | Payment Date | 2024-02-15 |
*   **Action**: Confirm Payment
*   **📊 Accountant's Validation**:
    - **Journal Entry**:
      - Dr `110202 - Cash (IQD)` — 28,550,000 IQD
      - Cr `Accounts Receivable - Hawre Trading Group` — 28,550,000 IQD
    - Invoice Status = `Paid`
    - Cash = 83,550,000 IQD (55M + 28.55M)
    - Receivables = 0

---

## Phase 8: Partial Delivery Scenario (Day 18-22)
**Goal**: Test partial delivery and invoice workflows.

### 8.1. Create Second Sales Order (Day 18)
*   **Navigate**: Sales > Sales Orders > **New**
*   **Data**:
    | Field | Value |
    |-------|-------|
    | Customer | `Zryan Tech Store` (Seeded Customer) |
    | SO Date | 2024-02-18 |
    | **Line 1** | Product: `NVIDIA RTX 4090 Graphics Card`, Qty: 4, Price: 2,600,000 IQD |
    | **Total** | | 10,400,000 IQD |
*   **Action**: Save & Confirm
*   **SO Status**: `To Deliver`

### 8.2. Partial Delivery (Day 19) - Deliver 2 of 4
*   **Navigate**: SO > **Deliver Goods**
*   **Data**: Deliver only 2 units of RTX 4090
*   **Action**: Confirm
*   **📊 Accountant's Validation**:
    - **COGS Journal Entry** (for 2 units):
      - Dr `500100 - Cost of Revenue` — 4,000,000 IQD
      - Cr `130102 - Inventory Asset (IQD)` — 4,000,000 IQD
    - SO Status = `Partially Delivered`
    - Remaining Inventory RTX 4090 = 3 units

### 8.3. Partial Invoice (Day 20) - Invoice Delivered Qty
*   **Navigate**: SO > **Create Invoice** (for delivered qty only)
*   **Data**: Invoice for 2 units @ 2,600,000
*   **📊 Accountant's Validation**:
    - **Revenue Journal Entry**:
      - Dr `Accounts Receivable - Zryan Tech Store` — 5,200,000 IQD
      - Cr `410102 - Sales Revenue (IQD)` — 5,200,000 IQD
    - SO Status = `Partially Invoiced`

### 8.4. Complete Delivery (Day 21) - Deliver Remaining 2
*   **Navigate**: SO > **Deliver Goods**
*   **Data**: Deliver remaining 2 units
*   **📊 Accountant's Validation**:
    - **COGS Journal Entry**:
      - Dr `500100 - Cost of Revenue` — 4,000,000 IQD
      - Cr `130102 - Inventory Asset (IQD)` — 4,000,000 IQD
    - SO Status = `Fully Delivered` but still `Partially Invoiced`
    - Remaining Inventory RTX 4090 = 1 unit

### 8.5. Final Invoice & Payment (Day 22)
*   **Navigate**: SO > **Create Invoice** (for remaining qty)
*   **Data**: Invoice for 2 units @ 2,600,000 = 5,200,000 IQD
*   **Action**: Post Invoice, then Register Payment for total 10,400,000 IQD
*   **📊 Accountant's Validation**:
    - Receivables = 0
    - SO Status = `Fully Invoiced` → `Done`
    - Cash += 10,400,000

---

## Phase 9: Financial Reporting & Analysis (Month End)
**Goal**: Generate reports and validate financial position.

### 9.1. Trial Balance
*   **Navigate**: Reports > Trial Balance
*   **Expected Key Balances**:
    | Account | Debit | Credit |
    |---------|-------|--------|
    | Cash (IQD) | 93,950,000 | |
    | Inventory Asset | 18,500,000 | |
    | Accounts Payable | | 0 |
    | Accounts Receivable | 0 | |
    | Share Capital | | 100,000,000 |
    | Sales Revenue | | 33,750,000 |
    | Cost of Revenue | 30,500,000 | |
*   **Validation**: Total Debits = Total Credits

### 9.2. Profit & Loss Statement
*   **Navigate**: Reports > Profit & Loss
*   **Expected Structure**:
    | Item | Amount (IQD) |
    |------|--------------|
    | **Sales Revenue** | 33,750,000 |
    | (-) Cost of Goods Sold | (30,500,000) |
    | **Gross Profit** | 3,250,000 |
    | **Net Profit** | 3,250,000 |
*   **Gross Margin**: 9.6%

### 9.3. Inventory Valuation Report
*   **Navigate**: Inventory > Reports > Valuation
*   **Expected**:
    | Product | Qty | Valuation Method | Total Value |
    |---------|-----|------------------|-------------|
    | RTX 4090 | 1 | FIFO | 2,000,000 |
    | DDR5 32GB | 25 | AVCO | 8,750,000 |
    | NVMe SSD | 15 | LIFO | 3,750,000 |
    | **Total** | | | **14,500,000** |

### 9.4. Balance Sheet
*   **Navigate**: Reports > Balance Sheet
*   **Expected Structure**:
    | **ASSETS** | |
    |------------|------------|
    | Cash | 93,950,000 |
    | Inventory | 14,500,000 |
    | **Total Assets** | **108,450,000** |
    | **LIABILITIES & EQUITY** | |
    | Share Capital | 100,000,000 |
    | Current Year Earnings | 8,450,000 |
    | **Total L&E** | **108,450,000** |
*   **Validation**: Assets = Liabilities + Equity

---

## ✅ Success Criteria

| Phase | Checkpoint | Expected Outcome |
|-------|------------|------------------|
| 1 | Capital Injection | Cash = 100M, JE posted |
| 2 | PO Creation & Confirmation | PO status = `To Receive` |
| 3 | Goods Receipt | Inventory = 45M, Stock Move confirmed |
| 4 | Vendor Bill & Payment | Cash = 55M, AP = 0 |
| 5 | SO Creation & Confirmation | SO status = `To Deliver` |
| 6 | Goods Delivery | COGS = 22.5M, Inventory reduced |
| 7 | Invoice & Payment | Cash = 83.55M, Revenue = 28.55M |
| 8 | Partial Flows | Partial delivery/invoice states work correctly |
| 9 | Reports | Trial Balance balances, P&L shows profit |

---

## 🔍 Key Accounting Principles Validated

1. **Accrual Accounting**: Inventory recognized at receipt, not payment
2. **Matching Principle**: COGS matched with Revenue in same period
3. **Double-Entry**: Every transaction has balanced debits and credits
4. **Cost Flow Methods**: FIFO, AVCO, LIFO properly applied per product
5. **Document Lifecycle**: PO/SO status transitions correctly tracked
6. **Separation of Concerns**: Physical (stock moves) vs Financial (bills/invoices) properly separated
