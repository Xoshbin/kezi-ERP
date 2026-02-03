---
title: Warehouse Transfer Reference
icon: heroicon-o-information-circle
order: 13
---

# Warehouse Transfer Reference

Technical specifications, workflow states, and troubleshooting for inter-warehouse transfers.

---

## Workflow States

Inter-warehouse transfers progress through the following states:

| State | Description | Stock Impact |
| :--- | :--- | :--- |
| **Draft** | Transfer order created, still editable. | No impact. |
| **Confirmed** | Stock is reserved at the source warehouse. | Reserved at Source. |
| **Shipped** | Goods are in transit. | In Transit (Virtual Location). |
| **Done** | Transfer completed and received. | Available at Destination. |

---

## System Requirements

### Location Configuration
- **Source Location**: Must be an Internal location with available stock.
- **Transit Location**: A virtual location used to track goods while moving.
- **Destination Location**: Must be an Internal location.

### Prerequisites
- Storable products with inventory tracking enabled.
- Sufficient "Available" quantity at the source location.
- User permissions for "Internal" stock picking types.

---

## Dashboard Metrics & Widgets

The system provides several ways to monitor transfers:

### In-Transit Widget
- **Count**: Number of active "Shipped" transfers.
- **Total Value**: Financial value of all goods currently in transit.
- **Aging**: Analysis of how long items have been in transit.

### Reports
- **Average Transit Time**: Time taken from "Ship" to "Receive".
- **Accuracy Rate**: Percentage of transfers completed without quantity discrepancies.

---

## Troubleshooting

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| **Cannot see "Ship" button** | Transfer is not in "Confirmed" state. | Ensure you have clicked "Confirm" first. |
| **Cannot see "Receive" button** | Transfer has not been "Shipped". | Ensure the sending warehouse has marked it as Shipped. |
| **Cannot edit products** | Transfer is Confirmed or Shipped. | Cancel the transfer to return to Draft (if not yet Shipped). |
| **Stock remains in Transit** | The "Receive" step was never performed. | Navigate to the Shipped transfer and click Receive. |

---

## Related Documentation
- [How-to: Managing Warehouse Transfers](../how-to/inter-warehouse-transfers.md)
- [Explanation: Understanding Warehouse Transfers](../explanation/warehouse-transfers.md)
- [Reference: Inventory Fields](inventory-fields.md)
