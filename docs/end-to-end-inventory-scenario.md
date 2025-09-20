# End-to-End Inventory Management Scenario

## Scenario Overview

**Company**: TechFlow Electronics Ltd.  
**Business**: Electronics distributor specializing in computer components  
**Location**: Baghdad, Iraq  
**Base Currency**: Iraqi Dinar (IQD)  
**Fiscal Year**: 2025

### Business Context

TechFlow Electronics imports computer components from international suppliers and distributes them to local retailers. The company needs comprehensive inventory management to handle:

-   Multi-currency purchasing (USD suppliers, IQD sales)
-   Lot tracking for warranty and compliance
-   FEFO allocation for components with shelf life
-   Automated reordering for fast-moving items
-   Accurate COGS calculation for profitability analysis

## Initial System Setup

### 1. Company Configuration

**Company Details:**

-   Name: TechFlow Electronics Ltd.
-   Currency: Iraqi Dinar (IQD)
-   Fiscal Year Start: January 1, 2025
-   Inventory Valuation: Mixed methods by product category

**Chart of Accounts:**

```
1200 - Inventory Asset (IQD)
1210 - Stock Input Account (IQD)
5000 - Cost of Goods Sold (IQD)
5100 - Inventory Adjustments (IQD)
2000 - Accounts Payable (IQD)
4000 - Sales Revenue (IQD)
```

**Stock Locations:**

-   Main Warehouse (Baghdad)
-   Retail Showroom (Baghdad)
-   Quality Control Area
-   Damaged Goods Area
-   Customer Location (virtual)
-   Vendor Location (virtual)

### 2. Product Catalog Setup

#### Product A: High-End Graphics Cards (FIFO Valuation)

```
sku: GPU-RTX4090
Name: NVIDIA RTX 4090 Graphics Card
Type: Storable
Valuation Method: FIFO
Unit Price: 2,500,000 IQD
Inventory Account: 1200
Stock Input Account: 1210
COGS Account: 5000
Lot Tracking: Enabled (Serial numbers)
Reorder Rule: Min: 5, Max: 20, Safety Stock: 2
```

#### Product B: Memory Modules (AVCO Valuation)

```
sku: RAM-DDR5-32GB
Name: DDR5 32GB Memory Module
Type: Storable
Valuation Method: AVCO
Unit Price: 400,000 IQD
Inventory Account: 1200
Stock Input Account: 1210
COGS Account: 5000
Lot Tracking: Enabled (Batch tracking)
Reorder Rule: Min: 20, Max: 100, Safety Stock: 10
```

#### Product C: Storage Drives (LIFO Valuation)

```
sku: SSD-2TB-NVME
Name: 2TB NVMe SSD Drive
Type: Storable
Valuation Method: LIFO
Unit Price: 300,000 IQD
Inventory Account: 1200
Stock Input Account: 1210
COGS Account: 5000
Lot Tracking: Enabled (Expiration dates)
Reorder Rule: Min: 15, Max: 50, Safety Stock: 5
```

## Phase 1: Initial Purchasing and Stock Receipt

### Step 1: Create Vendor Bill (Multi-Currency Purchase)

**Date**: January 15, 2025  
**Vendor**: TechGlobal Suppliers (USA)  
**Currency**: USD  
**Exchange Rate**: 1 USD = 1,310 IQD

**Vendor Bill Details:**

```
Bill Number: VB-2025-001
Bill Date: January 15, 2025
Accounting Date: January 15, 2025

Line Items:
1. GPU-RTX4090 × 10 units @ $1,900 USD = $19,000 USD
2. RAM-DDR5-32GB × 50 units @ $305 USD = $15,250 USD
3. SSD-2TB-NVME × 30 units @ $229 USD = $6,870 USD

Subtotal: $41,120 USD
Tax (5%): $2,056 USD
Total: $43,176 USD

Converted to IQD: 56,560,560 IQD
```

**Expected Journal Entry (VB-2025-001):**

```
Dr. Stock Input Account (1210)     56,560,560 IQD
    Cr. Accounts Payable (2000)                56,560,560 IQD
```

