---
title: Inter-Warehouse Transfers
icon: heroicon-o-arrow-path
order: 13
---

# Managing Inter-Warehouse Transfers

This guide shows you how to move inventory between different physical warehouses using the two-step **ship and receive** workflow. This process ensures you always know exactly what is in your warehouses and what is currently on the truck.

For a deeper understanding of why we use this workflow, see the [Explanation: Understanding Warehouse Transfers](../explanation/warehouse-transfers.md).

---

## Creating a Transfer Order

1. Navigate to **Inventory → Stock Pickings**.
2. Click **Create Stock Picking**.
3. Set the **Type** to "Internal".
4. Select your **Source Location** (where stock is leaving) and **Destination Location** (where stock is going).
5. In the **Product Lines** section, click **Add Product Line**.
6. Select the product and enter the **Quantity** to move.
7. Click **Save** or **Confirm**.

---

## Step 1: Shipping the Stock

Once the transfer is confirmed, the sending warehouse must "ship" the items to move them into transit.

1. Open the **Confirmed** transfer order.
2. Verify that the items are packed and ready to leave.
3. Click the **Ship** button.

**What happens next?**
The system creates a stock movement from your warehouse to a virtual "Transit" location. The items are no longer available in the source warehouse but appear in "In-Transit" reports.

---

## Step 2: Receiving the Stock

When the delivery arrives at the destination warehouse, the receiving team must record the receipt to move the items into their local stock.

1. Navigate to **Inventory → Stock Pickings**.
2. Filter for transfers with the status **Shipped**.
3. Open the relevant transfer.
4. Click the **Receive** button.

**Result**: The stock is moved from the virtual "Transit" location into the destination warehouse's internal stock. The transfer status changes to **Done**.

---

## Monitoring In-Transit Stock

To see what is currently moving between warehouses:

- **Dashboard**: Check the **Transfers in Transit** widget for a quick count and value.
- **Stock Quantities**: Go to **Inventory → Stock Quantities** and filter by the "Transit" location to see exactly which products are on the road.
- **Filters**: In the Stock Pickings list, use the **Type: Internal** and **Status: Shipped** filters.

---

## Troubleshooting & FAQ

| Question | Answer |
| :--- | :--- |
| **Can I cancel a shipped transfer?** | No. Once shipped, the stock is "on the road." You must receive it first, then create a new transfer to send it back if needed. |
| **How do I handle damaged goods?** | Receive the full transfer first, then perform an [Inventory Adjustment](stock-movements.md) to record the loss or damage. |
| **Why is the "Receive" button missing?** | The button only appears if the transfer has already been "Shipped." |

For full technical specifications and state details, see the [Reference: Warehouse Transfer Reference](../reference/warehouse-transfers.md).

---

## Related Documentation
- [How-to: Managing Stock](managing-stock.md)
- [Explanation: Understanding Warehouse Transfers](../explanation/warehouse-transfers.md)
- [Reference: Warehouse Transfer Reference](../reference/warehouse-transfers.md)
