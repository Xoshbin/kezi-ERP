---
title: Recording Your First Vendor Bill
icon: heroicon-o-academic-cap
order: 20
---

In this tutorial, we will walk through one of the most common tasks in any business: receiving an invoice and recording it in the system.

We will simulate a simple scenario: **You have just received the monthly rent invoice from your landlord.**

By the end of this tutorial, you will understand how to enter the bill, verify the taxes, and confirm the debt in your Accounts Payable.

---

## Prerequisites

Before starting, ensure you have:
1.  Access to the **Accounting** module.
2.  A **Vendor** created in the system (e.g., "City Properties LLC").
3.  An **Expense Account** for Rent (usually 6000-Rent Expense).

---

## Step 1: Initiate a New Bill

1.  Navigate to **Accounting > Purchases > Vendor Bills**.
2.  Click the **Create** button in the top-right corner.
3.  You are now viewing a blank **Draft Bill**.

> **Note**: Notice the status bar in the top right shows **Draft**. This means your work is currently just a scratchpad and has no impact on your books yet.

---

## Step 2: Enter Header Information

The "Header" contains the main details about who sent the bill and when.

1.  **Vendor**: Select "City Properties LLC" (or create it on the fly).
2.  **Bill Date**: Enter the date printed on the invoice (e.g., today's date).
3.  **Accounting Date**: Usually auto-filled with the Bill Date. Leave this as is.
4.  **Reference**: Type the invoice number from the physical paper (e.g., `INV-2024-001`).

---

## Step 3: Add the Bill Line

Now we define what we are paying for.

1.  Under the **Invoice Lines** tab, click **Add Line**.
2.  **Product**: You can leave this empty if you don't track rent as a product, or select a "Rent" service product if you have one.
3.  **Label**: Type "Office Rent - January 2024".
4.  **Account**: Select your rent expense account (e.g., `6xxx Rent Expense`).
5.  **Quantity**: 1.
6.  **Price**: Enter `1,500,000` (IQD).
7.  **Taxes**: If your landlord charges VAT, select the tax here. For this tutorial, we will assume it is tax-exempt or inclusive.

Check the **Total** at the bottom right. It should match the total on your physical invoice.

---

## Step 4: Validate and Post

Review your data. Does the date match? Is the total correct?

1.  Click the **Post** button.
2.  The status changes from **Draft** to **Posted**.

Do you see the **Journal Items** tab appear? Click on it. You will see the double-entry accounting that just happened automatically:

*   **Debit**: Rent Expense (Increases expenses)
*   **Credit**: Accounts Payable (Increases what you owe)

---

## Step 5: Verify the Outcome

To confirm you really owe this money now:

1.  Go back to the main **Vendor Bills** list.
2.  You should see your bill listed with a status of **Posted** and Payment Status **Not Paid**.
3.  Navigate to **Accounting > Reporting > Aged Payables**.
4.  You will see "City Properties LLC" listed with a balance of 1,500,000 IQD.

## Summary

You have successfully converted a physical piece of paper into a digital accounting record. You have acknowledged the debt and recognized the expense. The next step in the real world would be to **Register Payment**, which clears this debt.