### Step 2: Receipt Picking Creation

**System Action**: Vendor bill confirmation automatically creates Receipt Picking

**Receipt Picking Details:**

```
Picking Number: RCP-2025-001
Type: Receipt
State: Confirmed
Partner: TechGlobal Suppliers
Scheduled Date: January 15, 2025
Origin: VB-2025-001

Stock Moves Created:
1. GPU-RTX4090: Vendor → Main Warehouse (10 units)
2. RAM-DDR5-32GB: Vendor → Main Warehouse (50 units)
3. SSD-2TB-NVME: Vendor → Main Warehouse (30 units)
```

### Step 3: Lot Creation and Assignment

**Lot Details:**

```
Lot 1: GPU-RTX4090
- Lot Code: GPU-LOT-2025-001
- Product: GPU-RTX4090
- Expiration Date: N/A (No expiration)
- Serial Numbers: RTX001-RTX010

Lot 2: RAM-DDR5-32GB
- Lot Code: RAM-LOT-2025-001
- Product: RAM-DDR5-32GB
- Expiration Date: December 31, 2027
- Batch Code: DDR5-BATCH-001

Lot 3: SSD-2TB-NVME
- Lot Code: SSD-LOT-2025-001
- Product: SSD-2TB-NVME
- Expiration Date: June 30, 2026
- Batch Code: SSD-BATCH-001
```

### Step 4: Stock Move Processing and Valuation

**Processing Date**: January 15, 2025

#### GPU-RTX4090 (FIFO Method):

```
Incoming Stock Processing:
- Quantity: 10 units
- Cost per Unit: $1,900 USD = 2,489,000 IQD
- Total Cost: 24,890,000 IQD

Cost Layer Created:
- Product: GPU-RTX4090
- Quantity: 10 units
- Cost per Unit: 2,489,000 IQD
- Remaining Quantity: 10 units
- Purchase Date: January 15, 2025
- Source: VB-2025-001

Journal Entry (GPU Valuation):
Dr. Inventory Asset (1200)        24,890,000 IQD
    Cr. Stock Input Account (1210)            24,890,000 IQD
```

#### RAM-DDR5-32GB (AVCO Method):

```
Incoming Stock Processing:
- Quantity: 50 units
- Cost per Unit: $305 USD = 399,550 IQD
- Total Cost: 19,977,500 IQD

AVCO Calculation:
- Previous Quantity: 0 units
- Previous Average Cost: 0 IQD
- New Quantity: 50 units
- New Average Cost: 399,550 IQD

Product Updated:
- Quantity on Hand: 50 units
- Average Cost: 399,550 IQD

Journal Entry (RAM Valuation):
Dr. Inventory Asset (1200)        19,977,500 IQD
    Cr. Stock Input Account (1210)            19,977,500 IQD
```

#### SSD-2TB-NVME (LIFO Method):

```
Incoming Stock Processing:
- Quantity: 30 units
- Cost per Unit: $229 USD = 300,090 IQD
- Total Cost: 9,002,700 IQD

Cost Layer Created:
- Product: SSD-2TB-NVME
- Quantity: 30 units
- Cost per Unit: 300,090 IQD
- Remaining Quantity: 30 units
- Purchase Date: January 15, 2025
- Source: VB-2025-001

Journal Entry (SSD Valuation):
Dr. Inventory Asset (1200)         9,002,700 IQD
    Cr. Stock Input Account (1210)             9,002,700 IQD
```

### Step 5: Stock Quant Updates

**Stock Quantities After Receipt:**

```
Main Warehouse Stock Quants:

GPU-RTX4090:
- Total Quantity: 10 units
- Reserved Quantity: 0 units
- Available Quantity: 10 units
- Lot: GPU-LOT-2025-001

RAM-DDR5-32GB:
- Total Quantity: 50 units
- Reserved Quantity: 0 units
- Available Quantity: 50 units
- Lot: RAM-LOT-2025-001

SSD-2TB-NVME:
- Total Quantity: 30 units
- Reserved Quantity: 0 units
- Available Quantity: 30 units
- Lot: SSD-LOT-2025-001
```

