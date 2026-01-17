---
title: Lock Dates
icon: heroicon-o-lock-closed
order: 11
---

# Lock Dates: Protect Your Accounting Records

This guide explains how to use Lock Dates to secure your financial records and prevent accidental or unauthorized changes to completed accounting periods. Whether you're closing month-end books or preparing for a tax audit, lock dates ensure your historical data remains intact.

---

## What are Lock Dates?

Think of Lock Dates like a "seal" on your accounting books. Once you've finalized your monthly or yearly financial statements and reported them to stakeholders or tax authorities, you set a lock date to prevent anyone from making changes to transactions before that date.

**Why does this matter?**
1. **Data Integrity**: Prevents retrospective changes that would invalidate published financial reports.
2. **Audit Compliance**: Demonstrates control over your accounting records for auditors and regulators.
3. **Error Prevention**: Stops accidental edits to closed periods, reducing the risk of inconsistencies.

> [!IMPORTANT]
> Lock dates are **critical** for maintaining the accuracy and trustworthiness of your financial statements. Once a period is locked, transactions dated before the lock date cannot be created, edited, or deleted.

---

## Where to Find It

Navigate to: **Settings → Lock Dates**

From here you can view all configured lock dates for your company and create new ones as needed.

> [!TIP]
> Look for the Help button in the top-right corner—it opens this guide!

---

## Understanding Lock Date Types

There are **three types** of lock dates, each with different levels of restriction:

### 📊 Tax Return Date (Lock for Non-Advisers)
**Best for:** Monthly/quarterly closings where accountants need flexibility

- **Who it affects**: Regular users (non-accounting staff)
- **Who can still edit**: Users with accounting/adviser permissions
- **Common use**: Closing month-end while allowing accountants to make adjustments

**Example scenario:** You've closed March and prepared monthly reports for management. You set a Tax Return lock date to March 31st. Now, sales staff can't accidentally edit March invoices, but your accountant can still make corrections if needed.

---

### 🔐 All Users Lock (Lock for Everyone)
**Best for:** Year-end closings or periods audited by external parties

- **Who it affects**: **Everyone**, including accountants and administrators
- **Who can still edit**: Only system administrators with special override permissions
- **Common use**: Annual closing after financial statements are audited and filed

**Example scenario:** Your 2024 financial statements have been audited and filed with tax authorities. You set an All Users lock date to December 31, 2024. Now, **no one** can edit any 2024 transactions without explicit administrator approval.

---

### 🚫 Hard Lock (Permanent Lock)
**Best for:** Legally finalized periods that must never change

- **Who it affects**: **Everyone** without exception
- **Who can still edit**: **No one** — this lock cannot be removed or bypassed
- **Common use**: Statutory filings, court-ordered records, or regulatory submissions

> [!CAUTION]
> A **Hard Lock** is permanent and irreversible. Once set, it cannot be edited or deleted, even by administrators. Use this only when you are absolutely certain the period is finalized forever.

**Example scenario:** After a legal audit, the court accepts your 2023 financial records as evidence. You set a Hard Lock on December 31, 2023 to ensure these records can never be altered, preserving their legal validity.

---

## Setting a Lock Date

Let's walk through creating a new lock date step by step.

### Step 1: Navigate to Lock Dates

Go to **Settings → Lock Dates** and click **New Lock Date**.

### Step 2: Choose the Lock Type

Select the appropriate lock type based on your needs:

| Lock Type | When to Use | Can Be Changed Later? |
|-----------|-------------|----------------------|
| **Tax Return Date** | Monthly/quarterly closings | ✅ Yes, can edit or delete |
| **All Users Lock** | Year-end closings | ✅ Yes, can edit or delete |
| **Hard Lock** | Legal/statutory filings | ❌ No, permanent |

> [!WARNING]
> Think carefully before choosing **Hard Lock**. This decision cannot be reversed!

### Step 3: Set the Lock Date

Choose the date **up to which** the period should be locked.

- **Locked Until**: The last date of the period you want to protect
- **Example**: To lock all of January, set "Locked Until" to January 31

**What this means:**
- ✅ Transactions on or before this date: **Locked** (cannot edit)
- ✅ Transactions after this date: **Not locked** (can edit normally)

### Step 4: Save

Click **Create** to activate the lock date.

> [!NOTE]
> The lock takes effect immediately. Any existing transactions dated on or before the lock date will become read-only for the affected users.

---

## What Happens When a Period is Locked?

When users try to create or edit transactions in a locked period, they will see an error message:

```
⚠️ The period is locked until [date]. 
   You cannot create or modify transactions dated on or before this date.
```

### Locked Actions:
- ❌ Creating new transactions dated before the lock date
- ❌ Editing existing transactions dated before the lock date
- ❌ Deleting transactions dated before the lock date
- ❌ Posting draft transactions dated before the lock date

