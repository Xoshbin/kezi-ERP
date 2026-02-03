---
title: Understanding Petty Cash Management
icon: heroicon-o-banknotes
order: 8
---

# Understanding Petty Cash Management

This guide explains **Petty Cash**—what it is, how to manage it properly, and how the system keeps your small expenses organized and accounted for. Written for everyone (even if accounting makes your head spin!), it covers setting up funds, tracking vouchers, and keeping everything balanced.

---

## What is Petty Cash?

Imagine you need to buy coffee for an important client meeting or pay for parking when delivering documents. You can't use a company credit card every time, and you definitely can't wait days for approval on a $5 expense.

That's where **Petty Cash** comes in—it's like having a small cash allowance at your fingertips for those tiny, urgent expenses.

**In business terms:**
Petty Cash is a small amount of physical money kept on hand to cover minor day-to-day expenses that are too small or urgent to write a check or process through accounts payable.

**The Key Players:**
1. **The Custodian**: A trusted employee (like an office manager) who physically holds and manages the cash.
2. **The Fund**: A fixed amount of money (like $500) that's established and needs to stay constant.
3. **Vouchers**: Little receipts that document every single penny spent from the fund.

**Why track it so carefully?**
Because cash is easy to lose track of! The system ensures every cent is accounted for, preventing both accidental losses and intentional misuse.

---

## How It Works in Kezi ERP

Our system turns old-school petty cash management into a digital, trackable process—no more shoebox full of receipts!

### 1. The Setup: Creating a Petty Cash Fund