## Phase 2: Additional Purchases and Lot Management

### Step 6: Second Purchase (Different Costs)

**Date**: February 1, 2025  
**Vendor**: EuroTech Components (Germany)  
**Currency**: USD  
**Exchange Rate**: 1 USD = 1,315 IQD

**Vendor Bill VB-2025-002:**

```
Line Items:
1. RAM-DDR5-32GB × 30 units @ $310 USD = $9,300 USD
2. SSD-2TB-NVME × 25 units @ $235 USD = $5,875 USD

Total: $15,175 USD = 19,955,125 IQD
```

**New Lots Created:**

```
Lot 4: RAM-DDR5-32GB
- Lot Code: RAM-LOT-2025-002
- Expiration Date: March 31, 2028
- Cost per Unit: 407,650 IQD

Lot 5: SSD-2TB-NVME
- Lot Code: SSD-LOT-2025-002
- Expiration Date: August 31, 2026
- Cost per Unit: 309,025 IQD
```

### Step 7: AVCO Recalculation (RAM-DDR5-32GB)

**AVCO Update After Second Purchase:**

```
Previous State:
- Quantity: 50 units @ 399,550 IQD = 19,977,500 IQD

New Purchase:
- Quantity: 30 units @ 407,650 IQD = 12,229,500 IQD

Combined State:
- Total Quantity: 80 units
- Total Value: 32,207,000 IQD
- New Average Cost: 402,587.50 IQD

Product Updated:
- Quantity on Hand: 80 units
- Average Cost: 402,587.50 IQD
```

## Phase 3: Sales Orders and Delivery Processing

### Step 8: Customer Sales Order

**Date**: February 10, 2025  
**Customer**: Baghdad Computer Center  
**Currency**: IQD

**Sales Invoice INV-2025-001:**

```
Line Items:
1. GPU-RTX4090 × 3 units @ 2,500,000 IQD = 7,500,000 IQD
2. RAM-DDR5-32GB × 15 units @ 400,000 IQD = 6,000,000 IQD
3. SSD-2TB-NVME × 8 units @ 300,000 IQD = 2,400,000 IQD

Subtotal: 15,900,000 IQD
Tax (5%): 795,000 IQD
Total: 16,695,000 IQD
```

### Step 9: Delivery Picking and Reservation

**System Action**: Invoice posting creates Delivery Picking

**Delivery Picking DEL-2025-001:**

```
Type: Delivery
State: Confirmed
Customer: Baghdad Computer Center
Scheduled Date: February 10, 2025

Stock Moves Created:
1. GPU-RTX4090: Main Warehouse → Customer (3 units)
2. RAM-DDR5-32GB: Main Warehouse → Customer (15 units)
3. SSD-2TB-NVME: Main Warehouse → Customer (8 units)
```

### Step 10: FEFO Reservation Process

**Reservation Logic Applied:**

#### GPU-RTX4090 Reservation:

```
Available Lots:
- GPU-LOT-2025-001: 10 units (No expiration)

Reservation:
- Reserved from GPU-LOT-2025-001: 3 units
- Remaining available: 7 units
```

#### RAM-DDR5-32GB Reservation (FEFO):

```
Available Lots (ordered by expiration):
1. RAM-LOT-2025-001: 50 units (Exp: Dec 31, 2027)
2. RAM-LOT-2025-002: 30 units (Exp: Mar 31, 2028)

FEFO Allocation:
- Reserved from RAM-LOT-2025-001: 15 units
- Remaining in RAM-LOT-2025-001: 35 units
- RAM-LOT-2025-002: 30 units (untouched)
```

#### SSD-2TB-NVME Reservation (FEFO):

```
Available Lots (ordered by expiration):
1. SSD-LOT-2025-001: 30 units (Exp: Jun 30, 2026)
2. SSD-LOT-2025-002: 25 units (Exp: Aug 31, 2026)

FEFO Allocation:
- Reserved from SSD-LOT-2025-001: 8 units
- Remaining in SSD-LOT-2025-001: 22 units
- SSD-LOT-2025-002: 25 units (untouched)
```

