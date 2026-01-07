---
title: Serial Number Tracking
icon: heroicon-o-qr-code
order: 7
---


This detailed guide explains how to implement and manage serial number tracking in your inventory system, covering serial number creation, assignment, verification, and complete unit-level traceability.

---

## What is Serial Number Tracking?

Serial Number tracking is a precision inventory method that assigns a unique identifier to each individual unit of a product. Unlike lot tracking (which tracks batches of identical items), serial tracking ensures that every single item has its own unique history, warranty status, and movement log.

- **Unique Identification**: Every unit has a distinct code (e.g., SN-123456)
- **Unit-Level Traceability**: Track exact location and history of a specific item
- **Warranty Management**: Associate warranties with specific serial numbers
- **Theft Prevention**: Track high-value items individually
- **Return Verification**: Ensure returned items match what was sold

**Accounting Purpose**: Serial tracking provides granular audit trails for high-value assets and ensures strict inventory control, preventing shrinkage and ensuring expensive inventory is accurately valued.

---

## System Requirements

### Product Configuration
- **Tracking Type**: Products must be configured with "Serial" tracking type
- **Uniqueness**: Serial numbers must be unique per product (or globally depending on configuration)

### Prerequisites
1. **Product Setup**: Products configured as "Storable" with Tracking set to "Serial"
2. **Stock Locations**: Standard warehouse locations setup
3. **User Permissions**: Access to Inventory features

---

## Where to find it in the UI

Navigate to **Inventory → Serial Numbers**

Serial tracking also appears in:
- **Products**: Configuration of tracking type
- **Stock Movements**: Serial selection during moves
- **Stock Quantities**: View specific serial numbers at each location
- **Reports**: Traceability of individual units

**Tip**: The header's Help/Docs button opens this guide.

---

## Enabling Serial Number Tracking

### Product Configuration

Navigate to **Inventory → Products** → Select Product → **Edit**

**Inventory Tab**:
1. Locate the **Traceability** section
2. Change **Tracking** from "No Tracking" (or Lot) to **By Unique Serial Number**
3. Save the product

**Note**: You can only enable serial tracking on "Storable Products".

---

## Creating and Managing Serial Numbers

### Manual Creation

Navigate to **Inventory → Serial Numbers** → **Create Serial Number**

**Step 1: Basic Information**
- **Serial Number**: Enter the unique code (e.g., SN-2024-001)
- **Product**: Select the associated product (must have Serial tracking enabled)
- **Internal Reference**: Optional internal code

**Step 2: Additional Details**
- **Logistics**: View current location (updates automatically based on moves)
- **Purchase info**: Link to original Purchase Order or Lot (if applicable)

### Automatic Creation (Validation)

Serial numbers are typically created or validated during **Stock Movements**:

**Receiving Goods (Purchases)**:
1. When receiving a customized product, you can assign serial numbers immediately.
2. If the serial number doesn't exist, the system creates it.
3. If it exists but is in a different location, the system will validate the move.

---

## Workflow: Receiving & Delivering

### 1. Receiving Serialized Goods (Vendor Bill / Purchase)
When you process a Vendor Bill that generates a stock receipt:
1. The system creates a pending **Stock Movement**.
2. Open the movement.
3. For each serialized line item, you must **specify the serial number(s)**.
4. You can scan or type the serial numbers.
5. **Validate**: The system confirms the serials are new or valid for receipt.

### 2. Delivering Serialized Goods (Invoice / Sale)
When you process a Customer Invoice that generates a delivery:
1. The system creates a pending **Stock Movement**.
2. Open the movement.
3. **Select Serial Numbers**: Choose from the list of available serial numbers at the source location.
4. **Validation**: The system prevents selecting serial numbers that are not currently in stock at that location.
5. **Validate**: The stock is moved to the customer, and the serial number's location is updated.

---

## Traceability & History

### Viewing Serial Number History

Navigate to **Inventory → Serial Numbers** → Select a Serial Number

**Location History**:
- View every move this specific unit has made.
- See dates, source locations, destination locations, and users responsible.

**Status**:
- **Available**: In stock and ready to ship.
- **On Hand**: Physically present (may be reserved).
- **Out of Stock**: Delivered or consumed.

---

## Troubleshooting

**Q: I cannot select a serial number for delivery.**
A: Ensure the serial number is actually at the "From Location" of the movement. Check the "Current Location" field on the Serial Number detail page.

**Q: Can I have two products with the same serial number?**
A: Yes, if they are different products. The constraint is usually (Product + Serial Number) must be unique.

**Q: How do I correct a wrong serial number?**
A: If the move is already done, you must create a correction movement (Internal Transfer) to move the "wrong" serial to a scrap/virtual location and bring the "right" serial in (via adjustment).

---

## Best Practices

1. **Barcode Scanning**: Use barcode scanners to input serial numbers to avoid typos.
2. **Reception Check**: Always verify serial numbers physically matches the box/item upon receipt.
3. **High Value Only**: Only use serial tracking for high-value items (e.g., laptops, engines) due to the extra administrative work required. For smaller items, consider **Lot Tracking**.

---

## Related Documentation

- [Inventory Management](inventory-management.md)
- [Lot Tracking](lot-tracking.md)
- [Stock Movements](stock-movements.md)
