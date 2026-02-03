---
title: Setting Up Your First Manufacturing Line
icon: heroicon-o-academic-cap
order: 10
---

# Tutorial: Setting Up Your First Manufacturing Line

This tutorial will guide you through the process of setting up your first manufacturing line in Kezi ERP. By the end of this guide, you will have configured a work center, created a Bill of Materials, and completed your first manufacturing order.

---

## 1. Create a Work Center

First, we need to define where the work happens.

1.  Navigate to **Manufacturing → Configuration → Work Centers**.
2.  Click **New Work Center**.
3.  Enter a **Name** (e.g., "Assembly Line 1").
4.  Set the **Working Hours** (Standard 40 hours/week).
5.  Set the **Capacity** (e.g., 1 unit at a time).
6.  Click **Save**.

## 2. Create the Bill of Materials (BoM)

Now, let's define the "recipe" for your product.

1.  Navigate to **Manufacturing → Products → Bills of Materials**.
2.  Click **New Bill of Materials**.
3.  Select the **Product** you want to build (ensure the product type is "Storable").
4.  In the **Components** tab, add the raw materials needed.
    *   Example: For a "Wooden Chair", add "Wood" (quantities as needed).
5.  In the **Routing** tab, add the Work Center you created in Step 1.
6.  Click **Save**.

## 3. Plan Your First Manufacturing Order

With the BoM ready, we can start production.

1.  Navigate to **Manufacturing → Operations → Manufacturing Orders**.
2.  Click **New Manufacturing Order**.
3.  Select the **Product** (the system will automatically load the active BoM).
4.  Enter the **Quantity to Produce**.
5.  Click **Confirm**.

## 4. Execute and Complete Production

1.  Open your **Confirmed** Manufacturing Order.
2.  Click **Start Production** when work begins.
3.  In the **Work Orders** tab, click **Start** on the operation.
4.  Once the work is done, click **Done** on the work order.
5.  Back on the main Manufacturing Order, click **Produce All** or **Confirm** to finish.
6.  The MO status will change to **Done**, and your finished goods are now in stock!
