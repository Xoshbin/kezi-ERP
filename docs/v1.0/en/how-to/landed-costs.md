---
title: Landed Costs
icon: heroicon-o-calculator
order: 10
---

# Managing Landed Costs

This guide explains how to capture additional expenses—like freight, customs, and insurance—and apply them to your inventory value. 

For a conceptual overview, see [Explanation: Understanding Landed Costs](../explanation/landed-costs.md).

---

## Creating a Landed Cost Record

1. Navigate to **Inventory → Landed Costs**.
2. Click **Create New**.
3. Select the **Vendor Bill** that contains the additional costs (e.g., your freight invoice).
4. Enter the **Total Amount** to allocate.
5. Choose an **Allocation Method** (e.g., "By Weight" for freight).
6. In the **Stock Pickings** section, click **Attach Stock Picking** to select the receipt(s) these costs apply to.
7. Click **Validate**.

---

## Example: Import Scenario

If you are importing electronics:
- **Products**: 100 Smartphones and 50 Tablet Cases.
- **Freight Bill**: $800 from a shipping company.

**Process**:
1. Create a Landed Cost for **$800**.
2. Set method to **By Weight**.
3. Attach the receipt of the smartphones and cases.
4. **Validate**.

The system will automatically add a portion of that $800 to each smartphone and tablet case based on their weight, increasing their "landed" cost per unit.

---

## Troubleshooting & Tips

- **Validation is Permanent**: Once you click Validate, the inventory value is updated and journal entries are posted. To fix a mistake, you must **Cancel** the record and create a new one.
- **Multiple Costs**: You can create multiple Landed Cost records for the same shipment (e.g., one for Freight, one for Customs).
- **Timing**: It is best to apply landed costs **before** you sell the items, so your Cost of Goods Sold (COGS) is accurate from the first sale.

---

## Related Documentation
- [How-to: Generating Financial Reports](generating-financial-reports.md)
- [Explanation: Understanding Landed Costs](../explanation/landed-costs.md)
- [Reference: Landed Cost Reference](../reference/landed-costs.md)