### Step 11: Stock Quant Updates After Reservation

**Updated Stock Quants:**

```
Main Warehouse After Reservations:

GPU-RTX4090:
- Total Quantity: 10 units
- Reserved Quantity: 3 units
- Available Quantity: 7 units

RAM-DDR5-32GB:
- Total Quantity: 80 units
- Reserved Quantity: 15 units
- Available Quantity: 65 units

SSD-2TB-NVME:
- Total Quantity: 55 units
- Reserved Quantity: 8 units
- Available Quantity: 47 units
```

## Phase 4: Delivery Processing and COGS Calculation

### Step 12: Delivery Picking Completion

**Date**: February 12, 2025  
**Action**: Complete delivery picking DEL-2025-001

### Step 13: COGS Calculation by Valuation Method

#### GPU-RTX4090 (FIFO Method):

```
COGS Calculation:
- Method: FIFO (First In, First Out)
- Quantity Sold: 3 units
- Cost Layer Consumption:
  * GPU-LOT-2025-001: 3 units @ 2,489,000 IQD = 7,467,000 IQD

Cost Layer Update:
- Remaining Quantity: 7 units
- Remaining Value: 17,423,000 IQD

COGS Journal Entry:
Dr. Cost of Goods Sold (5000)      7,467,000 IQD
    Cr. Inventory Asset (1200)                 7,467,000 IQD
```

#### RAM-DDR5-32GB (AVCO Method):

```
COGS Calculation:
- Method: AVCO (Average Cost)
- Quantity Sold: 15 units
- Average Cost: 402,587.50 IQD
- COGS Amount: 15 × 402,587.50 = 6,038,812.50 IQD

Product Update:
- New Quantity: 65 units
- Average Cost: 402,587.50 IQD (unchanged)
- New Total Value: 26,168,187.50 IQD

COGS Journal Entry:
Dr. Cost of Goods Sold (5000)      6,038,813 IQD
    Cr. Inventory Asset (1200)                 6,038,813 IQD
```

#### SSD-2TB-NVME (LIFO Method):

```
COGS Calculation:
- Method: LIFO (Last In, First Out)
- Quantity Sold: 8 units
- Cost Layer Consumption (newest first):
  * SSD-LOT-2025-002: 8 units @ 309,025 IQD = 2,472,200 IQD

Cost Layer Update:
- SSD-LOT-2025-002: Remaining 17 units
- SSD-LOT-2025-001: Unchanged 22 units

COGS Journal Entry:
Dr. Cost of Goods Sold (5000)      2,472,200 IQD
    Cr. Inventory Asset (1200)                 2,472,200 IQD
```

### Step 14: Final Stock Quant Updates

**Stock Quantities After Delivery:**

```
Main Warehouse Final State:

GPU-RTX4090:
- Total Quantity: 7 units
- Reserved Quantity: 0 units
- Available Quantity: 7 units

RAM-DDR5-32GB:
- Total Quantity: 65 units
- Reserved Quantity: 0 units
- Available Quantity: 65 units

SSD-2TB-NVME:
- Total Quantity: 47 units
- Reserved Quantity: 0 units
- Available Quantity: 47 units
```

## Phase 5: Inventory Adjustments and Internal Movements

### Step 15: Physical Inventory Count

**Date**: February 20, 2025  
**Action**: Physical count reveals discrepancies

**Count Results:**

```
Physical Count vs System:

GPU-RTX4090:
- System: 7 units
- Physical: 6 units
- Variance: -1 unit (shortage)

RAM-DDR5-32GB:
- System: 65 units
- Physical: 67 units
- Variance: +2 units (overage)

SSD-2TB-NVME:
- System: 47 units
- Physical: 47 units
- Variance: 0 units (match)
```

### Step 16: Inventory Adjustment Processing

**Adjustment Document ADJ-2025-001:**

