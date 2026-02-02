---
title: Manage Vendor Bills
icon: heroicon-o-document-text
order: 14
---

This guide covers the practical steps for creating, managing, and paying vendor bills in the system.

For a conceptual overview of vendor bills and accounting impacts, see [Understanding Vendor Bills](../explanation/understanding-vendor-bills.md).
For a step-by-step walkthrough for beginners, see [Tutorial: Recording Your First Vendor Bill](../tutorials/recording-your-first-vendor-bill.md).

---

## Create a Standard Vendor Bill

1.  Navigate to **Accounting > Purchases > Vendor Bills**.
2.  Click **Create**.
3.  **Bill Header**:
    *   **Vendor**: Select the supplier.
    *   **Bill Date**: The date on the received invoice.
    *   **Reference**: The vendor's invoice number (critical for duplicate detection).
4.  **Invoice Lines**:
    *   Select a **Product** for automatic account coding, or manually enter a **Label** and **Account**.
    *   Verify the **Quantity** and **Price**.
    *   Ensure the correct **Taxes** are selected.
5.  **Attachments**: Drag and drop the scanned invoice PDF onto the form or use the attachment clip icon to upload it.
6.  Click **Post** to finalize the bill.

## Register a Payment

Once a bill is posted, you can register a payment directly from the bill view.

1.  Open the Posted Bill.
2.  Click the **Register Payment** button.
3.  **Journal**: Select the Bank or Cash journal you are paying from.
4.  **Amount**: The full amount is selected by default. Change this if making a partial payment.
5.  **Payment Date**: Date the money left your account.
6.  Click **Create Payment**.

The bill status will change to **In Payment** or **Paid**.

## Handle a Credit Note (Vendor Refund)

If a vendor sends you a refund or you return goods, you must record a Credit Note (Debit Note).

1.  Navigate to **Accounting > Purchases > Refunds**.
2.  Click **Create**.
3.  Fill it out exactly like a Vendor Bill, but note that the accounting entry is reversed (Debits Accounts Payable).
4.  **Tip**: You can also open an existing Vendor Bill and click **Add Credit Note** to pre-fill the refund with the original bill's data.

## Manage Specific Scenarios

### Multi-Currency Bills
1.  On the bill header, change the **Currency** to the vendor's currency (e.g., USD).
2.  The system effectively locks the exchange rate at the moment of posting based on the daily rate.
3.  To use a custom rate, edit the Exchange Rate field before posting.

### Recurring Bills
For monthly expenses like rent or internet:
1.  Create a bill as usual.
2.  Instead of posting, look for the **Recurring** action (or "Create Recurring Template").
3.  Set the frequency (e.g., Monthly) and the next execution date.
4.  The system will automatically generate a Draft bill on the scheduled date.

### 3-Way Matching (Bill + PO + Receipt)
If you use the Inventory app:
1.  Make sure the Purchase Order (PO) is confirmed and products are received.
2.  On the Vendor Bill, use the **Auto-Complete** feature (often found near the Vendor name or as a "Generate from PO" button).
3.  Select the relevant Purchase Order.
4.  The system pulls in line items based on what has been *received*, not just what was ordered, ensuring you only pay for what you got.

## Troubleshooting

*   **Cannot Post?**: Check if the **Bill Date** is in a closed fiscal period.
*   **Wrong Tax Amount?**: Verify the Tax setting on the specific line item. You can manually adjust the tax amount field if there is a rounding difference with the vendor's paper invoice.
