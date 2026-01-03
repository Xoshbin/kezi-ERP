---
title: Understanding Fiscal Years
icon: heroicon-o-calendar-days
order: 7
---

# Understanding Fiscal Years

This guide explains how to manage Fiscal Years in your accounting system—from setting up your financial calendar to closing the books at year-end. Written for everyone, it covers the "why" and "how" of financial periods without the accounting jargon.

---

## What is a Fiscal Year?

A **Fiscal Year** is the one-year period that your company uses for financial reporting and budgeting.

While many businesses use the standard calendar year (January 1st to December 31st), a "Fiscal Year" can start on any date. For example, a school might run from September 1st to August 31st.

**Why does this matter?**

1.  **Performance Scorecard**: It sets the start and finish line for measuring your annual profit or loss.
2.  **Tax Compliance**: Governments require you to report income based on established years.
3.  **Data Safety**: "Closing" a year locks your old data so nobody can accidentally change history.

---

## The Lifecycle of a Financial Year

In our system, a Fiscal Year moves through four distinct stages. Think of it like a sports season:

### 1. 📝 Draft
**What it is**: Planning mode.
- You are setting up the dates (e.g., "Year 2026").
- You haven't started using it yet.
- You can freely delete or change dates.

### 2. 🟢 Open
**What it is**: Game on!
- This is where you do your daily business: creating invoices, paying bills, and recording journals.
- The system allows you to post entries within these dates.
- You can create monthly **Periods** (Jan, Feb, Mar) to control access even more tightly.

### 3. ⚠️ Closing
**What it is**: The referee is blowing the whistle.
- You have decided to end the year.
- The system is checking your data for errors (like draft entries that need posting).
- You are reviewing your final Profit & Loss numbers before locking them in.

### 4. 🔒 Closed
**What it is**: The season is over. The trophy is in the cabinet.
- **No changes allowed**: You cannot add, edit, or delete any transactions in this year.
- **Why?** Once you report your numbers to the tax office or investors, they cannot change.
- **Mistakes?** If you find a major error later, you must "Reopen" the year (with strict permission) or make a correction in the *current* year.

---

## The "Closing" Process: Resetting the Scoreboard

One of the most confusing parts of accounting is "Closing the Year." Let's simplify it.

Imagine a scoreboard at a basketball game.
*   **During the game (The Year)**: Points go up (Income) and fouls go up (Expenses).
*   **At the end of the game (Closing)**: You record the final score.
*   **Start of next game (New Year)**: The scoreboard resets to 0-0.

### The Accounting Magic Trick

When you click "Close Fiscal Year," the system performs a magic trick called the **Closing Entry**:

1.  **Zero Out P&L**: It takes all your Income accounts and Expense accounts and makes their balance **zero**. (Resetting the scoreboard).
2.  **Calculate Net Result**: It takes the difference (Profit or Loss) and moves it to a special account called **Retained Earnings**.
3.  **Keep the Balance Sheet**: Accounts like Bank, Inventory, and Debt **DO NOT** reset. If you have $1,000 in the bank on Dec 31st, you still have $1,000 on Jan 1st.

> **Example**:
> *   You made $100,000 in Revenue.
> *   You spent $80,000 in Expenses.
> *   **Net Profit**: $20,000.
>
> When you close, the system moves that $20,000 into "Retained Earnings" (Equity) and resets your Revenue and Expenses to $0 for the new year.

---

## How to Manage Fiscal Years

### Creating a New Year

1.  Go to **Accounting** → **Configuration** → **Fiscal Years**.
2.  Click **New Fiscal Year**.
3.  **Name**: Give it a clear name (e.g., "2026").
4.  **Dates**: Select Start and End dates (usually Jan 1 - Dec 31).
5.  **Generate Periods**: Turn this **ON** to automatically create 12 monthly periods (Jan, Feb, etc.). This helps you lock singular months (like "Close January") without closing the whole year.

### Generating Opening Entries

If you have a previous fiscal year in the system, you don't need to manually create opening balances. The system can calculate them for you.