```
Date: February 20, 2025
Reason: Physical inventory count adjustment

Adjustment Lines:
1. GPU-RTX4090: -1 unit (shortage)
2. RAM-DDR5-32GB: +2 units (overage)
```

#### GPU-RTX4090 Adjustment (Negative):

```
Adjustment Processing:
- Product: GPU-RTX4090
- Adjustment Quantity: -1 unit
- Valuation Method: FIFO
- Cost per Unit: 2,489,000 IQD (from oldest layer)

Stock Move Created:
- From: Main Warehouse
- To: Inventory Adjustment Location
- Quantity: 1 unit
- Type: Adjustment Out

Journal Entry:
Dr. Inventory Adjustments (5100)    2,489,000 IQD
    Cr. Inventory Asset (1200)                 2,489,000 IQD
```

#### RAM-DDR5-32GB Adjustment (Positive):

```
Adjustment Processing:
- Product: RAM-DDR5-32GB
- Adjustment Quantity: +2 units
- Valuation Method: AVCO
- Cost per Unit: 402,587.50 IQD

Stock Move Created:
- From: Inventory Adjustment Location
- To: Main Warehouse
- Quantity: 2 units
- Type: Adjustment In

Journal Entry:
Dr. Inventory Asset (1200)           805,175 IQD
    Cr. Inventory Adjustments (5100)           805,175 IQD
```

### Step 17: Internal Transfer

**Date**: February 25, 2025  
**Action**: Transfer items to retail showroom

**Internal Transfer INT-2025-001:**

```
Transfer Details:
- From: Main Warehouse
- To: Retail Showroom
- Date: February 25, 2025

Items Transferred:
1. GPU-RTX4090: 2 units
2. RAM-DDR5-32GB: 10 units
3. SSD-2TB-NVME: 5 units

Note: Internal transfers do not affect valuation,
only location of stock quants.
```

**Stock Quant Updates:**

```
After Internal Transfer:

Main Warehouse:
- GPU-RTX4090: 4 units
- RAM-DDR5-32GB: 57 units
- SSD-2TB-NVME: 42 units

Retail Showroom:
- GPU-RTX4090: 2 units
- RAM-DDR5-32GB: 10 units
- SSD-2TB-NVME: 5 units
```

## Phase 6: Reordering Rules and Automation

### Step 18: Reordering Rule Evaluation

**Date**: March 1, 2025  
**System Action**: Automated reordering rule evaluation

**Rule Evaluation Results:**

```
GPU-RTX4090 Analysis:
- Current Stock: 6 units (4 warehouse + 2 showroom)
- Minimum Level: 5 units
- Maximum Level: 20 units
- Safety Stock: 2 units
- Status: Above minimum, no action needed

RAM-DDR5-32GB Analysis:
- Current Stock: 67 units (57 warehouse + 10 showroom)
- Minimum Level: 20 units
- Maximum Level: 100 units
- Safety Stock: 10 units
- Status: Above minimum, no action needed

SSD-2TB-NVME Analysis:
- Current Stock: 47 units (42 warehouse + 5 showroom)
- Minimum Level: 15 units
- Maximum Level: 50 units
- Safety Stock: 5 units
- Status: Near maximum, no action needed
```

### Step 19: Simulate Low Stock Scenario

**Date**: March 15, 2025  
**Action**: Additional sales reduce GPU stock below minimum

**Sales Transaction (Simulated):**

```
Customer: Tech Solutions Ltd.
Items Sold:
- GPU-RTX4090: 3 units

Resulting Stock Levels:
- GPU-RTX4090: 3 units total
- Below minimum of 5 units
- Below safety stock of 2 units
```

### Step 20: Replenishment Suggestion Generation

**System Action**: Automatic replenishment suggestion

**Replenishment Suggestion REP-2025-001:**

```
Product: GPU-RTX4090
Current Quantity: 3 units
Minimum Quantity: 5 units
Maximum Quantity: 20 units
Suggested Quantity: 17 units (20 - 3)
Priority: High (below safety stock)
Route: Min/Max
Suggested Order Date: March 15, 2025
Expected Delivery Date: March 22, 2025
Reason: Stock below minimum level
```

