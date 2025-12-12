---
title: Loan Agreements
icon: heroicon-o-banknotes
order: 5
---

# Loan Agreements: Comprehensive Loan Management and Accounting

This comprehensive guide explains how loan agreements work in your accounting system, covering setup, payment schedules, interest accrual, and financial reporting. Written for all users — accountants and non‑accountants — it provides practical guidance following double‑entry accounting best practices.

---

## What is a Loan Agreement?
A Loan Agreement represents money borrowed by your company (Payable) or money lent to a partner (Receivable). Each loan keeps its own currency, principal, interest terms, and repayment schedule.

Key attributes:
- Loan Type: Receivable (you are the lender) or Payable (you are the borrower)
- Partner: Counterparty (customer, vendor, or other partner)
- Currency: Document currency for principal and all schedule amounts
- Dates:
  - Loan Date: When the obligation originates or is recognized
  - Start Date: When installment schedule begins
  - Maturity Date: Optional target end date
- Principal Amount: Initial amount of the loan
- Duration (months): Number of installments in the schedule
- Schedule Method:
  - Annuity: Equal total payments; interest decreases and principal increases over time
  - Straight Line Principal: Equal principal each period; total payment decreases over time
- Interest Rate (nominal annual): Used for schedule calculations (unless using EIR)
- EIR (Effective Interest Rate) support: Optional recalculation using EIR logic

Status:
- Draft: Editable
- Posted/Active (implementation detail): Used by actions; operations create posted journal entries

---

## Where to find it in the UI
- Accounting → Loan Agreements: Create, view, and manage loans
- On the View page of a loan, you have action buttons for: Compute Schedule, Recalculate EIR, Accrue Interest, Post Repayment, and Reclassify Current Portion
- Related tables in the view:
  - Schedule Entries: The periodic payment plan (payment amount, principal, interest, balance)
  - Fee Lines: One‑off or periodic fees that may be capitalized
  - Rate Changes: Effective‑date changes to annual interest used in the schedule

Tip: The header’s Help/Docs button opens this guide.

---

## Creating a Loan Agreement
Use Create to define the baseline:
1) Partner and Currency: Select your counterparty and the loan currency
2) Dates and Duration: Set loan date, start date, duration months; maturity is optional
3) Amounts: Enter Principal Amount (Outstanding Principal is tracked automatically)
4) Terms: Choose Schedule Method and Annual Interest Rate; toggle EIR if you’ll use effective interest
5) Save

Outcome: A Draft loan is created. You can now compute a schedule and proceed with accruals and repayments.

---

## Compute the Repayment Schedule
Purpose: Build the amortization schedule used for accruals and repayments.

Steps:
1) Open the loan → View page
2) Click Compute Schedule
3) The system deletes any prior schedule entries and recalculates the full schedule based on:
   - Principal, duration, start date
   - Schedule method (Annuity or Straight Line Principal)
   - Current or updated annual interest rate per month (Rate Changes table)

Result: The Schedule Entries table shows, for each installment:
- Due Date
- Payment Amount
- Principal Component
- Interest Component
- Outstanding Balance After

Notes:
- Rate Changes: If provided, they adjust the annual rate as of their effective month.
- Currency: All schedule amounts are in the loan currency.

---

## Recalculate using EIR (optional)
If EIR is enabled, you can re‑derive interest vs principal using the effective interest rate:
1) Open the loan → View page
2) Click Recalculate EIR
3) The system recomputes the interest allocation using EIR on the carrying amount across the remaining schedule

Why: EIR is often required under IFRS/GAAP for certain loans/fees to recognize interest using the internal rate of return over the loan’s life.

---

## Accrue Interest (periodic)
Purpose: Recognize interest expense (for Payable loans) or interest income (for Receivable loans) up to a given installment.

Steps:
1) Open the loan → View page → Accrue Interest
2) Fill the dialog:
   - Journal: Where to post the accrual
   - Interest Expense/Income Account
   - Accrued Interest (liability/asset) Account
   - Installment #: Which schedule period to accrue
