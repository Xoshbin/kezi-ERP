---
title: Recording Payments
icon: heroicon-o-banknotes
order: 10
---

# How to Record Payments

Recording payments is the process of documenting cash inflows or outflows in Kezi. This guide covers how to record a payment and link it to existing documents (Vendor Bills or Customer Invoices).

## Recording a Payment from a Bill/Invoice

The most common way to record a payment is directly from the document you are paying.

1.  Navigate to **Accounting > Vendor Bills** (or **Sales > Invoices**).
2.  Open the document you wish to pay.
3.  Click the **Register Payment** button.
4.  In the slide-over/modal:
    - **Journal**: Select the Bank or Cash journal.
    - **Amount**: The system pre-fills the balance due, but you can adjust it for partial payments.
    - **Payment Date**: Enter the date the payment was made.
    - **Memo/Reference**: Add any identifying information.
5.  Click **Create Payment**.

The document status will update to **Partially Paid** or **Paid**.

## Recording a Standalone Payment

You can also record a payment independently and link it to documents later.

1.  Navigate to **Accounting > Payments**.
2.  Click **New Payment**.
3.  Select the **Payment Type** (Receive Money or Send Money).
4.  Select the **Partner** (Vendor or Customer).
5.  Enter the **Amount** and **Journal**.
6.  Click **Confirm**.

### Linking Standalone Payments to Bills

If you created a standalone payment, you must link it to the appropriate bill to clear the debt:

1.  Open the **Payment** record.
2.  Scroll to the **Allocated Documents** section.
3.  Select the Bills or Invoices this payment should cover.
4.  Specify the **Amount Applied** for each document.
5.  Save the record.

## Confirming the Payment

New payments are created in **Draft** status. While in draft, they have no impact on the General Ledger.

1.  Review the payment details.
2.  Click **Confirm**.
3.  The system generates a Journal Entry moving the balance from the Partner Account to the **Outstanding Receipts/Payments** account.

## Next Steps
- [Understanding Reconciliation](../explanation/understanding-reconciliation.md)
- [How to Reconcile Bank Statements](bank-reconciliation.md)
