---
title: Landed Costs
icon: heroicon-o-calculator
order: 10
---

# Landed Costs: Understanding the True Cost of Your Inventory

This guide explains how to use Landed Costs to capture the **real cost** of getting goods to your warehouse—including freight, customs, insurance, and handling fees. Written in plain language for all users.

---

## What Are Landed Costs?

Think of landed costs like this: when you buy a product overseas, the **purchase price** is just the beginning. You also pay:

- 🚢 **Freight/Shipping** – Getting it from the supplier to you
- 🏛️ **Customs Duties** – Government taxes on imports
- 🛡️ **Insurance** – Protection during transit
- 📦 **Handling Fees** – Loading, unloading, warehouse handling

The **landed cost** is the TOTAL of all these expenses. It represents the true cost of each item sitting in your warehouse.

**Why does this matter?**

1. **Accurate Profit Margins**: If you only track the purchase price, you're underestimating your costs and overestimating your profits
2. **Better Pricing Decisions**: Knowing the true cost helps you set competitive yet profitable prices
3. **Correct COGS**: Your Cost of Goods Sold should include ALL costs to acquire inventory
4. **IFRS/GAAP Compliance**: Accounting standards require capitalizing these costs into inventory value

---

## How Landed Costs Affect Your Accounting

### Without Landed Costs (Incorrect Approach)

You buy 100 widgets at $10 each and pay $200 in shipping:

| Entry | Debit | Credit |
|-------|-------|--------|
| Inventory Asset | $1,000 | - |
| Accounts Payable | - | $1,000 |
| Shipping Expense | $200 | - |
| Cash | - | $200 |

**Problem**: Your inventory shows $10/unit, but you really spent $12/unit ($1,200 ÷ 100). When you sell, your COGS is understated and profits are overstated.

### With Landed Costs (Correct Approach)

| Entry | Debit | Credit |
|-------|-------|--------|
| Inventory Asset | $1,200 | - |
| Accounts Payable | - | $1,000 |
| Accounts Payable (Freight) | - | $200 |

**Result**: Your inventory correctly shows $12/unit. COGS and profits are accurate.

---

## Where to Find It

Navigate to: **Inventory → Landed Costs**

You'll see a list of all landed cost records with:
- Date
- Related Vendor Bill
- Total Amount
- Allocation Method
- Status

---

## Creating a Landed Cost Record

### Step 1: Navigate to Landed Costs

Go to **Inventory → Landed Costs → Create New**

### Step 2: Fill in the Details

| Field | Description | Example |
|-------|-------------|---------|
| **Vendor Bill** | The bill containing the additional costs (freight, customs, etc.) | BILL-2026-0045 |
| **Date** | When the costs were incurred | 2026-01-15 |
| **Total Amount** | The total landed cost amount to allocate | $500.00 |
| **Allocation Method** | How to distribute costs across products | By Quantity |
| **Description** | Notes about this landed cost | "Ocean freight for January shipment" |

### Step 3: Attach Stock Pickings

In the **Stock Pickings** section, select the receipt(s) that these costs apply to:

1. Click **Attach Stock Picking**
2. Search for the receipt (e.g., "GR-2026-0123")
3. Select the receipt(s) containing the products
4. The system will show the products and quantities received

### Step 4: Review and Confirm

Before posting:
- [ ] Is the total amount correct?
- [ ] Are the correct stock pickings selected?
- [ ] Is the allocation method appropriate for these products?

Click **Validate** to apply the landed costs to your inventory.

---

## Allocation Methods Explained

The **allocation method** determines how the landed cost is distributed across the products in the selected stock pickings.

### By Quantity

Divides the total cost equally per unit.

**Example**: $500 landed cost for 100 units
- Each unit gets: $500 ÷ 100 = **$5.00**

✅ **Best for**: Items of similar size and weight

### By Value

Distributes costs proportionally based on product value.

**Example**: $500 landed cost for:
- 50 items worth $1,000 (50% of total value)
- 50 items worth $1,000 (50% of total value)

Each group receives $250.

**Detailed Example**:
| Product | Qty | Unit Price | Total Value | % of Total | Landed Cost |
|---------|-----|------------|-------------|------------|-------------|
| Widget A | 10 | $50 | $500 | 25% | $125 |
| Widget B | 20 | $75 | $1,500 | 75% | $375 |
| **Total** | 30 | - | $2,000 | 100% | $500 |

✅ **Best for**: Mixed shipments with varying product values

### By Weight

Distributes costs based on product weight.

**Example**: $600 landed cost for products totaling 300 kg
- Cost per kg: $600 ÷ 300 = $2.00/kg
- A 10 kg item receives: 10 × $2.00 = $20.00

✅ **Best for**: Freight charges (often based on weight)

---

## Real-World Example: Import Scenario

Let's walk through a complete import scenario with multiple cost components.

### The Situation

You're importing electronics from China:
- **100 smartphones** at $200 each = $20,000
- **50 tablet cases** at $20 each = $1,000

**Additional costs**:
| Cost Type | Amount |
|-----------|--------|
| Ocean Freight | $800 |
| Customs Duty (5% on smartphones) | $1,000 |
| Insurance | $200 |
| Port Handling | $300 |
| **Total Landed Costs** | $2,300 |

### Step-by-Step Process

#### 1. Receive the Goods
When the shipment arrives, post the vendor bill for $21,000 (goods only).

The system creates an incoming stock movement:
- 100 smartphones at $200 = $20,000
- 50 tablet cases at $20 = $1,000

#### 2. Create the Freight Landed Cost

