---
title: Deferred Revenue & Expenses
icon: heroicon-o-clock
order: 8
---

# Deferred Revenue & Expenses: Managing Future Income and Costs

This guide explains how to handle deferred revenues and expenses in the system. Whether you are dealing with subscriptions, insurance, or prepaid rent, this feature helps you recognize income and expenses in the correct accounting periods.

---

## What are Deferred Items?

**Deferred Items** track money that has been exchanged but not yet "earned" or "used" for accounting purposes. This ensures your financial reports follow the **matching principle**—matching revenues and expenses to the period they actually relate to.

There are two main types:

1.  **Deferred Revenue (Unearned Revenue)**: Money you received from a customer for services you haven't provided yet.
    -   *Example:* A customer pays $1,200 upfront for a 1-year software subscription. You get the cash now, but you "earn" $100 each month.
    -   **Accounting Impact**: Liability (you owe them service).

2.  **Deferred Expenses (Prepaid Expenses)**: Money you paid for something you haven't used yet.
    -   *Example:* You pay $6,000 for a year of business insurance. You paid it all, but the "expense" should be spread over 12 months.
    -   **Accounting Impact**: Asset (you have a right to coverage).

**Why does this matter?**
-   **Accuracy**: Shows your true profit for each month.
-   **Compliance**: Required for many accounting standards (like accrual accounting).
-   **Better Decisions**: Prevents seeing a huge "profit" one month and "losses" the next just because of timing.

---

## Where to Find It

Navigate to: **Accounting → Accounting → Deferred Items**

Here you'll see a list of all active deferrals, their total amounts, and how much is remaining to be recognized.

> **💡 Tip**: You rarely create these manually. They are usually created automatically from Invoices and Vendor Bills!

---

## How to Create Deferred Items

The best way to create a deferred item is directly from the source document (Invoice or Bill).

### 1. Deferred Revenue (From Customer Invoice)

When selling a subscription or service that spans time:

1.  Go to **Sales → Invoices → Create New**.
2.  Add a line item (e.g., "Annual Support Plan").
3.  In the line item details, look for the **Deferred Periods** or dates.
4.  Set the **Start Date** and **End Date** (e.g., Jan 1st to Dec 31st).
5.  **Confirm** the invoice.

**What happens?**
-   The system automatically creates a **Deferred Revenue** record.
-   It calculates the monthly schedule based on the dates.
-   The revenue is moved to a "Deferred Revenue" liability account instead of your normal "Sales" account.

### 2. Deferred Expense (From Vendor Bill)

When paying for something in advance:

1.  Go to **Accounting → Vendor Bills → Create New**.
2.  Add a line item (e.g., "Office Rent - Q1").
3.  Set the **Start Date** and **End Date** for the coverage (e.g., Jan 1st to Mar 31st).
4.  **Confirm** the bill.

**What happens?**
-   The system creates a **Deferred Expense** record.
-   The cost is booked to a "Prepaid Expense" asset account primarily.

> **! IMPORTANT**: For this to work, the Product setup must have the correct accounts linked (Deferred Revenue Account or Deferred Expense Account).

---

## The Deferral Schedule

Once created, you can view the schedule by clicking on any **Deferred Item**.

### Understanding the Schedule
The system uses the **Straight-Line Method** to spread the amount equally over the months.

**Example**: $1,200 Subscription (Jan - Dec)

| Date | Amount | Status |
|------|--------|--------|
| Jan 31 | $100 | Posted |
| Feb 28 | $100 | Posted |
| Mar 31 | $100 | Draft |
| ... | ... | ... |
| Dec 31 | $100 | Draft |

-   **Draft**: Scheduled for the future.
-   **Posted**: Already recognized in your books.

---

## Automatic Recognition

You don't need to manually post entries every month!

The system runs a **Daily Job** that:
1.  Checks for any "Draft" lines where the date is today or in the past.
2.  Automatically **Posts** the journal entry:
    -   *Revenue*: Dr. Deferred Revenue / Cr. Sales Income
    -   *Expense*: Dr. Expense Account / Cr. Prepaid Expense
3.  Updates the line status to **Posted**.

This means your Balance Sheet and P&L update automatically as time passes. 🪄

---

## Common Scenarios

### Scenario 1: Annual Insurance Policy
**Situation**: You pay $12,000 for liability insurance on Jan 1st.
1.  Create Vendor Bill for $12,000.
2.  Set Start: Jan 1, End: Dec 31.
3.  **Result**:
    -   Jan P&L shows $1,000 expense (not $12,000).
    -   Feb P&L shows $1,000 expense.
    -   ...and so on.

### Scenario 2: Two-Year Contract
**Situation**: You sign a client for a 24-month maintenance contract for $24,000.
1.  Create Invoice.
2.  Set Start: Jan 1, 2024, End: Dec 31, 2025.
3.  **Result**:
    -   Revenue is recognized at $1,000/month for 24 months.
    -   Keeps your monthly revenue steady and comparable.

---

## Troubleshooting

### Q: Why didn't a Deferred Item get created?
Check these common reasons:
1.  **Missing Dates**: Did you set the Start/End dates on the Invoice/Bill line?
2.  **Product Setup**: Does the product have a `Deferred Revenue Account` (for sales) or `Deferred Expense Account` (for purchases) set in its category or settings?
3.  **Status**: Is the Invoice/Bill confirmed? Draft documents don't create accounting entries.

### Q: Can I change the schedule?
Once created, the schedule is generally fixed based on the original dates. If you made a mistake:
1.  **Cancel** the original Invoice/Bill (creates a Credit Note).
2.  Create a **New** Invoice/Bill with the correct dates.
This ensures the audit trail remains clean.

### Q: Why isn't it posting?
The background job might not have run yet.
-   Wait for the nightly system process.
-   Check if the "Recognition Date" has actually passed.