3) Confirm

Accounting entry (example for Payable):
- Dr Interest Expense
- Cr Accrued Interest

Notes:
- The accrual amount equals the schedule entry’s interest component for the selected installment.
- The entry date uses the schedule entry’s due date.
- For Receivable loans, the accounts are reversed appropriately (income instead of expense).

---

## Post Repayment (cash movement)
Purpose: Record a repayment (or receipt) for a given installment, settling accrued interest and principal.

Steps:
1) Open the loan → View page → Post Repayment
2) Fill the dialog:
   - Journal: Bank or Cash journal
   - Bank Account: Cash/bank GL account
   - Loan Account: Loan principal account (payable or receivable)
   - Accrued Interest Account: Same one used by accruals
   - Installment #: Which schedule period you’re paying
3) Confirm

Accounting entry (Payable loan example):
- Dr Accrued Interest (for that period’s interest)
- Dr Loan Payable (principal component)
- Cr Bank (total payment)

For Receivable loans, the direction reverses (Dr Bank; Cr Accrued Interest; Cr Loan Receivable).

Important: Loan repayments are not recorded via the Payments module. Use the Post Repayment action on the Loan Agreement. These journal entries will later be reconciled to your bank statement.

---

## Reclassify Current Portion (periodic, typically month‑end or year‑end)
Purpose: Move the portion of a long‑term loan due within the next N months from long‑term to short‑term.

Steps:
1) Open the loan → View page → Reclassify Current Portion
2) Fill the dialog:
   - Journal
   - Long‑term Account (e.g., Loans Payable – LT)
   - Short‑term Account (e.g., Current Portion of LT Debt)
   - Months (e.g., 12)
   - As of date
3) Confirm

Accounting entry (Payable loan example):
- Dr Long‑term Loan
- Cr Current Portion of Long‑term Loan

The amount equals the principal scheduled within the next N months from the as‑of date.

---

## Fee Lines and Capitalization
You can record fees (e.g., origination fees) with optional capitalization.
- If capitalized, fees affect carrying amount and may interact with EIR logic
- Non‑capitalized fees are typically expensed when incurred

---

## Multi‑Currency Behavior
- The loan’s currency is fixed and used for all schedule calculations
- Monetary amounts are stored as Money objects in the loan currency
- When journal entries post, standard base‑currency conversions apply per your company’s settings

---

## Interactions with Other Modules
- Payments: Do not use Payments to post loan repayments. Payments are reserved for AR/AP settlements and partner advances. Loan cash movements are posted by the loan’s Post Repayment action.
- Bank Statements & Reconciliation: Repayment journal entries appear in your bank ledger and will be matched to imported bank statement lines during reconciliation.
- Lock Dates: Loan Date, accruals, and repayments respect lock‑date rules. If a period is locked, you must use an allowed date or adjust the lock.

---

## Good Practices
- Compute schedule immediately after creation and whenever you update terms or add rate changes
- Accrue interest monthly; do not combine multiple months in one accrual unless policy allows
- Use Reclassify Current Portion at period‑end for accurate current vs long‑term presentation
- Keep consistent accounts for interest, accruals, and principal to maintain clean reporting
- Add clear references in journal entries (the system includes structured references like LOAN‑INT/ID/SEQ, LOAN‑PAY/ID/SEQ)

---

## FAQ
Q: Can I record a loan repayment with Payments?
A: No. Use the Loan Agreement’s Post Repayment action. Payments are for AR/AP settlements and partner advances.

Q: Can I partially pay an installment?
A: The current UI posts by installment. To reflect partial payments, consider splitting the schedule or post manual adjustments; future UI iterations may support partial postings.

Q: What about variable interest?
A: Use Rate Changes to alter the annual rate from a given effective month; recompute the schedule to apply changes.

Q: When should I use EIR?
A: When required by policy or standards to recognize interest using effective yield over the loan’s life, especially when fees are capitalized.

---

Need more help? Click Help / Docs from any Loan Agreement page to open this guide.
