---
title: Scrap Management
icon: heroicon-o-trash
order: 15
---

# Scrap & Disposal Management

This guide explains how to manage inventory scrap, spoilage, and disposal in Kezi ERP. Proper management of scrapped items ensures accurate inventory counts and correct financial reporting of losses.

## Overview

**Scrap Management** is the process of removing items from your active inventory when they are no longer sellable or usable due to:
- **Damage** (broken, defective)
- **Expiry** (perishable goods past their date)
- **Obsolescence** (outdated technology or styles)
- **Theft/Loss** (inventory shrinkage)

In Kezi ERP, scrapping is handled through **Stock Moves** or **Inventory Adjustments**, moving items from a physical stock location to a virtual "loss" location.

## Key Concepts

### Virtual Scrap/Adjustment Locations
Unlike physical warehouses, a scrap location is a **Virtual Location**. When you move goods here:
1. They are removed from your available stock.
2. The system recognizes that they have left the company's asset value.
3. An accounting entry is generated to write off the asset cost.

### Accounting Impact
When you confirm a scrap operation, the system automatically:
- **Credits** the Inventory Asset Account (reducing asset value).
- **Debits** the Inventory Adjustment/Loss Account (recognizing the expense).

---

## How to Scrap Items

The recommended method to scrap items is by creating a **Stock Move** with the **Adjustment** type.

### Step 1: Create a Stock Move
1. Navigate to **Inventory > Operations > Stock Moves**.
2. Click **Create**.

### Step 2: Configure the Move
Fill in the following details:

- **Reference**: Enter a descriptive reference (e.g., `SCRAP/2024/001`).
- **Move Type**: Select **Adjustment**.
- **Description**: Document the *reason* for the scrap (e.g., "Broken Pallet", "Expired milk").

### Step 3: Add Products
In the **Product Lines** section:
1. **Product**: Select the item to be scrapped.
2. **Quantity**: Enter the quantity to remove.
3. **From Location**: Select the physical location (e.g., `WH/Stock`).
4. **To Location**: Select the **Inventory Adjustment** (or Scrap) virtual location.
   > **Note**: This ensures the items are properly written off.

### Step 4: Confirm and Validate
1. Click **Save** to create the draft.
2. Review the details.
3. Click **Confirm Movement**.

Once confirmed:
- The stock quantity is immediately reduced.
- The financial journal entry is posted.

---

## Scrapping vs. Returns
- **Use Scrap**: When the item has no value and is being destroyed or discarded.
- **Use Vendor Return**: When the item is damaged but can be returned to the supplier for credit (use a **Debit Note** or **Return** instead).

## Reporting & Analysis
To review your scrap history:
1. Go to **Inventory > Operations > Stock Moves**.
2. Filter by **Move Type: Adjustment**.
3. You can also filter by "To Location" = Adjustment/Scrap to see all disposals.

### Cost Analysis
The cost of scrapped goods is calculated based on the product's valuation method (FIFO/Average Cost) at the time of the move. This cost flows into your **Profit & Loss** report under **Inventory Loss/Adjustment Expenses**.
