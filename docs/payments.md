# Payments: Easy Guide for Everyone (Odoo‑style)

This guide explains how Payments work in the system, how to create a Payment from the Payments menu, and how to register a payment directly on an Invoice or a Vendor Bill. We wrote it for all users — accountants and non‑accountants — so it’s simple, practical, and trustworthy. It follows double‑entry accounting best practices.

---

## What is a Payment?
A Payment is money moving between your company and a partner:
- Inbound (Receive money) — from a Customer
- Outbound (Send money) — to a Vendor

Each Payment belongs to a Bank or Cash journal and has a date, amount, currency, method (e.g. bank transfer, cash), and optional reference.

You can record a Payment in two main ways:
- Standalone Payment — when you simply received/sent money (e.g. a customer advance) without linking to a specific invoice/bill yet
- Settlement Payment — linked to an existing Invoice (AR) or Vendor Bill (AP) to partially or fully settle it

Key statuses you may see:
- Draft — just created, editable
- Confirmed — posted to accounting, immutable to protect audit trail
- Reconciled — matched to bank statement during reconciliation

Tip: We don’t allow deleting confirmed financial documents. Use proper reversal workflows when needed.

---

## Where do Payments appear in the system?
- Payments menu: Create and manage standalone payments (Banking & Cash)
- Invoices: Register Payment action for posted (unpaid) Invoices
- Vendor Bills: Register Payment action for posted (unpaid) Bills
- Bank Reconciliation: Confirmed payments are matched to bank statement lines and marked Reconciled

---

## 1) Create a Payment from the Payments menu
Use this when money moved but is not directly tied to a specific invoice/bill (e.g. customer deposit, vendor advance, or to record an immediate payment that you’ll later link).

Steps:
1) Go to Accounting → Banking & Cash → Payments
2) Click Create
3) Fill the fields:
   - Payment Type: Receive (from customer) or Send (to vendor)
   - Journal: Bank or Cash journal where the money moved
   - Payment Date
   - Currency and Amount
   - Partner: Who paid/received the money
   - Payment Method: e.g. Bank Transfer or Cash
   - Reference: Optional internal note or bank reference
4) Save, then Confirm when ready

Result:
- A Draft payment is created
- When you Confirm, accounting entries are posted in the selected journal
- Later you can apply this payment to invoices/bills (settlement) or reconcile it with your bank statement

---

## 2) Register a Payment on an Invoice (Customer payment)
Use this to collect money for a posted customer invoice.

Prerequisites:
- The invoice must be Posted (not Draft)
- There must be a remaining amount due

Steps:
1) Open the Invoice
2) Click Register Payment (yellow banknotes icon)
3) Fill the dialog:
   - Journal: Usually your bank
   - Payment Date: The date money arrived
   - Amount: Defaults to Remaining Amount; adjust for partial payments
   - Reference: Optional
4) Confirm

What happens:
- The system creates an Inbound payment linked to the invoice
- The payment is immediately Confirmed and reduces the invoice’s due amount
- If you paid partially, the invoice remains Partially Paid with a new remaining balance
- Later, during bank reconciliation, this payment can be matched to a statement line and marked Reconciled

---

## 3) Register a Payment on a Vendor Bill (Vendor payment)
Use this to pay a posted vendor bill.

Prerequisites:
- The bill must be Posted (not Draft)
- There must be a remaining amount due

Steps:
1) Open the Vendor Bill
2) Click Register Payment (yellow banknotes icon)
3) Fill the dialog:
   - Journal: Usually your bank
   - Payment Date: The date money left
   - Amount: Defaults to Remaining Amount; adjust for partial payments
   - Reference: Optional
4) Confirm

What happens:
- The system creates an Outbound payment linked to the bill
- The payment is immediately Confirmed and reduces the bill’s due amount
- Partial payments leave a remaining amount to pay
- Later you can reconcile this payment against your bank statement

---

## Multi‑Currency behavior (summary)
- You can create payments in any currency configured for your company
- The payment stores both the payment currency amount and the equivalent in the company base currency
- When applying payments to invoices/bills with different currencies, the system calculates realized exchange gains/losses according to accounting principles

---

## Bank Reconciliation overview
After confirming payments and importing/creating bank statements, you can reconcile:
- Match confirmed payments to statement lines
- When matched, status becomes Reconciled and a reconciliation entry is posted
- This ensures your bank balance in the system equals the bank statement

Where to find it: Accounting → Banking & Cash → Bank Statements → open a statement → Reconcile

---

## Good practices & tips
- Confirm only when sure: Confirmed payments are immutable (for audit trail). Use corrections via new entries if needed
- Use References: Add bank reference or notes to make matching and audits easier
- Prefer Register Payment from the invoice/bill when money settles a specific document
- Use standalone payments for deposits or advances and apply them later
- Reconcile regularly: Keep your books aligned with the bank

---

## Glossary
- Partner: Customer or Vendor
- Journal: Where entries are posted (Bank/Cash)
- Settlement: Applying a payment to an invoice or bill
- Reconciliation: Matching system payments to bank statements

---

## Frequently Asked Questions
Q: Can I delete a confirmed payment?
A: No. Financial documents become immutable once confirmed to preserve the audit trail. Reverse or adjust with new entries.

Q: Can I do partial payments?
A: Yes. Enter a smaller amount when registering payment. The document remains partially paid.

Q: How does foreign currency work?
A: We store original amounts and base‑currency equivalents; gains/losses are realized correctly when you settle.

---

Need more help? Click the Help/Docs button in Payments, Invoices, Vendor Bills, or during Reconciliation to open this guide.


---

## Example

```bash
# Create a standalone payment (CLI example)
echo "Record an inbound payment and confirm it"
```
