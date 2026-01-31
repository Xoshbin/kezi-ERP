---
title: Inventory Adjustments & Physical Counts
icon: heroicon-o-clipboard-document-check
order: 15
---

# Inventory Adjustments & Physical Counts

This guide explains how to synchronize your system's inventory levels with the actual physical stock in your warehouse. Whether you're doing a full annual count or a quick correction, this document covers the procedures and accounting impacts of inventory adjustments.

---

## What is an Inventory Adjustment?

An **Inventory Adjustment** is a transaction used to correct the quantity on hand in the system to match the quantity found during a physical count. Discrepancies often occur due to:
- Damaged items not recorded
- Theft or shrinkage
- Data entry errors during receipts or deliveries
- Unrecorded internal usage

> [!CAUTION]
> **Inventory Adjustments** (Physical stock) are different from **Adjustment Documents** (Manual journal entries). Use this module for stock quantities, and the Accounting module for purely financial corrections.

---

## Where to find it in the UI

1. **Quick Adjustment**: Navigate to **Inventory → Stock Quantities**. Select a record and use the Edit action to update the quantity directly.
2. **Formal Movement**: Navigate to **Inventory → Stock Movements**. Create a movement with Type **Adjustment**.

---

## The Adjustment Process

### Step 1: Performing a Physical Count
Before entering data, perform a physical count of your products in their respective locations. 
- Use a "Count Sheet" (you can export your Stock Quantities to Excel as a starting point).
- Record the **System Quantity** vs. the **Actual Physical Quantity**.

### Step 2: Recording the Adjustment

#### Method A: Direct Quantity Update (Best for simple fixes)
1. Navigate to **Inventory → Stock Quantities**.
2. Find the Product and Location you want to adjust.
3. Click **Edit**.
4. Update the **Quantity** field to match your physical count.
5. Save the record.

#### Method B: Adjustment Movement (Best for audit trails)
1. Navigate to **Inventory → Stock Movements**.
2. Click **Create Stock Movement**.
3. Set **Movement Type** to **Adjustment**.
4. **If quantity increases**: Set the "From Location" to a Virtual/Adjustment location and the "To Location" to your Warehouse.
5. **If quantity decreases**: Set the "From Location" to your Warehouse and the "To Location" to a Virtual/Adjustment location.
6. Enter the **Quantity** difference.
7. Add a clear **Reason** (e.g., "Found 2 extra units during cycle count").

---

## Accounting Impact

When you finish an adjustment, the system automatically creates a Journal Entry to reflect the change in value.

### If Stock Increases (Positive Adjustment)
- **Debit**: Inventory Asset Account (Increases your assets)
- **Credit**: Inventory Adjustment/Loss Account (Recorded as a gain or recovery)

### If Stock Decreases (Negative Adjustment)
- **Debit**: Inventory Adjustment/Loss Account (Recorded as an expense/loss)
- **Credit**: Inventory Asset Account (Decreases your assets)

> **Note**: For products using FIFO or AVCO, the system uses the current unit value to calculate the adjustment amount.

---

## Best Practices

### 1. Identify the Cause
Don't just fix the numbers. If you are constantly adjusting stock for a specific product, investigate why. Is it a theft issue? A packing error? Or a vendor shipment mistake?

### 2. Use Cycle Counting
Instead of counting everything once a year (which stops operations), count a small category of products every week. This keeps the system accurate year-round.

### 3. Clear Descriptions
Always write a specific reason for the adjustment. "Correction" is not enough. Better: "Sofa damaged during warehouse reorganization" or "Typo in previous receipt #402".

### 4. Zeroing Out
If a product is no longer in stock, adjust it to **0** rather than deleting the record. This maintains historical traceability.

---

## Troubleshooting

**Q: I adjusted the quantity but the valuation didn't change.**
A: Check if the product is set as a **Storable Product**. Only storable products create valuation entries during adjustments.

**Q: Why can't I edit a Stock Quant record?**
A: Check if the stock is currently **Reserved** for a pending delivery or transfer. You must cancel the reservation or complete the movement before you can adjust the total quantity.

**Q: Which account should I use for adjustments?**
A: Usually, a "Stock Variance" or "Inventory Loss" expense account is used. Consult your accountant for the specific GL account mapping in your Chart of Accounts.

---

## Related Documentation
- [Stock Management](stock-management.md) - Basic inventory concepts
- [Stock Movements](stock-movements.md) - Detailed movement workflows
- [Understanding Inventory Ins and Outs](understanding-inventory-ins-and-outs.md) - Detailed valuation methods