Go to **Inventory → Landed Costs → Create New**:
- **Vendor Bill**: Select the freight invoice
- **Date**: Shipment arrival date
- **Total Amount**: $800
- **Allocation Method**: By Weight (freight is weight-based)
- **Description**: "Ocean freight - Container MSKU1234567"

Attach the stock picking (receipt) and validate.

#### 3. Create the Customs Duty Landed Cost

Create another landed cost:
- **Total Amount**: $1,000
- **Allocation Method**: By Value (duties apply to product value)
- **Description**: "Import duty 5% on smartphones"

Attach only the smartphone receipt line and validate.

#### 4. Create Insurance and Handling Landed Costs

Repeat for insurance ($200 by value) and handling ($300 by quantity).

### Final Inventory Values

After all landed costs are applied:

| Product | Qty | Original Value | Landed Costs | Final Value | Cost/Unit |
|---------|-----|----------------|--------------|-------------|-----------|
| Smartphones | 100 | $20,000 | $2,087.50 | $22,087.50 | $220.88 |
| Tablet Cases | 50 | $1,000 | $212.50 | $1,212.50 | $24.25 |
| **Total** | 150 | $21,000 | $2,300 | $23,300 | - |

---

## Impact on COGS and Profit

When you sell a smartphone:

**Without Landed Costs:**
- Sale Price: $350
- COGS: $200
- Gross Profit: $150 (42.9% margin)

**With Landed Costs:**
- Sale Price: $350
- COGS: $220.88
- Gross Profit: $129.12 (36.9% margin)

> **💡 The $20.88 difference per unit** in COGS gives you a more accurate picture of your true profitability!

---

## Status Workflow

Landed cost records go through these stages:

```
┌─────────┐      ┌─────────────┐      ┌─────────┐
│  Draft  │ ──▶ │  Validated  │ ──▶ │  Done   │
└─────────┘      └─────────────┘      └─────────┘
    📝              ✅                   💰
```

### 📝 Draft
- You can still edit all fields
- No accounting entries created yet
- Costs not applied to inventory

### ✅ Validated
- Costs have been applied to inventory values
- Cost layers/average costs updated
- Journal entries created

### ❌ Cancelled
- Landed cost was voided
- Reversing entries created automatically

---

## Best Practices

### 📅 Timing
- **Apply landed costs promptly**: Before selling goods affected by them
- **Match to shipments**: One landed cost per shipment or bill of lading
- **Document thoroughly**: Keep copies of freight invoices, customs declarations

### ✅ Accuracy
- **Get exact amounts**: Use actual invoices, not estimates
- **Choose the right method**: Match allocation method to cost type
- **Review before validating**: Once validated, changes require reversal

### 📊 Organization
- **Use descriptive names**: "Ocean Freight - Container XYZ123"
- **Reference external documents**: Include bill of lading numbers
- **Group related costs**: Consider one landed cost for all costs on a shipment

### 🔍 Audit Trail
- Keep copies of all supporting documents
- Note the relationship between landed costs and vendor bills
- Review landed cost reports periodically

---

## Common Scenarios

### Scenario 1: Single Supplier, All Costs Included

Your supplier quotes **DDP (Delivered Duty Paid)** pricing—all costs are included in the product price.

**Action**: No separate landed cost needed! The purchase price already includes everything.

### Scenario 2: FOB Shipping Point

You buy products **FOB (Free on Board)**—you're responsible for shipping from the supplier's dock.

**Action**: Track ALL costs from that point forward:
- International freight
- Customs
- Local delivery
- Insurance

### Scenario 3: Partial Shipment

A shipment arrives in two parts, but freight was paid upfront for the full container.

**Action**: 
1. Create one landed cost for the full freight amount
2. Attach BOTH stock pickings (receipts)
3. Costs will be allocated based on what's in each receipt

---

## Troubleshooting

### Common Questions

**Q: Why can't I edit a validated landed cost?**

A: Validated landed costs have already updated inventory values and created journal entries. To make changes:
1. Cancel the existing landed cost
2. Create a new one with correct values

**Q: The allocated amounts don't look right—what happened?**

A: Check the allocation method:
- By Quantity: Each unit gets the same amount
- By Value: Higher-value items get more cost
- By Weight: Heavier items get more cost

Also verify the correct stock pickings are attached.

**Q: Can I apply landed costs to goods already sold?**

A: Yes, but it will create adjustment entries. The COGS for those sales will be adjusted to reflect the true cost.

**Q: What if I receive multiple freight invoices for one shipment?**

A: Create separate landed cost records for each invoice, all attached to the same stock picking(s).

---

## Accounting Entries

When you validate a landed cost, the system creates:

| Account | Debit | Credit |
|---------|-------|--------|
| Inventory Asset | $500 | - |
| Stock Input Account | - | $500 |

**In plain English**: We're increasing our inventory value by the landed costs, funded by what we owe (accounts payable for the freight/duty bills).

---

## Related Documentation

- [Understanding Inventory Ins and Outs](understanding-inventory-ins-and-outs.md) - Valuation methods and cost tracking
- [Vendor Bills](vendor-bills.md) - Recording purchase invoices
- [Stock Management](stock-management.md) - Warehouse and inventory basics
- [Stock Picking](stock-picking.md) - Receipt and shipment processing

---

## Glossary

- **Landed Cost**: Total cost to bring goods to their final destination
- **Freight**: Transportation/shipping charges
- **Customs Duty**: Government tax on imported goods
- **FOB**: Free on Board—point at which ownership/responsibility transfers
- **DDP**: Delivered Duty Paid—seller covers all costs to destination
- **Allocation Method**: How costs are distributed across products
- **Stock Picking**: Receipt or shipment document
