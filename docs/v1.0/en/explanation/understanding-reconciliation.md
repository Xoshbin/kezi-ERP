# Understanding Reconciliation

Reconciliation is the process of ensuring that two sets of records (usually the balances of two accounts) are in agreement. In Kezi, this primarily refers to **Bank Reconciliation**: matching your internal accounting records against your actual bank statements.

## Why Reconcile?

Reconciliation is a critical internal control that ensures your records are accurate and complete. It helps you:
- **Verify Accuracy**: Ensure that every transaction on your bank statement is recorded in your books.
- **Detect Errors**: Find mistakes made by the bank or within your own data entry.
- **Prevent Fraud**: Identify unauthorized transactions.
- **Monitor Cash Flow**: Know exactly how much "real" cash you have available.

## The Reconciliation Flow

Kezi uses a two-step "Outstanding" flow to maintain high-integrity double-entry accounting. This ensures that the debt is cleared when the check is written, but the cash is only reduced when the bank actually clears it.

### 1. Recording the Internal Payment
When you pay a vendor or receive money from a customer, you record a **Payment**.
- **Financial Impact**: The balance moves from the Partner Account (A/P or A/R) to an **Outstanding Account**.
- **Status**: `Confirmed`.
- **Journal Entry Example (Vendor Payment)**:
  - Debit: Accounts Payable
  - Credit: **Outstanding Payments**

### 2. Matching with Bank Statement
When you receive your bank statement, you use the **Reconciliation Matcher** to link it to the confirmed payment.
- **Financial Impact**: The balance moves from the Outstanding Account to the actual **Bank Account**.
- **Status**: `Reconciled`.
- **Journal Entry Example**:
  - Debit: **Outstanding Payments**
  - Credit: Bank Account

## Direct Reconciliation (Write-offs)

Sometimes, you might have a bank transaction that doesn't have a pre-recorded internal payment (like bank fees or interest). In these cases, you can perform a **Direct Reconciliation** (Write-off).
- I.e., you "write off" the statement line directly to an expense or income account.
- **Journal Entry (Bank Fee)**:
  - Debit: Bank Service Charges
  - Credit: Bank Account

## Key Concepts

| Concept | Description |
| :--- | :--- |
| **Outstanding Account** | A temporary account used to hold a balance until it is confirmed by the bank. |
| **Statement Line** | An individual transaction from a bank statement. |
| **Matcher** | The visual interface used to link statement lines with internal payments. |
| **Reconciled** | The final state where internal records and bank records agree. |

## Related Guides
- [How to Record Payments](../how-to/recording-payments.md)
- [How to Reconcile Bank Statements](../how-to/bank-reconciliation.md)
- [Payment Methods & Statuses](../reference/payment-methods.md)
