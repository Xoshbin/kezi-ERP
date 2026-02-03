---
title: Landed Cost Reference
icon: heroicon-o-information-circle
order: 10
---

# Landed Cost Reference

Technical details on allocation methods, workflow states, and accounting integration.

---

## Allocation Methods

The allocation method determines how the total cost (e.g., $1,000 freight) is distributed across multiple products in a shipment.

| Method | How it Works | Best Used For |
| :--- | :--- | :--- |
| **By Quantity** | Divides cost equally per unit. | Items of similar size and weight. |
| **By Value** | Distributes proportionally to product price. | High-value items vs. low-value fillers. |
| **By Weight** | Distributes based on physical weight. | Freight charges (which are weight-based). |
| **By Volume** | Distributes based on space occupied. | Container shipping (where space is the limit). |

---

## Workflow States

| State | Description | Accounting Impact |
| :--- | :--- | :--- |
| **Draft** | Record is being prepared. | None. |
| **Validated** | Costs are applied to inventory. | Inventory Asset increases; Stock Input increases. |
| **Cancelled** | Record is voided. | Reversing entries created. |

---

## Accounting Entries

When a landed cost is **Validated**, the following entry is generated:

| Account | Debit (Increase) | Credit (Decrease) |
| :--- | :--- | :--- |
| **Inventory Asset** | $ Amount | — |
| **Stock Input (Accrual)** | — | $ Amount |

*Note: The Stock Input account is cleared when you post the actual Vendor Bill from the freight/insurance provider.*

---

## Glossary

- **Landed Cost**: The total price of a product once it has arrived at the buyer's door.
- **FOB (Free on Board)**: Determines at what point the buyer becomes responsible for costs.
- **DDP (Delivered Duty Paid)**: Seller covers all landed costs.
- **Allocation**: The mathematical process of splitting one cost across many items.

---

## Related Documentation
- [How-to: Managing Landed Costs](../how-to/landed-costs.md)
- [Explanation: Understanding Landed Costs](../explanation/landed-costs.md)
- [Reference: Inventory Fields](inventory-fields.md)
