---
title: Understanding Reversals
icon: heroicon-o-arrow-path-rounded-square
order: 10
---

# Understanding Reversals: Fixing Financial Errors

This guide explains how to correct mistakes in your financial records safely. In accounting, we follow a golden rule: **Never Delete, Always Reverse.**

---

## The Golden Rule: Never Delete, Always Reverse

In a perfect world, we wouldn't make mistakes. In the real world, we do. When you record a transaction incorrectly (like a wrong invoice amount or a payment sent to the wrong vendor), you might be tempted to just hit "Delete."

**But in accounting, deleting is dangerous because:**
1.  **It breaks the audit trail**: You lose the history of what happened.
2.  **It creates gaps**: Invoice numbers must be sequential (INV-001, INV-002, INV-003). Deleting INV-002 makes auditors suspicious.
3.  **It's often illegal**: Many tax regulations forbid deleting posted transactions.

**The Solution: Reversal**
Instead of deleting, we create a **Reversal** (or Correction) entry. This is a new transaction that effectively "zeros out" the mistake.

### Visualizing the Concept

Think of it like math. If you added +100 by mistake, you don't erase it. You add -100 to get back to zero.

```
Original Mistake:   + $100.00  (Wrong Revenue)
Reversal Entry:     - $100.00  (Negative Revenue)
---------------------------------
Net Result:           $0.00    (Corrected)
```

---

## Reversing Invoices

When a customer returns an item or you billed them incorrectly, you reverse the invoice.

### For Customer Invoices (Sales)
Reversing a customer invoice creates a **Credit Note**.
1.  Navigate to the posted Invoice.
2.  Click **Create Credit Note** (or "Reverse").
3.  The system creates a new document (Credit Note) with negative amounts.
4.  **Reconcile** the Credit Note against the original Invoice to "pay" it off.

### For Vendor Bills (Purchases)
Reversing a vendor bill creates a **Debit Note** (also called a Vendor Refund/Credit).
1.  Navigate to the posted Bill.
2.  Click **Create Debit Note**.
3.  This reduces the amount you owe the vendor.

---

## Reversing Payments

Sometimes you record a payment by mistake—maybe you selected the wrong date, the wrong journal, or the check bounced.

### How to Reverse a Payment
1.  Go to **Accounting → Payments**.
2.  Open the confirmed Payment.
3.  Click **Cancel** (or Reverse).
    *   *Note: If the payment is already reconciled with a bank statement, you must **Unreconcile** it first.*
4.  The system changes the status to **Cancelled** and posts a reversing journal entry to fix your ledger.

### What happens behind the scenes?
If you originally recorded:
```
Dr. Bank            $500
    Cr. Accounts Receivable  $500
```

The reversal will record:
```
Dr. Accounts Receivable  $500
    Cr. Bank                 $500
```
This puts the money "back" into the customer's debt (so they still owe you) and removes it from your bank balance in the system.

---

## Unreconciling: Unmatching a Payment

Sometimes the payment itself is correct, but it was matched (reconciled) to the wrong invoice. You don't need to reverse the *payment*, just the *matching*.

1.  Go to the Payment or the Invoice.
2.  Look for the **Payment Allocation** or **Credits** section.
3.  Click **Unreconcile** or **Remove Allocation**.

**Result:**
*   The Invoice becomes **Open** (Unpaid) again.
*   The Payment becomes **Unallocated** (it's sitting in the customer's account, ready to be incorrectly applied).
*   No money "moved," only the link between documents was broken.

---

## Troubleshooting

### Q: Why is the "Reverse" or "Cancel" button grayed out?
This usually happens for two reasons:

1.  **Lock Dates**: The transaction belongs to a period that has been locked (closed). You cannot change history in a closed year/month. You must ask an administrator to unlock the period or create the correction in the *current* open period.
2.  **Reconciled**: You cannot cancel a payment that has been reconciled with a Bank Statement. You must first go to the Bank Reconciliation and remove the match there.

### Q: Can I just edit the invoice instead?
If the invoice is in **Draft** status, yes! You can edit, delete, or change anything.
Once it is **Posted**, it is legal and final. You must use the Reversal method.