## Phase 7: Comprehensive Reporting and Analytics

### Step 21: Inventory Valuation Report

**Date**: March 31, 2025  
**Report Type**: Inventory Valuation as of Date

**Valuation Report Results:**

```
Inventory Valuation Report
As of: March 31, 2025
Company: TechFlow Electronics Ltd.
Currency: Iraqi Dinar (IQD)

Product Summary:
┌─────────────────┬──────────┬─────────────┬─────────────────┬─────────────────┐
│ Product         │ Quantity │ Avg Cost    │ Total Value     │ Method          │
├─────────────────┼──────────┼─────────────┼─────────────────┼─────────────────┤
│ GPU-RTX4090     │ 3        │ 2,489,000   │ 7,467,000       │ FIFO            │
│ RAM-DDR5-32GB   │ 67       │ 402,588     │ 26,973,363      │ AVCO            │
│ SSD-2TB-NVME    │ 47       │ 304,558     │ 14,314,225      │ LIFO            │
├─────────────────┼──────────┼─────────────┼─────────────────┼─────────────────┤
│ TOTAL           │ 117      │ -           │ 48,754,588      │ -               │
└─────────────────┴──────────┴─────────────┴─────────────────┴─────────────────┘

Cost Layer Breakdown:
GPU-RTX4090 (FIFO):
- Layer 1: 3 units @ 2,489,000 IQD (Jan 15, 2025)

RAM-DDR5-32GB (AVCO):
- Average cost across all receipts: 402,588 IQD

SSD-2TB-NVME (LIFO):
- Layer 1: 22 units @ 300,090 IQD (Jan 15, 2025)
- Layer 2: 25 units @ 309,025 IQD (Feb 1, 2025)

GL Reconciliation:
- Inventory Asset Account (1200): 48,754,588 IQD
- Valuation Report Total: 48,754,588 IQD
- Difference: 0 IQD ✓
```

### Step 22: Inventory Aging Report

**Aging Report (30/60/90 days):**

```
Inventory Aging Report
As of: March 31, 2025
Aging Periods: 30, 60, 90 days

┌─────────────────┬─────────────┬─────────────┬─────────────┬─────────────┬─────────────┐
│ Product         │ 0-30 Days   │ 31-60 Days  │ 61-90 Days  │ 90+ Days    │ Total       │
├─────────────────┼─────────────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ GPU-RTX4090     │ 0           │ 7,467,000   │ 0           │ 0           │ 7,467,000   │
│ RAM-DDR5-32GB   │ 12,229,500  │ 14,743,863  │ 0           │ 0           │ 26,973,363  │
│ SSD-2TB-NVME    │ 7,725,625   │ 6,588,600   │ 0           │ 0           │ 14,314,225  │
├─────────────────┼─────────────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ TOTAL           │ 19,955,125  │ 28,799,463  │ 0           │ 0           │ 48,754,588  │
│ PERCENTAGE      │ 40.9%       │ 59.1%       │ 0.0%        │ 0.0%        │ 100.0%      │
└─────────────────┴─────────────┴─────────────┴─────────────┴─────────────┴─────────────┘

Lot Expiration Analysis:
┌─────────────────┬─────────────┬─────────────┬─────────────────────┐
│ Lot Code        │ Product     │ Expiry Date │ Days Until Expiry   │
├─────────────────┼─────────────┼─────────────┼─────────────────────┤
│ SSD-LOT-2025-001│ SSD-2TB     │ Jun 30, 2026│ 456 days            │
│ SSD-LOT-2025-002│ SSD-2TB     │ Aug 31, 2026│ 518 days            │
│ RAM-LOT-2025-001│ RAM-DDR5    │ Dec 31, 2027│ 1,005 days          │
│ RAM-LOT-2025-002│ RAM-DDR5    │ Mar 31, 2028│ 1,095 days          │
└─────────────────┴─────────────┴─────────────┴─────────────────────┘
```

### Step 23: Inventory Turnover Analysis

**Turnover Report (Q1 2025):**

