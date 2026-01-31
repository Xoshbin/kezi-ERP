---
title: Currency Revaluation
icon: heroicon-o-currency-dollar
order: 15
---

# Currency Revaluation: Managing Exchange Rate Differences

This guide explains how to use Currency Revaluation to adjust your foreign currency balances to current exchange rates. Whether it's month-end or year-end closing, this process ensures your financial statements accurately reflect the value of foreign currency assets and liabilities.

---

## What is Currency Revaluation?

**Currency Revaluation** adjusts the book value of foreign currency balances to reflect current exchange rates. When exchange rates change between when you recorded a transaction and period-end, the value in your base currency changes—even though the foreign currency amount stays the same.

**Why does this matter?**

| Reason | Example |
|--------|---------|
| **Accurate balance sheet** | Your $10,000 USD receivable was worth 14.5M IQD when recorded, but is now worth 14.7M IQD |
| **Regulatory compliance** | IFRS and most accounting standards require period-end revaluation |
| **Financial visibility** | See unrealized gains/losses before they become realized |
| **Month-end closing** | Required step before finalizing monthly financial statements |

**Accounting Impact**: A revaluation adjustment recognizes unrealized exchange gains or losses in your Profit & Loss statement, while adjusting the book value of your foreign currency accounts.

---

## Where to Find It

Navigate to: **Accounting → Currency Revaluation**

You'll see a list of all revaluations with their status, date, and net adjustment amounts.

> [!TIP]
> Run currency revaluation at the end of each accounting period (monthly, quarterly, or annually) before generating financial reports.

---

## Currency Revaluation Workflow

Your revaluation goes through these stages:

┌─────────┐      ┌─────────┐      ┌──────────┐
│  Draft  │ ──▶ │ Posted  │ ──▶ │ Reversed │
└─────────┘      └─────────┘      └──────────┘
    📝              ✅              ↩️

### 📝 Draft
- System calculates unrealized gains/losses
- You can review and modify if needed
- No accounting impact yet
- Safe to delete if not needed

### ✅ Posted
- Journal entry created
- Gains/losses recorded in P&L
- Balance sheet accounts adjusted
- Can be reversed if needed

### ↩️ Reversed
- Offsetting journal entry created
- Useful if you need to re-run revaluation with different rates

---

## How the Calculation Works

For each foreign currency balance, the system calculates:

```
Book Value         = Foreign Balance × Historical Rate
Revalued Amount    = Foreign Balance × Current Rate
Adjustment         = Revalued Amount - Book Value
```

**Example**: You have $10,000 USD in accounts receivable

| Factor | Value |
|--------|-------|
| Foreign Balance | $10,000 USD |
| Historical Rate | 1 USD = 1,450 IQD |
| Book Value | 14,500,000 IQD |
| Current Rate | 1 USD = 1,470 IQD |
| Revalued Amount | 14,700,000 IQD |
| **Adjustment (Gain)** | **+200,000 IQD** |

---

## Which Accounts Are Affected?

Currency revaluation primarily affects accounts with foreign currency balances:

| Account Type | Example Accounts |
|--------------|------------------|
| **Accounts Receivable** | Customer invoices in USD, EUR, etc. |
| **Accounts Payable** | Vendor bills in foreign currencies |
| **Bank Accounts** | Foreign currency bank accounts |
| **Loan Accounts** | Foreign currency loans given or received |

> [!IMPORTANT]
> Only accounts with foreign currency transactions (different from your base currency) will show revaluation adjustments.

---

## Creating a Currency Revaluation

### Step 1: Start the Revaluation

1. Go to **Accounting → Currency Revaluation**
2. Click **Create Currency Revaluation**

### Step 2: Fill in the Details

| Field | What to Enter | Example |
|-------|---------------|---------|
| **Revaluation Date** | The date for rate lookup | Last day of month (Dec 31) |
| **Description** | Optional note | "December 2025 month-end revaluation" |

### Step 3: Review Calculated Lines

The system automatically:
- Finds all accounts with foreign currency balances
- Looks up current exchange rates for the revaluation date
- Calculates the adjustment for each account/currency/partner combination

You'll see for each line:
- **Account**: The account being revalued
- **Currency**: The foreign currency
- **Foreign Balance**: Amount in foreign currency
- **Historical Rate**: Average rate when transactions were recorded
- **Current Rate**: Rate on revaluation date
- **Book Value**: Current book value in base currency
- **Revalued Amount**: New value at current rate
- **Adjustment**: The difference (gain or loss)

### Step 4: Review Totals

Before posting, verify the totals:
- **Total Gain**: Sum of all positive adjustments
- **Total Loss**: Sum of all negative adjustments
- **Net Adjustment**: Overall gain or loss

### Step 5: Post the Revaluation

Once satisfied, click **Post** to:
- Create the journal entry
- Lock the revaluation record
- Update financial statements

---

