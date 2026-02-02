---
title: Tax Management
icon: heroicon-o-calculator
order: 6
---

# Tax Management: Automating Your Tax Calculations

This guide explains how Tax Management works in plain language. Whether you're dealing with VAT, Sales Tax, or other levies, this feature helps you automate calculations and keep your records accurate.

---

## What is Tax Management?

Think of Tax Management as your automated calculator for government dues. When you buy or sell products, there's often a tax involved. Instead of manually calculating $100 + 15% tax every time, the system does it for you.

**Why does this matter?**
1. **Accuracy**: Eliminates math errors in your invoices and bills.
2. **Compliance**: Ensures you're collecting and paying the right amounts to tax authorities.
3. **Reporting**: Makes it easy to see exactly how much tax you owe (Tax Payable) or can reclaim (Tax Receivable).

---

## Where to Find It

Navigate to: **Accounting → Configuration → Taxes**

You'll see a list of all your configured taxes.

> **💡 Tip**: You can filter taxes by Sale, Purchase, or Tax Scope to find what you need quickly.

---

## Creating a New Tax

Let's walk through setting up a new tax rate.

### Step 1: Start Fresh

Navigate to **Accounting → Configuration → Taxes** and click **Create Tax**.

You'll see a form with these sections:

### Basic Information

| Field | What to Enter | Example |
|-------|---------------|---------|
| **Name** | A clear name for the tax | "VAT 15%" or "Sales Tax" |
| **Tax Computation** | How the tax is calculated | Percentage of Price |
| **Amount** | The rate or value | 15 (for 15%) |
| **Tax Type** | Where this tax applies | Sales, Purchase, or None |
| **Tax Scope** | Limit to specific categories | Goods or Services |

### Accounting Configuration

This is where you tell the system which accounts to use for bookkeeping.

- **Tax Received Account**: Where sales tax goes (Liability). e.g., "Output VAT".
- **Tax Paid Account**: Where purchase tax goes (Asset/Expense). e.g., "Input VAT".

> **[!IMPORTANT]**
> Correct account mapping is crucial for your tax reports. If you're unsure, ask your accountant.

### Advanced Options

- **Label on Invoices**: What your customers see on their invoice lines (e.g., "VAT").
- **Included in Price**: Check this if your product prices *already include* tax (e.g., a $115 item includes $15 tax).

### Step 2: Save

Click **Create** to save your new tax. It's now ready to be used on products and partners!

---

## Tax Computation Methods

The system supports various ways to calculate tax:

1. **Percentage of Price**: The most common. Standard 15% VAT on $100 = $15 tax.
2. **Fixed**: A fixed amount regardless of price. e.g., $5 eco-tax per tire.
3. **Group of Taxes**: Combines multiple taxes. e.g., City Tax + State Tax.
4. **Python Code**: For complex, custom logic (advanced users).

---

## Understanding Tax Types

- **Sales**: Applied to customer invoices. This increases what the customer pays you.
- **Purchase**: Applied to vendor bills. This increases what you pay your supplier.
- **None**: For informational or disabled taxes.

---

## Grouping Taxes

Sometimes you need to apply multiple taxes at once. For example, a "Local Tax" and a "Federal Tax".

1. **Create the individual taxes** first (e.g., "City Tax 2%" and "State Tax 5%").
2. **Create a new Tax** called "Total Sales Tax".
3. **Set Computation** to "Group of Taxes".
4. **Add the sub-taxes** to the group.

Now, selecting "Total Sales Tax" applies both automatically!

---

## Best Practices

### 📅 Classification
- Use clear names like "Sales VAT 15%" vs "Purchase VAT 15%" to avoid confusion.

### ✅ Accounts
- Ensure you have separate accounts for **Tax Payable** (Liability) and **Tax Receivable** (Asset) to track your net position easily.

### 🔒 Updates
- If tax rates change (e.g., government changes VAT from 15% to 16%), **create a new tax** instead of editing the old one. This preserves the history of old transactions.

---

## Frequently Asked Questions

**Q: What is "Price Include"?**

A: It means the tax is already inside the item price.
- **Price Exclude** (Default): Item $100 + Tax $15 = Total $115.
- **Price Include**: Item Price $115. The system calculates that the real price is $100 and tax is $15.

**Q: Can I apply taxes to specific products?**

A: Yes! You can set a default tax on a **Product** form. Whenever you sell that product, the specific tax will be applied automatically.

**Q: How do I see how much tax I owe?**

A: Check your **Balance Sheet** for your Tax Liability accounts, or generate a **Tax Report** (if available) to see a detailed breakdown.