### Still Allowed:
- ✅ Viewing historical transactions (read-only access)
- ✅ Creating transactions dated **after** the lock date
- ✅ Generating reports for locked periods

---

## Best Practices

### 📅 Timing Your Lock Dates

**Month-End Closing:**
1. Complete all month-end adjustments (accruals, depreciation, reconciliations)
2. Generate and review financial statements
3. Obtain approval from management
4. **Then** set a **Tax Return Date** lock to close the month

**Year-End Closing:**
1. Complete all year-end procedures
2. External audit (if applicable)
3. File financial statements with authorities
4. **Then** set an **All Users Lock** to close the year

**Legal Filings:**
- Only use **Hard Lock** after final, irrevocable submissions (tax returns accepted by authorities, court-ordered records, etc.)

---

### ✅ Progressive Locking Strategy

Use a layered approach for maximum control:

```
┌─────────────────────────────────────────────────────────────┐
│  January (Tax Return Lock) → Users locked, Accountants OK   │
│  February (All Users Lock) → Everyone locked after audit    │
│  March (Hard Lock) → Permanent after legal filing           │
└─────────────────────────────────────────────────────────────┘
```

**Recommended workflow:**
1. **Tax Return Lock** → After monthly close (protects from accidental user edits)
2. **All Users Lock** → After year-end audit (prevents any internal changes)
3. **Hard Lock** → After statutory filing (permanent legal record)

---

### 🔒 Who Should Manage Lock Dates?

**Recommended permissions:**
- **Set Tax Return Locks**: Month-end accountants, controllers
- **Set All Users Locks**: CFO, financial controller, external auditors
- **Set Hard Locks**: CFO only, with documented approval process

> [!TIP]
> Implement an approval workflow for lock dates—require management sign-off before locking periods to avoid premature closures.

---

### 📝 Documentation

**Keep records of:**
- When lock dates were set
- Who authorized the lock
- What reports/filings justified the lock
- Any special circumstances (audits, legal requirements)

This audit trail is valuable for compliance and internal controls.

---

## Unlocking a Period (If Needed)

### Tax Return Lock or All Users Lock

If you need to make corrections to a locked period:

1. Navigate to **Settings → Lock Dates**
2. Find the lock date record
3. **Option A:** Edit the date to an earlier date (temporarily unlocks the period)
4. **Option B:** Delete the lock date entirely (removes the lock)
5. Make your corrections
6. Re-apply the lock date

> [!WARNING]
> Unlocking a period should be rare and well-documented. Each unlock weakens your internal controls and may raise audit concerns.

### Hard Lock

**Cannot be unlocked.** Hard locks are permanent by design.

If you discover an error in a hard-locked period, you have two options:
1. **Correcting Entry**: Make an adjusting journal entry in the current period with a note referencing the error
2. **Legal Process**: In extreme cases, consult legal counsel about amending filed records through official channels

---

## Troubleshooting

### Common Questions

**Q: Can I have multiple lock dates active at once?**

A: Yes! You can have different lock types for different periods. For example:
- Tax Return Lock: June 30 (protects H1)
- All Users Lock: December 31, 2023 (protects all of 2023)
- Hard Lock: December 31, 2022 (permanently locks 2022)

**Q: I accidentally set a Hard Lock too early. What can I do?**

A: Unfortunately, nothing. Hard Locks cannot be removed. This is why we strongly recommend using Tax Return or All Users locks first, and only applying Hard Locks after absolute certainty.

**Q: How do lock dates interact with fiscal year closing?**

A: Lock dates are independent of fiscal year status. You can (and should) lock periods even if the fiscal year is still "Open" in the system. Lock dates provide transaction-level protection, while fiscal year status is for reporting organization.

**Q: What if I need to record a transaction dated in a locked period?**

A: You have three options:
1. **Date the transaction later**: Use the current date instead of the historical date, with a note explaining the backdating
2. **Unlock temporarily**: If using Tax Return or All Users lock, temporarily remove or adjust the lock
3. **Correcting entry**: Make an adjusting entry in the current period to correct the impact

---

## Related Documentation

- [Fiscal Years](fiscal-years.md) - Managing accounting periods
- [Journal Entries](journal-entries.md) - Understanding transaction entries
- [Audit Logs](audit-logs.md) - Tracking who changed what

---

## Glossary

- **Lock Date**: A date before which transactions cannot be modified
- **Tax Return Date**: A lock that applies to non-accounting users only
- **All Users Lock**: A lock that applies to everyone except administrators
- **Hard Lock**: A permanent, irreversible lock
- **Period Closing**: The process of finalizing an accounting period's records

---

*Keep your books secure with proper lock date management! 🔒*