## What Happens Behind the Scenes

When you post a revaluation with a net gain of 200,000 IQD on accounts receivable:

```
┌─────────────────────────────────────────────────────────────┐
│  Dr. Accounts Receivable           200,000 IQD              │
│      Cr. Unrealized Exchange Gain           200,000 IQD     │
└─────────────────────────────────────────────────────────────┘
```

**In plain English**: We're increasing the book value of our receivables to match the current exchange rate, and recording the gain in our income statement.

For a net loss of 150,000 IQD on accounts payable:

```
┌─────────────────────────────────────────────────────────────┐
│  Dr. Unrealized Exchange Loss      150,000 IQD              │
│      Cr. Accounts Payable                   150,000 IQD     │
└─────────────────────────────────────────────────────────────┘
```

---

## Common Scenarios

### Scenario 1: Month-End Closing

**The situation**: It's January 31st and you need to close the month. Your company has USD receivables and EUR payables.

**What to do**:

1. Ensure exchange rates for January 31st are recorded in the system
2. Create a new Currency Revaluation
3. Set **Revaluation Date** to January 31st
4. Review the calculated adjustments
5. **Post** the revaluation
6. Generate your month-end financial reports

**Result**:
- Balance sheet shows foreign currency items at current values
- P&L includes unrealized exchange gains/losses
- Trial balance is ready for period close

### Scenario 2: Year-End Audit Preparation

**The situation**: Auditors need to verify foreign currency valuations for year-end.

**What to do**:

1. Verify year-end exchange rates are accurate and documented
2. Create a revaluation for December 31st
3. Export or print the revaluation report showing:
   - Each foreign currency balance
   - Historical vs. current rates
   - Adjustment calculations
4. **Post** the revaluation
5. Provide the report and journal entry to auditors

### Scenario 3: Correcting a Wrong Revaluation

**The situation**: You posted a revaluation but discovered the exchange rate was incorrect.

**What to do**:

1. **Reverse** the posted revaluation (creates offsetting entry)
2. Update the exchange rate in the system
3. Create a new revaluation with the correct rate
4. **Post** the new revaluation

---

## Filters and Options

The revaluation list provides filtering options:

| Filter | Purpose |
|--------|---------|
| **Status** | View only Draft, Posted, or Reversed revaluations |
| **Date Range** | Find revaluations for specific periods |
| **Search** | Find by description or reference |

---

## Best Practices

### 📅 Timing
- **Revalue at period-end**: Always before closing monthly, quarterly, or annual books
- **Document rates**: Ensure exchange rates are recorded for each revaluation date
- **Consistent timing**: Use the same day each period (e.g., last business day)

### ✅ Accuracy
- **Verify rates**: Double-check exchange rates before posting
- **Review large adjustments**: Investigate unusually large gains/losses
- **Reconcile first**: Ensure bank reconciliation is complete before revaluation

### 🔒 Controls
- **Audit trail**: Keep documentation of rate sources
- **Sequential revaluation**: Don't skip periods—revalue in sequence
- **Review before posting**: Have a second pair of eyes on significant adjustments

---

## Troubleshooting

### No Lines Calculated

**Check**:
- Do you have foreign currency transactions?
- Are exchange rates recorded for the revaluation date?
- Is the current rate different from the historical rate?

### "Cannot Post" Error

**Check**:
- Is there at least one revaluation line?
- Is your company's default gain/loss account configured?
- Is the revaluation date within an open accounting period?

### Unexpected Adjustment Amounts

**Check**:
- Verify the historical (weighted average) rate is correct
- Confirm the current exchange rate for the revaluation date
- Check if there are multiple transactions at different rates

---

## Frequently Asked Questions

**Q: How often should I run currency revaluation?**

A: At minimum, run revaluation before closing each accounting period (monthly or quarterly). Many companies run it monthly for accurate interim reporting.

**Q: What happens when the foreign currency transaction is settled?**

A: When you receive payment or pay a bill, the system calculates the realized exchange difference. The unrealized adjustment from revaluation is effectively reversed as part of the settlement.

**Q: Can I revalue specific accounts only?**

A: The system processes all eligible foreign currency accounts. You cannot exclude specific accounts from a revaluation run.

**Q: Does revaluation affect my tax reporting?**

A: Unrealized gains/losses may or may not be taxable depending on your jurisdiction. Consult your accountant for tax treatment guidance.

---

## Related Documentation

- [Payments](payments.md) - Handling multi-currency payments
- [Opening Balances](opening-balances.md) - Setting up initial foreign currency balances
- [Vendor Bills](vendor-bills.md) - Creating bills in foreign currencies
- [Customer Invoices](customer-invoices.md) - Creating invoices in foreign currencies

---

Currency revaluation is essential for accurate financial reporting when dealing with multiple currencies. By regularly running revaluations at period-end, you ensure your balance sheet reflects true economic values and your P&L captures exchange rate movements.