Think of this as setting up your cash drawer. You decide:
- **Who's in charge?** (The Custodian)
- **How much money?** (The Imprest Amount—let's say $500)
- **Where does it come from?** (Which bank account)

**Example:**
"Main Office Fund" managed by Sarah, with $500 taken from our operating bank account.

**Where to find this:** Accounting → Petty Cash → Funds

---

## The Imprest System: The Golden Rule

Here's the magic: **The fund should always contain $500 in total (cash + receipts).**

Let's say you start with $500 cash:
- Day 1: Spend $20 on taxi fare → Now you have $480 cash + $20 receipt = Still $500 total
- Day 2: Spend $35 on office supplies → Now you have $445 cash + $55 receipts = Still $500 total
- Day 5: Spend $100 on client lunch → Now you have $345 cash + $155 receipts = Still $500 total

When the cash gets low (maybe $345 is too little), you **replenish** it by depositing exactly $155 back in, bringing it back to $500 cash!

---

## The Workflow: Real-Life Example

Let's follow a typical day with petty cash.

### Scenario: Office Supplies Emergency 🖊️

It's Monday morning. The printer runs out of toner, and you need it for a presentation in 2 hours. Sarah (the custodian) grabs $45 from the petty cash drawer and runs to the store.

#### Step 1: The Purchase
Sarah buys toner for **$45** and gets a receipt.

#### Step 2: Creating a Voucher 📝
Sarah logs into the system and creates a petty cash voucher:
- **Amount**: $45
- **Description**: "Emergency toner cartridge for HP printer"
- **Expense Account**: Office Supplies
- **Receipt Reference**: Receipt #12345
- **Date**: Today

*Status: Draft*

The voucher is saved but nothing happens to the accounting yet.

#### Step 3: Posting the Voucher ✅
Sarah's manager reviews and approves the voucher. When they click "Post":

**The system automatically:**
1. **Deducts $45** from the Petty Cash Fund balance
2. **Records an expense** in Office Supplies
3. **Creates a journal entry** linking everything to the General Ledger
4. **Locks the voucher** (can't be changed anymore—that's the whole point of an immutable system!)

**What Sarah sees:**
- Fund balance drops from $500 → $455
- The voucher shows "Posted" status
- She can print it if needed for the files

---

## Fund Replenishment: Refilling the Cookie Jar 🍪

After a week, Sarah has used $200 from petty cash (lots of small expenses). The drawer only has $300 left, and she's nervous about running out.

### The Replenishment Process

#### Step 1: Check the Balance
Sarah checks the system:
- **Imprest Amount**: $500 (the goal)
- **Current Balance**: $300
- **Needed**: $200

#### Step 2: Create Replenishment 💰
Sarah creates a replenishment:
- **Amount**: $200 (system calculates this automatically!)
- **Payment Method**: Bank Transfer
- **Date**: Today
- **Reference**: Bank transfer #TRX-98765

#### Step 3: The Money Movement
The accountant approves it, and:
1. **$200 leaves the bank account**
2. **$200 is added to the petty cash fund**
3. **Both transactions are recorded in the GL**

**Result:**
- Fund balance: $300 → $500 ✅
- Sarah can breathe easy again
- The fund is back to full strength

---

## Visualizing the Petty Cash Cycle

```
    ┌─────────────────────────────────┐
    │   START: Fund Created           │
    │   Cash: $500                    │
    └──────────────┬──────────────────┘
                   │
                   ▼
    ┌─────────────────────────────────┐
    │   Daily Expenses (Vouchers)     │
    │   Cash: $500 → $450 → $380 ...  │
    └──────────────┬──────────────────┘
                   │
                   ▼
    ┌─────────────────────────────────┐
    │   Balance Getting Low!          │
    │   Cash: $300 (need more!)       │
    └──────────────┬──────────────────┘
                   │
                   ▼
    ┌─────────────────────────────────┐
    │   Replenishment                 │
    │   Cash: $300 + $200 = $500      │
    └──────────────┬──────────────────┘
                   │
                   └──────┐
                          │
    ┌─────────────────────▼───────────┐
    │   REPEAT THE CYCLE!             │
    └─────────────────────────────────┘
```

---

## Best Practices

### 1. Set a Low Balance Threshold
Configure your fund to show a warning when cash drops below, say, $100. This gives you time to arrange a replenishment before running out.

**In the system:** When creating a fund, you can set "Low Balance Threshold" to $100.

### 2. Always Get Receipts
No receipt = No voucher = No money back!

The custodian should be trained: ***"If there's no receipt, it comes out of your pocket."***

### 3. Post Vouchers Promptly
Don't wait until month-end to enter 50 vouchers. Enter them the same day or week while the memory is fresh.

### 4. Regular Surprise Counts
Once a month, randomly count the physical cash + receipts. They should equal the system balance. This prevents errors and keeps everyone honest.

### 5. Close Inactive Funds
If a fund hasn't been used in 6 months (maybe a branch office closed), mark it as "Closed" to prevent confusion.

---

## Security & Controls

### Who Can Do What?

| Role | Create Fund | Create Voucher | Post Voucher | Replenish |
|------|-------------|----------------|--------------|-----------|
| **Custodian** | ❌ | ✅ | ❌ | ❌ |
| **Manager** | ❌ | ✅ | ✅ | ❌ |
| **Accountant** | ✅ | ✅ | ✅ | ✅ |
| **CFO** | ✅ | ✅ | ✅ | ✅ |

**Why separate duties?**
The person holding the cash shouldn't be the one approving their own expenses. This separation prevents fraud.

### Audit Trail

Every action is logged:
- Who created the voucher?
- When was it posted?
- Who approved the replenishment?

The system creates an **immutable record**—once posted, you can't go back and change history. If there's a mistake, you create a new correcting entry.

---

## Common Scenarios & Solutions

### Scenario 1: Lost Receipt 😱
**Problem:** Sarah lost the receipt for a $15 taxi fare.

**Solution:**
1. Sarah writes a memo explaining what happened
2. Her manager approves it as an exception
3. The voucher includes "No receipt - approved exception" in notes
4. This should be **rare**—make it uncomfortable so people don't lose receipts

### Scenario 2: Fund Running Negative
**Problem:** Someone spent$25 when only $20 was left in the fund.

**Solution:**
The system **won't let you** post a voucher that exceeds the current balance! You'll get an error: *"Insufficient petty cash balance."*

You must:
1. Replenish the fund first
2. Then post the voucher

### Scenario 3: Multiple Funds
**Problem:** You have 3 branch offices, each needs its own petty cash.

**Solution:**
Create 3 separate funds:
- "Head Office Fund" - Sarah's responsibility
- "North Branch Fund" - Ahmed's responsibility  
- "South Branch Fund" - Layla's responsibility

Each fund is completely independent with its own balance, custodian, and tracking.

### Scenario 4: Wrong Expense Account
**Problem:** Sarah posted a voucher to "Office Supplies" but it should have been "Transportation".

**Solution in immutable system:**
You **cannot edit** a posted voucher. Instead:
1. Create a journal entry to reclassify the expense
2. Add notes referencing the original voucher number
3. Train staff to double-check before posting

---

## Troubleshooting Common Questions

**Q: Why can't I edit a posted voucher?**
A: Because it's already recorded in the General Ledger! Changing it would break the audit trail. Think of it like signing a legal document—once signed, you can't erase your signature.

**Q: Can I use petty cash for a $500 expense?**
A: Technically yes, but **you probably shouldn't**. Petty cash is for small, urgent purchases. Large expenses should go through the normal accounts payable process for better tracking and approval.

**Q: What if the custodian quits?**
A: 
1. Do a physical count of the cash
2. Create a replenishment if needed to get back to $500
3. Change the custodian name in the fund settings
4. Hand over the cash box to the new custodian

**Q: How often should I replenish?**
A: There's no fixed rule, but generally:
- **High activity fund**: Weekly
- **Low activity fund**: Monthly
- **Emergency**: Whenever balance drops below your threshold

**Q: Can I have a fund in USD and another in IQD?**
A: Absolutely! Each fund has its own currency. Just make sure you're not mixing currencies in the same physical cash box!

---

## Related Documentation

- [Chart of Accounts](chart-of-accounts.md) - Setting up expense accounts
- [Journal Entries](journal-entries.md) - Understanding the GL impact
- [Financial Periods](financial-periods.md) - Lock dates and periods

---

## Quick Reference Card

**Creating a New Fund:**
1. Accounting → Petty Cash → Funds → Create
2. Set name, custodian, amount, accounts
3. Save and transfer money to the custodian

**Recording an Expense:**
1. Accounting → Petty Cash → Vouchers → Create
2. Select fund, amount, expense account
3. Add description and receipt reference
4. Save (Draft)
5. Get approval and Post

**Replenishing:**
1. Accounting → Petty Cash → Replenishments → Create
2. Select fund (amount auto-calculates!)
3. Enter payment method and reference
4. Save and process

**Pro Tip:** The system shows you the fund balance in **red** when it's below your threshold—that's your signal to replenish!
