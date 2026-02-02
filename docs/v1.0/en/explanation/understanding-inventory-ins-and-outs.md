---
title: Understanding Inventory Ins and Outs
icon: heroicon-o-arrows-up-down
order: 6
---

# Understanding Inventory Ins and Outs

This guide explains how inventory flows through your business—from purchasing goods to selling them to customers. Written in plain language for all users, it covers practical examples of tracking stock, understanding costs, and reading inventory reports.

---

## What is Inventory Movement?

Every time products enter or leave your business, that's an **inventory movement**. Think of it like a log book at a warehouse door—every box that comes in or goes out gets recorded.

**Why does this matter?**

1. **Know what you have**: Without tracking, you might think you have 100 items when you only have 50
2. **Know what it cost**: Different purchases have different costs—this affects your profits
3. **Meet customer demand**: You can't sell what you don't have
4. **Accurate financials**: Your accountant needs this for balance sheets and tax reporting

---

## The Four Types of Inventory Movement

### 1. 📥 Incoming (Receipts)

**What it is**: Products entering your warehouse

**When it happens**:
- You receive goods from a supplier
- You post a Vendor Bill for products

**Example**:
> You ordered 100 laptops from Dell. When the delivery truck arrives and you post the bill, the system records an **incoming movement** of 100 laptops to your warehouse.

**What changes**:
- Warehouse quantity **increases** by 100 laptops
- Your Inventory Asset account **increases** (you own more stuff)
- Your Accounts Payable **increases** (you owe the supplier)

---

### 2. 📤 Outgoing (Deliveries)

**What it is**: Products leaving your warehouse to customers

**When it happens**:
- You ship goods to a customer
- You post a Customer Invoice for products

**Example**:
> A customer orders 10 laptops. When you ship them and post the invoice, the system records an **outgoing movement** of 10 laptops from your warehouse.

**What changes**:
- Warehouse quantity **decreases** by 10 laptops
- Your Inventory Asset account **decreases** (you own less stuff)
- Cost of Goods Sold (COGS) is recorded as an **expense**
- Revenue is recorded from the sale

---

### 3. 🔄 Internal Transfer

**What it is**: Moving products between your own locations

**When it happens**:
- Moving stock from main warehouse to a retail store
- Reorganizing inventory between storage areas

**Example**:
> You have 200 laptops in your Main Warehouse. Business is good at your Downtown Store, so you transfer 50 laptops there.

**What changes**:
- Main Warehouse quantity **decreases** by 50
- Downtown Store quantity **increases** by 50
- **Total inventory stays the same** (no accounting entries)

---

### 4. ⚖️ Adjustment

**What it is**: Corrections to inventory counts

**When it happens**:
- Physical count reveals more or fewer items than expected
- Products are damaged, lost, or expired
- Correcting data entry errors

**Example**:
> During a physical count, you find only 195 laptops but the system shows 200. You create an **adjustment** to decrease inventory by 5 units.

**What changes**:
- System quantity is corrected to match reality
- An Inventory Adjustment expense is recorded (for losses)
- Full audit trail is maintained

---

## Understanding Stock Quantities

### The Three Key Numbers

When you look at your inventory, you'll see three important numbers:

| Number | What It Means | Example |
|--------|---------------|---------|
| **Quantity** | Total physical stock you have | 100 laptops |
| **Reserved** | Stock promised to pending orders | 15 laptops |
| **Available** | Stock you can sell right now | 85 laptops |

**The Formula**:
```
Available = Quantity - Reserved
```

### Why Reserved Matters

When a customer places an order but you haven't shipped yet:

**Before the order:**
- Quantity: 100
- Reserved: 0
- Available: 100