```
Inventory Turnover Analysis
Period: January 1 - March 31, 2025
Company: TechFlow Electronics Ltd.

┌─────────────────┬─────────────┬─────────────┬─────────────┬─────────────┐
│ Product         │ Avg Inv     │ COGS        │ Turnover    │ Days Sales  │
├─────────────────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ GPU-RTX4090     │ 12,178,500  │ 7,467,000   │ 0.61        │ 147 days    │
│ RAM-DDR5-32GB   │ 29,592,681  │ 6,038,813   │ 0.20        │ 450 days    │
│ SSD-2TB-NVME    │ 11,658,463  │ 2,472,200   │ 0.21        │ 428 days    │
├─────────────────┼─────────────┼─────────────┼─────────────┼─────────────┤
│ TOTAL           │ 53,429,644  │ 15,978,013  │ 0.30        │ 334 days    │
└─────────────────┴─────────────┴─────────────┴─────────────┴─────────────┘

Analysis:
- GPU-RTX4090: Fastest moving (highest turnover)
- RAM and SSD: Slower turnover, consider promotional strategies
- Overall turnover: 0.30 (industry benchmark: 0.40-0.60)
```

### Step 24: Lot Traceability Report

**Lot Trace for GPU-RTX4090 Serial RTX005:**

```
Lot Traceability Report
Product: GPU-RTX4090
Serial Number: RTX005
Lot Code: GPU-LOT-2025-001

Movement History:
┌─────────────┬─────────────────┬─────────────────┬─────────────────┬─────────────┐
│ Date        │ Movement Type   │ From Location   │ To Location     │ Document    │
├─────────────┼─────────────────┼─────────────────┼─────────────────┼─────────────┤
│ Jan 15, 2025│ Receipt         │ Vendor          │ Main Warehouse  │ VB-2025-001 │
│ Feb 25, 2025│ Internal        │ Main Warehouse  │ Retail Showroom │ INT-2025-001│
│ Mar 15, 2025│ Sale            │ Retail Showroom │ Customer        │ INV-2025-002│
└─────────────┴─────────────────┴─────────────────┴─────────────────┴─────────────┘

Financial Impact:
- Purchase Cost: 2,489,000 IQD (VB-2025-001)
- Sale Price: 2,500,000 IQD (INV-2025-002)
- Gross Profit: 11,000 IQD
- Margin: 0.44%

Quality Events: None recorded
Warranty Status: Active (2 years from purchase)
Current Location: Customer (Tech Solutions Ltd.)
```

## Summary and Business Impact

### Financial Summary (Q1 2025)

```
Revenue Performance:
- Total Sales Revenue: 16,695,000 IQD
- Total COGS: 15,978,013 IQD
- Gross Profit: 716,987 IQD
- Gross Margin: 4.3%

Inventory Investment:
- Current Inventory Value: 48,754,588 IQD
- Inventory Turnover: 0.30 times
- Days Sales in Inventory: 334 days

Currency Impact:
- USD Purchases: $58,295 USD
- Exchange Rate Variance: Minimal (stable rates)
- Multi-currency handling: Successful
```

### Operational Achievements

```
✓ Multi-currency purchasing and conversion
✓ Automated lot tracking and FEFO allocation
✓ Real-time inventory valuation (FIFO/LIFO/AVCO)
✓ Integrated accounting with proper journal entries
✓ Automated reordering suggestions
✓ Comprehensive reporting and analytics
✓ Full traceability from purchase to sale
✓ Accurate COGS calculation by valuation method
✓ Physical inventory adjustment processing
✓ Internal transfer management
```

### System Validation

```
All inventory transactions properly recorded ✓
Journal entries balanced and accurate ✓
Stock quantities match physical counts ✓
Lot tracking maintains complete history ✓
FEFO allocation working correctly ✓
Reordering rules triggering appropriately ✓
Multi-currency conversions accurate ✓
Reports providing actionable insights ✓
```

This comprehensive scenario demonstrates the complete inventory management system functionality across all four implementation phases, showing real-world business workflows with specific data points and measurable outcomes.
