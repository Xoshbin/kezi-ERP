---
title: Understanding Withholding Tax
icon: heroicon-o-scissors
order: 7
---

# Understanding Withholding Tax

This guide explains **Withholding Tax (WHT)**—what it is, why we use it, and how the system handles it automatically. Written for everyone (even if you're not an accountant!), it covers the basics of tax deduction, certificates, and paying the government.

---

## What is Withholding Tax?

Imagine you order a pizza with your friends. Before you hand a slice to your friend, you take a small bite out of it. That "bite" is **Withholding Tax**.

In business terms:
When you pay a vendor for a service (like consulting or repairs), the government requires you to keep a small percentage of that money and pay it directly to the tax authority instead of giving it to the vendor.

**The Three Players:**
1.  **The Payer (You)**: You are responsible for deducting the tax and sending it to the government.
2.  **The Payee (Vendor)**: They receive slightly less cash than the bill amount, but they get a "Certificate" proving they paid their tax.
3.  **The Government**: Receives the tax money directly from the source (you).

**Why do we do this?**
It prevents tax evasion. By collecting tax *at the moment of payment*, the government ensures they get their share before the money leaves the country or disappears.

---

## How It Works in Kezi ERP

Our system automates this entire process so you don't have to use a calculator every time you pay a bill.

### 1. The Setup: Withholding Tax Types

First, we define the rules. DIFFERENT services have DIFFERENT tax rates.

| Service Type | Rate | Description |
|--------------|------|-------------|
| **Services** | 5% | General services like cleaning, IT support |
| **Legal Fees** | 10% | Lawyers and legal consultants |
| **Rent** | 10% | Office or equipment rent |
| **Goods** | 0% | Usually, buying physical goods is exempt |

**Where to find this**: Settings → Withholding Tax Types

### 2. The Logic: Thresholds

Sometimes, small payments are exempt.
*   **Example**: If the bill is under $500, no tax is deducted.
*   The system checks the **Line Amount** vs. the **Threshold** automatically.

---

## The Workflow: Step-by-Step

Let's walk through a real-life example.

**Scenario**: You hire a consultant, "TechSolutions," to fix your server. They send you a bill for **$1,000**. The WHT rate for services is **10%**.

### Step 1: The Vendor Bill 📄
You receive the bill and enter it into the system normally.
*   **Bill Amount**: $1,000
*   **Status**: Posted
*   **You Owe**: $1,000 (Account Payable)

*Nothing happens to tax yet! WHT happens at **Payment**, not Billing.*

### Step 2: The Payment 💸
You go to pay the bill. The system sees that "TechSolutions" is subject to WHT.

**The Math:**
1.  **Bill Amount**: $1,000
2.  **WHT Rate**: 10% ($100)
3.  **Payment Amount**: $900

**What happens**:
You write a check to TechSolutions for **$900**. The system asks: *"Wait, what about the other $100?"*

### Step 3: The Magic Deduction ✨
When you confirm the payment, the system automatically:
1.  Marks the **full $1,000 bill** as Paid.
2.  Takes **$900** from your Bank.
3.  Takes **$100** and moves it to a special "WHT Payable" account (a liability account meaning "Money linked to Government").
4.  Generates a **Withholding Tax Certificate**.

### Step 4: The Certificate 📜
The system creates a formal document called a **Withholding Tax Certificate**.
*   **You give this to TechSolutions.**
*   It proves that although you only paid them $900, the other $100 was paid as tax on their behalf. They use this to claim tax credits later.

### Step 5: Paying the Government 🏛️
Later (usually monthly), you pay the total accumulated tax to the government.
*   The system clears the "WHT Payable" account.
*   Money leaves your bank to the Tax Authority.

---

## Visualizing the Money Flow

```
                  ┌──────────────┐
                  │  Your Bank   │
                  │   ($1,000)   │
                  └──────┬───────┘
                         │
           ┌─────────────┴─────────────┐
           ▼                           ▼
    ┌──────────────┐            ┌──────────────┐
    │  The Vendor  │            │  Government  │
    │    ($900)    │            │    ($100)    │
    └──────────────┘            └──────────────┘
           ▲                           ▲
           │                           │
    (Gets Cash + Certificate)   (Gets Tax Payment)
```

---

## Best Practices

### 1. Check Your Vendor Setup
Ensure your vendors correspond to the correct **Applicable To** category (e.g., Services, Goods, Rent). If a vendor is set up incorrectly, the tax calculation will be wrong.

### 2. Don't Ignore Thresholds
If a payment is small, the system might skip WHT. This is normal! Check the "Threshold" setting in the WHT Type if you're unsure why tax wasn't applied.

### 3. Send Certificates Promptly
Your vendors need these certificates for their own tax filings. Sending them automatically via email (if configured) builds good relationships.

---

## Troubleshooting Common Questions

**Q: I paid a bill but no tax was deducted. Why?**
A: Check three things:
1.  Does the WHT Type have a **Threshold**? (Is the amount too small?)
2.  Is the Vendor configured for WHT?
3.  Did you pay for **Goods** instead of Services? (Goods are often 0%)

**Q: Can I edit the tax amount during payment?**
A: Generally, no. The system calculates it based on strict rules to ensure compliance. If the amount is wrong, the *rule* (WHT Type configuration) is likely wrong.

**Q: What if I forget to deduct tax?**
A: You (the payer) are liable! You might have to pay the tax to the government out of your own pocket because you already gave the full cash to the vendor. The system prevents this by automating the deduction.

---

## Related Documentation

- [Vendor Bills](vendor-bills.md) - How to enter bills
- [Payments](payments.md) - Processing payments
- [Tax Settings](tax-settings.md) - Configuring rates