**After the order (before shipping):**
- Quantity: 100 *(hasn't changed—laptops are still in warehouse)*
- Reserved: 15 *(set aside for this customer)*
- Available: 85 *(what you can promise to new customers)*

**After shipping:**
- Quantity: 85 *(laptops have left the building)*
- Reserved: 0 *(order fulfilled)*
- Available: 85

> **💡 Tip**: Reserved stock prevents "overselling"—promising the same items to multiple customers.

---

## Understanding Stock Locations

Locations help you organize WHERE your inventory is stored.

### Location Types

| Type | Purpose | Examples |
|------|---------|----------|
| **Internal** | Your own storage areas | Main Warehouse, Store A, Back Room |
| **Vendor** | Represents suppliers (virtual) | Supplier Holding, Dell Shipments |
| **Customer** | Represents customers (virtual) | Customer Deliveries |
| **Adjustment** | For inventory corrections (virtual) | Inventory Adjustments |

### How Locations Work in Movements

Every movement has a **From** and **To** location:

| Movement Type | From Location | To Location |
|--------------|---------------|-------------|
| Incoming (Receipt) | Vendor location | Your warehouse |
| Outgoing (Delivery) | Your warehouse | Customer location |
| Internal Transfer | One warehouse | Another warehouse |
| Adjustment (loss) | Your warehouse | Adjustment location |

---

## Where to Find Inventory Information

### Stock Quantities Screen

**Navigate to**: Inventory → Stock Quantities

This is your dashboard for "what do I have and where?"

| Column | Description |
|--------|-------------|
| Product | Product name and code |
| Location | Where the stock is |
| Quantity | Total units on hand |
| Reserved | Units reserved for orders |
| Available | Units available for new orders |
| Lot | Lot code (if lot tracking enabled) |

**Helpful Filters**:
- Filter by product to see all locations where it's stored
- Filter by location to see all products in one warehouse
- Filter by low stock to find items needing reorder

---

### Stock Movements Screen

**Navigate to**: Inventory → Stock Movements

This is your audit trail—every movement ever made.

| Column | Description |
|--------|-------------|
| Date | When the movement occurred |
| Reference | Movement number or source document |
| Type | Incoming, Outgoing, Transfer, or Adjustment |
| Product | What was moved |
| Quantity | How much was moved |
| From/To | Origin and destination locations |
| Status | Draft, Confirmed, or Done |

**Helpful Filters**:
- Filter by date range to see recent activity
- Filter by type to see only receipts or deliveries
- Filter by product to trace its movement history

---

## Valuation Methods: Understanding Product Costs

### Why Valuation Methods Matter

When you sell a product, what did it **cost** you? This seems simple, but consider:

- You bought 10 units at $100 each in January
- You bought 10 more units at $120 each in February
- You now have 20 units worth a total of $2,200
- A customer buys 1 unit—what was its cost?

The answer depends on your **valuation method**. This affects:
- Your **profit margins** on each sale
- Your **Cost of Goods Sold (COGS)** expense
- Your **inventory value** on the balance sheet
- Your **taxes** (higher costs = lower profits = lower taxes)

---

## AVCO: Average Cost Method

### How AVCO Works

**AVCO** (Average Cost, also called Weighted Average) calculates a single average cost for all units of a product.

**The Formula**:
```
Average Cost = Total Value of Inventory ÷ Total Quantity
```

### AVCO Step-by-Step Example

**January 1st: Purchase 10 units at $100 each**
```
┌───────────────────────────────────────────────────────┐
│ Quantity: 10 units                                    │
│ Total Value: 10 × $100 = $1,000                       │
│ Average Cost: $1,000 ÷ 10 = $100 per unit            │
└───────────────────────────────────────────────────────┘
```

**February 1st: Purchase 10 more units at $120 each**
```
┌───────────────────────────────────────────────────────┐
│ Previous: 10 units worth $1,000                       │
│ New Purchase: 10 units worth $1,200                   │
│ ──────────────────────────────────────────────────────│
│ Total Quantity: 10 + 10 = 20 units                    │
│ Total Value: $1,000 + $1,200 = $2,200                 │
│ NEW Average Cost: $2,200 ÷ 20 = $110 per unit        │
└───────────────────────────────────────────────────────┘
```

**February 15th: Sell 5 units**
```
┌───────────────────────────────────────────────────────┐
│ Cost of Goods Sold: 5 × $110 = $550                   │
│ ──────────────────────────────────────────────────────│
│ Remaining Quantity: 20 - 5 = 15 units                 │
│ Remaining Value: $2,200 - $550 = $1,650               │
│ Average Cost: Still $110 per unit                     │
│ (Average only changes when you BUY, not when you sell)│
└───────────────────────────────────────────────────────┘
```

**March 1st: Purchase 5 more units at $130 each**
```
┌───────────────────────────────────────────────────────┐
│ Previous: 15 units worth $1,650                       │
│ New Purchase: 5 units worth $650                      │
│ ──────────────────────────────────────────────────────│
│ Total Quantity: 15 + 5 = 20 units                     │
│ Total Value: $1,650 + $650 = $2,300                   │
│ NEW Average Cost: $2,300 ÷ 20 = $115 per unit        │
└───────────────────────────────────────────────────────┘
```

### When to Use AVCO

✅ **Best for**:
- Commodities (oil, grain, metals)
- Products with stable prices
- High-volume, low-value items
- When simplicity is preferred

❌ **Not ideal for**:
- Perishable goods (use FIFO instead)
- Products with significant price fluctuations
- When tracking specific purchase costs is important

### AVCO Pros and Cons

| Advantages | Disadvantages |
|------------|---------------|
| Simple to understand and calculate | Doesn't match physical flow of goods |
| Smooths out price fluctuations | May not reflect actual costs paid |
| Less record-keeping than FIFO/LIFO | Can give misleading margins if prices change rapidly |
| Good for tax planning (stable costs) | Not suitable for perishables |

---

## FIFO: First In, First Out

### How FIFO Works

**FIFO** assumes that the **oldest** inventory is sold first. Each purchase creates a separate "cost layer" that is consumed in order.

**Think of it like**: A grocery store rotating milk—the oldest bottles are sold first to prevent spoilage.

### FIFO Step-by-Step Example

**January 1st: Purchase 10 units at $100 each**
```
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 1: 10 units @ $100 each (Jan 1) - Remaining: 10      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Total Inventory: 10 units worth $1,000                          │
└─────────────────────────────────────────────────────────────────┘
```

**February 1st: Purchase 10 more units at $120 each**
```
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 1: 10 units @ $100 each (Jan 1) - Remaining: 10      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 2: 10 units @ $120 each (Feb 1) - Remaining: 10      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Total Inventory: 20 units worth $2,200                          │
└─────────────────────────────────────────────────────────────────┘
```

**February 15th: Sell 12 units** (Using FIFO: oldest first!)

```
┌─────────────────────────────────────────────────────────────────┐
│ CONSUMING LAYERS (oldest first):                                │
│                                                                 │
│ Step 1: Consume ALL of Layer 1                                  │
│         10 units × $100 = $1,000                                │
│         Still need: 12 - 10 = 2 more units                      │
│                                                                 │
│ Step 2: Consume PART of Layer 2                                 │
│         2 units × $120 = $240                                   │
│                                                                 │
│ ──────────────────────────────────────────────────────────────  │
│ TOTAL COGS: $1,000 + $240 = $1,240                             │
│ Average cost per unit sold: $1,240 ÷ 12 = $103.33              │
└─────────────────────────────────────────────────────────────────┘

After the sale:
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 1: 10 units @ $100 each (Jan 1) - Remaining: 0 ✗     │ │
│ │          (FULLY CONSUMED)                                   │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 2: 10 units @ $120 each (Feb 1) - Remaining: 8       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Remaining Inventory: 8 units worth $960 ($120 × 8)              │
└─────────────────────────────────────────────────────────────────┘
```

**March 1st: Purchase 5 more units at $130 each**
```
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 2: 10 units @ $120 each (Feb 1) - Remaining: 8       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 3: 5 units @ $130 each (Mar 1) - Remaining: 5        │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Remaining Inventory: 13 units worth $1,610                      │
│ ($960 from Layer 2 + $650 from Layer 3)                         │
└─────────────────────────────────────────────────────────────────┘
```

### When to Use FIFO

✅ **Best for**:
- Perishable goods (food, medicine, chemicals)
- Products with expiration dates
- When you physically rotate stock (oldest sold first)
- Rising cost environments (shows lower COGS, higher profits)

❌ **Not ideal for**:
- When you want to minimize taxable income
- Products where newest stock should be sold first
- Very high-volume operations (more record-keeping)

### FIFO Pros and Cons

| Advantages | Disadvantages |
|------------|---------------|
| Matches physical flow of most businesses | More complex record-keeping |
| Best for perishables and dated products | In rising prices: higher profits = higher taxes |
| Ending inventory reflects recent costs | More cost layers to track |
| Widely accepted and understood | Can show inflated profits during inflation |

---

## LIFO: Last In, First Out

### How LIFO Works

**LIFO** assumes that the **newest** inventory is sold first. The most recent purchases are consumed before older ones.

**Think of it like**: A stack of papers—you take from the top (most recent addition) first.

> **⚠️ Note**: LIFO is not permitted under IFRS (International Financial Reporting Standards). It's mainly used in the United States under US GAAP. Check your local accounting regulations.

### LIFO Step-by-Step Example

**Starting Point**: Same purchases as FIFO example

```
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 1: 10 units @ $100 each (Jan 1) - Remaining: 10      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 2: 10 units @ $120 each (Feb 1) - Remaining: 10      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Total Inventory: 20 units worth $2,200                          │
└─────────────────────────────────────────────────────────────────┘
```

**February 15th: Sell 12 units** (Using LIFO: newest first!)

```
┌─────────────────────────────────────────────────────────────────┐
│ CONSUMING LAYERS (newest first):                                │
│                                                                 │
│ Step 1: Consume ALL of Layer 2 (the NEWEST)                     │
│         10 units × $120 = $1,200                                │
│         Still need: 12 - 10 = 2 more units                      │
│                                                                 │
│ Step 2: Consume PART of Layer 1                                 │
│         2 units × $100 = $200                                   │
│                                                                 │
│ ──────────────────────────────────────────────────────────────  │
│ TOTAL COGS: $1,200 + $200 = $1,400                             │
│ Average cost per unit sold: $1,400 ÷ 12 = $116.67              │
└─────────────────────────────────────────────────────────────────┘

Compare to FIFO for same sale:
┌─────────────────────────────────────────────────────────────────┐
│           │ FIFO        │ LIFO        │ Difference             │
│ COGS      │ $1,240      │ $1,400      │ LIFO is $160 higher    │
│ Profit    │ Higher      │ Lower       │ Lower taxes with LIFO  │
└─────────────────────────────────────────────────────────────────┘
```

**After the sale:**
```
┌─────────────────────────────────────────────────────────────────┐
│ COST LAYERS:                                                    │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 1: 10 units @ $100 each (Jan 1) - Remaining: 8       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Layer 2: 10 units @ $120 each (Feb 1) - Remaining: 0 ✗     │ │
│ │          (FULLY CONSUMED)                                   │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ Remaining Inventory: 8 units worth $800 ($100 × 8)              │
└─────────────────────────────────────────────────────────────────┘
```

### When to Use LIFO

✅ **Best for**:
- Tax minimization during inflation (higher COGS = lower profits = lower taxes)
- Non-perishable goods where physical rotation doesn't matter
- US companies using US GAAP

❌ **Not ideal for**:
- Perishable goods
- Companies using IFRS (LIFO is prohibited)
- When accurate inventory valuation is prioritized

### LIFO Pros and Cons

| Advantages | Disadvantages |
|------------|---------------|
| In rising prices: lower profits = lower taxes | Ending inventory may show outdated costs |
| Matches costs with current revenues | Not permitted under IFRS |
| Reduces paper profits during inflation | Doesn't match physical flow |
| Better for tax planning | More complex record-keeping |

---

## Comparing Valuation Methods: A Complete Example

Let's run through a complete quarter with all three methods:

### The Transactions

| Date | Transaction | Units | Unit Cost | Total |
|------|-------------|-------|-----------|-------|
| Jan 1 | Opening Balance | 100 | $10.00 | $1,000 |
| Jan 15 | Purchase | 50 | $12.00 | $600 |
| Feb 1 | Sale | 80 | - | - |
| Feb 15 | Purchase | 60 | $11.50 | $690 |
| Mar 1 | Sale | 70 | - | - |

### AVCO Calculations

**After Jan 15 Purchase:**
- Total Units: 100 + 50 = 150
- Total Value: $1,000 + $600 = $1,600
- Average Cost: $1,600 ÷ 150 = **$10.67**

**Feb 1 Sale (80 units):**
- COGS: 80 × $10.67 = **$853.60**
- Remaining: 70 units × $10.67 = $746.90

**After Feb 15 Purchase:**
- Total Units: 70 + 60 = 130
- Total Value: $746.90 + $690 = $1,436.90
- Average Cost: $1,436.90 ÷ 130 = **$11.05**

**Mar 1 Sale (70 units):**
- COGS: 70 × $11.05 = **$773.50**
- Remaining: 60 units × $11.05 = $663.00

**AVCO Total COGS: $853.60 + $773.50 = $1,627.10**

---

### FIFO Calculations

**Layers after Jan 15:**
- Layer 1: 100 units @ $10.00
- Layer 2: 50 units @ $12.00

**Feb 1 Sale (80 units)** - Take from oldest first:
- From Layer 1: 80 units × $10.00 = **$800**
- Layer 1 remaining: 20 units
- COGS: **$800**

**After Feb 15 Purchase:**
- Layer 1: 20 units @ $10.00
- Layer 2: 50 units @ $12.00
- Layer 3: 60 units @ $11.50

**Mar 1 Sale (70 units)** - Take from oldest first:
- From Layer 1: 20 units × $10.00 = $200
- From Layer 2: 50 units × $12.00 = $600
- COGS: $200 + $600 = **$800**
- Layer 2 remaining: 0 units

**Remaining Inventory:**
- Layer 3: 60 units @ $11.50 = $690

**FIFO Total COGS: $800 + $800 = $1,600**

---

### LIFO Calculations

**Layers after Jan 15:**
- Layer 1: 100 units @ $10.00
- Layer 2: 50 units @ $12.00

**Feb 1 Sale (80 units)** - Take from newest first:
- From Layer 2: 50 units × $12.00 = $600
- From Layer 1: 30 units × $10.00 = $300
- COGS: $600 + $300 = **$900**
- Layer 1 remaining: 70 units

**After Feb 15 Purchase:**
- Layer 1: 70 units @ $10.00
- Layer 3: 60 units @ $11.50

**Mar 1 Sale (70 units)** - Take from newest first:
- From Layer 3: 60 units × $11.50 = $690
- From Layer 1: 10 units × $10.00 = $100
- COGS: $690 + $100 = **$790**

**Remaining Inventory:**
- Layer 1: 60 units @ $10.00 = $600

**LIFO Total COGS: $900 + $790 = $1,690**

---

### Method Comparison Summary

| Metric | AVCO | FIFO | LIFO |
|--------|------|------|------|
| **Total COGS** | $1,627.10 | $1,600.00 | $1,690.00 |
| **Ending Inventory Value** | $663.00 | $690.00 | $600.00 |
| **Gross Profit** (if sales = $3,000) | $1,372.90 | $1,400.00 | $1,310.00 |
| **Tax Impact** (lower profit = lower taxes) | Medium | Highest taxes | Lowest taxes |

---

## How to Choose the Right Method

### Decision Guide

```
                    START HERE
                        │
                        ▼
            ┌───────────────────────┐
            │ Is your product      │
            │ perishable or dated? │
            └───────────────────────┘
                  │           │
                 YES          NO
                  │           │
                  ▼           ▼
            ┌─────────┐  ┌───────────────────────┐
            │ Use     │  │ Are prices generally  │
            │ FIFO    │  │ rising over time?     │
            └─────────┘  └───────────────────────┘
                               │           │
                              YES          NO
                               │           │
                               ▼           ▼
                    ┌─────────────────┐  ┌─────────┐
                    │ Is tax          │  │ Use     │
                    │ minimization a  │  │ AVCO    │
                    │ priority?       │  │         │
                    └─────────────────┘  └─────────┘
                         │         │
                        YES        NO
                         │         │
                         ▼         ▼
                   ┌─────────┐ ┌───────────────────┐
                   │ Use     │ │ Is simplicity     │
                   │ LIFO*   │ │ important?        │
                   └─────────┘ └───────────────────┘
                                    │         │
                                   YES        NO
                                    │         │
                                    ▼         ▼
                              ┌─────────┐ ┌─────────┐
                              │ Use     │ │ Use     │
                              │ AVCO    │ │ FIFO    │
                              └─────────┘ └─────────┘

                    * Only if permitted in your jurisdiction
```

### Quick Reference

| If You Need... | Best Method |
|----------------|-------------|
| Simplicity | AVCO |
| Match physical flow (perishables) | FIFO |
| Lower taxes during inflation | LIFO (where permitted) |
| Accurate ending inventory value | FIFO |
| Smooth out price fluctuations | AVCO |
| Detailed cost tracing | FIFO or LIFO |

---

## Accounting Entries Explained

### When You Receive Goods (Incoming)

**What the system records:**

| Account | Debit | Credit |
|---------|-------|--------|
| Inventory Asset | $1,200 | - |
| Stock Input (Liability) | - | $1,200 |

**In plain English**: "We now own $1,200 more inventory, and we owe the supplier $1,200."

When you pay the bill later:
| Account | Debit | Credit |
|---------|-------|--------|
| Stock Input (Liability) | $1,200 | - |
| Cash/Bank | - | $1,200 |

---

### When You Sell Goods (Outgoing)

**What the system records:**

| Account | Debit | Credit |
|---------|-------|--------|
| Cost of Goods Sold | $800 | - |
| Inventory Asset | - | $800 |

**In plain English**: "We're recording $800 as an expense (the cost of items sold), and our inventory value decreased by $800."

Plus the revenue side:
| Account | Debit | Credit |
|---------|-------|--------|
| Accounts Receivable | $1,500 | - |
| Revenue | - | $1,500 |

**Your profit**: $1,500 - $800 = $700

---

## Best Practices

### 1. Choose Your Valuation Method Carefully
- This is usually set at company setup and should rarely change
- Changing methods requires careful accounting adjustments
- Consult with your accountant before making changes

### 2. Process Movements Promptly
- Record receipts when goods arrive, not when bills arrive
- Record deliveries when goods ship, not when invoiced
- Keep your inventory counts accurate in real-time

### 3. Regular Physical Counts
- Count inventory periodically (monthly, quarterly, or annually)
- Use adjustment movements to correct discrepancies
- Investigate significant variances

### 4. Use References Consistently
- Always include reference numbers on movements
- Link to source documents (PO numbers, bill numbers, etc.)
- This helps with tracking and auditing

### 5. Understand the Reports
- **Valuation Report**: What is your inventory worth?
- **Aging Report**: How old is your inventory?
- **Turnover Report**: How fast is inventory selling?
- Review these regularly for business insights

---

## Troubleshooting Common Questions

**Q: Why doesn't my physical count match the system?**
A: Common causes:
- Movements not recorded promptly
- Goods received but bill not posted
- Goods shipped but invoice not posted
- Theft, damage, or errors
- Create an adjustment to correct and investigate the cause

**Q: Why is my COGS zero for a sale?**
A: The system may not have cost information. Check:
- Has a receipt been processed for this product?
- For FIFO/LIFO: Are there cost layers available?
- For AVCO: Is the average cost set on the product?

**Q: Can I change the valuation method for a product?**
A: Yes, but with caution:
- This affects historical and future calculations
- May require adjusting journal entries
- Consult with your accountant first
- Document the change for audit purposes

**Q: Why can't I edit a completed movement?**
A: Completed movements are "immutable" (can't be changed) to maintain data integrity and audit trails. Instead:
- Create a reversing movement to undo the original
- Then create a new correct movement
- This maintains a complete history

---

## Related Documentation

- [Inventory Management](inventory-management.md) - Complete system overview
- [Stock Movements](stock-movements.md) - Detailed movement guide
- [Lot Tracking](lot-tracking.md) - Lot management for traceability
- [Vendor Bills](vendor-bills.md) - Purchase processing
- [Customer Invoices](customer-invoices.md) - Sales processing

---

This guide covers everything you need to understand how inventory flows through your business and how costs are calculated. For technical implementation details, see the [Developer Guide](../Developers/inventory-movements-guide.md).