1.  **Requirement**: The previous year must exist in the system.
2.  **Action**: Open your new Fiscal Year (e.g., "2026") and click **Generate Opening Entry**.
3.  **Result**: 
    *   The system takes all Assets, Liabilities, and Equity from the previous year.
    *   It creates a new "Opening Balance" journal entry on the 1st day of the new year.
    *   Income and Expense accounts are excluded (they reset to 0).
    *   **Review**: The entry is created as a **Draft**, so you can check it before posting.

### Closing the Year (The Wizard)

When you are ready to finish a year:

1.  Open the Fiscal Year record.
2.  Click **Close Fiscal Year**. A wizard will appear:
    *   **Step 1 (Preview)**: Verify your Total Income, Expenses, and Net Income. Does it look right?
    *   **Step 2 (Configuration)**: Select your **Retained Earnings** account. This is where the profit goes.
3.  Click **Close**.

**What happens next?**
*   A **Closing Journal Entry** is created automatically.
*   The year status changes to **Closed**.
*   All periods within that year are marked as Closed.
*   A **Lock Date** is set, preventing any changes before the end date.

### Reopening a Year (Emergency Only)

Did you make a mistake? Did the auditor find something huge?

1.  Open the Closed Fiscal Year.
2.  Click **Reopen Fiscal Year**.
3.  Confirm the action.

**What happens?**
*   The **Closing Journal Entry** is reversed (cancelled out).
*   The year becomes **Open** again.
*   You can now make corrections.
*   **Remember**: You must close it again when you are done!

---

## Closing Individual Periods

Sometimes you don't want to wait until year-end to lock your books. You can close **individual periods** (e.g., January) to prevent changes while the rest of the year stays open.

### Why Close Periods?

1.  **Monthly Compliance**: Many companies need to "lock" each month after their accountant reviews it.
2.  **Mistake Prevention**: Once January is closed, nobody can accidentally post an invoice dated in January.
3.  **Audit Trail**: Auditors like to see that old months cannot be modified.

### How to Close a Period

1.  Go to **Accounting** → **Fiscal Years**.
2.  Open the Fiscal Year you want to manage.
3.  Scroll down to the **Fiscal Periods** table.
4.  Find the period you want to close (e.g., \"January 2024\").
5.  Click the **Close Period** button.

**What happens?**
*   The period status changes to **Closed**.
*   The system automatically updates the **Lock Date** to the period's end date.
*   Any attempt to create or modify transactions before this date will be blocked.

### Reopening a Period

Made a mistake? You can reopen a closed period as long as the **parent Fiscal Year is still open**.

1.  Find the closed period in the table.
2.  Click the **Reopen Period** button.
3.  The lock date will automatically adjust to the previous closed period's end date.

> [!WARNING]
> If the Fiscal Year itself is closed, you cannot reopen individual periods. You must reopen the entire year first.

---

## Troubleshooting Common Questions

**Q: Why does the system say "Validation Failed" when I try to close?**
A: The system protects you from bad data. Common reasons:
*   **Draft Entries**: You have journal entries that haven't been posted. You must either Post them or Delete them.
*   **Open Periods**: If you use monthly periods, you should close them individually first (though the system may allow bulk closing depending on settings).

**Q: I closed the year, but my Bank Balance didn't reset to zero!**
A: **That is correct!** Asset accounts (Bank, Cash, Inventory) and Liability accounts (Loans, Payables) are *never* reset. Only "Performance" accounts (Income/Expense) are reset to zero.

**Q: Can I have two Open fiscal years at the same time?**
A: **Yes.** It is common to have the new year (2027) open for daily work while you are still finalizing and closing the old year (2026).

**Q: What is "Retained Earnings"?**
A: It is an Equity account that represents the total accumulated profit of your company since day one. It tells you how much value the business has kept over its lifetime.

---

## Related Documentation

- [Journal Entries](journal-entries.md) - How individual transactions work
- [Chart of Accounts](chart-of-accounts.md) - Understanding Account Types
- [Reports](reports.md) - Viewing your P&L and Balance Sheet
