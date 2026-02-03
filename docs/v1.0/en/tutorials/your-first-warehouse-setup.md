---
title: Your First Warehouse Setup
icon: heroicon-o-building-office
order: 1
---

# Tutorial: Your First Warehouse Setup

This tutorial guides you through setting up your first warehouse in Kezi ERP. You will learn how to configure the physical structure of your stock locations, set up a product for tracking, and process your initial inventory receipt.

**Time to complete**: 10 minutes
**Prerequisites**: Admin access to the Inventory module

---

## Story: The New Distribution Center

Imagine you are opening a new distribution center called "North Hub". You need to organize it into three main zones: a receiving dock, a main storage area, and a dispatch zone. You also need to add your first product, a "Premium Widget", and record that you have receiving 100 units.

---

## Step 1: Define Your Warehouse

First, we need to tell the system that this physical building exists.

1.  Navigate to **Inventory > Configuration > Warehouses**.
2.  Click **Create Warehouse**.
3.  Fill in the details:
    *   **Warehouse**: North Hub
    *   **Short Name**: NH (This will be used as a prefix for locations)
4.  Click **Create** (or **Save**).

The system automatically creates a main stock location for this warehouse (e.g., `NH/Stock`).

---

## Step 2: Organize Your Locations

Now, let's create the zones within your warehouse. We will create a "Receiving Dock" and a "Dispatch Zone".

1.  Navigate to **Inventory > Configuration > Locations**.
2.  You should see `NH/Stock` already in the list.
3.  Click **Create Location**.
4.  Enter the details for the Receiving Dock:
    *   **Location Name**: Receiving Dock
    *   **Parent Location**: NH (Select the view or internal location representing the warehouse root)
    *   **Location Type**: Internal Location
5.  Click **Create & Create Another**.
6.  Enter details for the Dispatch Zone:
    *   **Location Name**: Dispatch Zone
    *   **Parent Location**: NH
    *   **Location Type**: Internal Location
7.  Click **Create**.

*Result*: You now have a structured warehouse with `NH/Receiving Dock`, `NH/Stock`, and `NH/Dispatch Zone`.

---

## Step 3: Create a Storable Product

To track inventory, the product must be set to "Storable".

1.  Navigate to **Inventory > Products > Products**.
2.  Click **Create Product**.
3.  Fill in the Key Information:
    *   **Product Name**: Premium Widget
    *   **Product Type**: Storable Product (Crucial! "Service" or "Consumable" types do not track stock)
    *   **Sales Price**: $50.00
    *   **Cost**: $25.00
4.  Go to the **Inventory** tab (if available in your view) to verify the "Storable" setting.
5.  Click **Create**.

---

## Step 4: Receive Your First Stock

Now, let's bring 100 widgets into the "Receiving Dock".

1.  Navigate to **Inventory > Operations > Transfers**.
2.  Click **Create Transfer** (or **Create Picking**).
3.  Configure the Transfer:
    *   **Operation Type**: Receipts (or NH: Receipts if configured)
    *   **Source Location**: Partner Locations/Vendors (represents the supplier)
    *   **Destination Location**: NH/Receiving Dock
4.  Add the Product:
    *   **Product**: Premium Widget
    *   **Quantity**: 100
5.  Click **Save** (creates the Draft).
6.  Click **Confirm** (locks the plan).
7.  Click **Validate** (executes the move).

---

## Step 5: Check Your Stock

Finally, verify that the system recorded the inventory correctly.

1.  Navigate to **Inventory > Reporting > Stock Quantities**.
2.  Search for "Premium Widget".
3.  You should see:
    *   **Location**: NH/Receiving Dock
    *   **On Hand Quantity**: 100.00

---

## Next Steps

Congratulations! You have successfully set up a warehouse structure and received your first inventory.

*   [Manage Stock Moves](../how-to/stock-movements.md)
*   [Learn about Inventory Concepts](../explanation/inventory-concepts.md)
