# How to Reconcile Bank Statements

Bank reconciliation ensures that your internal accounting records match your actual bank balance. This guide explains how to use the **Reconciliation Matcher** to bridge the gap between your bank statements and internal payments.

## Prerequisites

Before reconciling, ensure:
- You have recorded and **Confirmed** your internal payments.
- You have created or imported a **Bank Statement** with transaction lines.
- Reconciliation is enabled in **Company Settings**.

## 1. Access the Reconciliation Matcher

1.  Navigate to **Accounting > Bank Statements**.
2.  Locate the statement you wish to reconcile.
3.  Click the **Reconcile** action (green scale icon).

## 2. Match a Statement Line to a Payment

The interface is divided into Bank Transactions (left) and System Payments (right).

1.  Select a **Bank Transaction** on the left.
2.  The system will suggest matching **System Payments** on the right based on the amount.
3.  Select the corresponding payment.
4.  Verify that the **Remaining Balance** at the bottom is zero.
5.  Click **Reconcile Selected**.

The payment status will update to `Reconciled`, and the statement line will be marked as matched.

## 3. Reconcile Multiple Items (Batch Match)

You can match multiple bank lines against multiple payments if their totals align.

1.  Select multiple checkboxes for **Bank Transactions**.
2.  Select multiple checkboxes for **System Payments**.
3.  Ensure the totals match and the difference is zero.
4.  Click **Reconcile Selected**.

## 4. Handle Discrepancies (Direct Write-off)

If a bank transaction has no matching internal payment (e.g., bank fees or interest), you can record it directly:

1.  Select the **Bank Transaction**.
2.  Click the **Manual Entry / Write-off** tab.
3.  Select an **Account** (e.g., Bank Service Charges).
4.  Enter the amount and description.
5.  Click **Reconcile**.

The system will create the necessary journal entry and reconcile the line.

## Next Steps
- [Understanding Reconciliation](../explanation/understanding-reconciliation.md)
- [How to Record Payments](recording-payments.md)
